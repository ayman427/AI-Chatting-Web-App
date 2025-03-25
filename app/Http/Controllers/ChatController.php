<?php
namespace App\Http\Controllers;

use App\Models\Chat;
use App\Models\ChatSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Symfony\Component\DomCrawler\Crawler;

class ChatController extends Controller
{
    public function fetchSessions()
    {
        $userId   = Auth::id();
        $sessions = ChatSession::where('user_id', $userId)->orderBy('created_at', 'desc')->get();
        return response()->json($sessions);
    }

    public function createSession(Request $request)
    {
        $request->validate(['name' => 'required|string|max:255']);

        $session = ChatSession::create([
            'user_id' => Auth::id(),
            'name'    => $request->name,
        ]);

        return response()->json($session);
    }

    public function fetchChats($sessionId)
    {
        $userId = Auth::id();
        $chats  = Chat::where('chat_session_id', $sessionId)->where('user_id', $userId)->orderBy('created_at')->get();
        return response()->json($chats);
    }

    public function sendMessage(Request $request)
    {
        $request->validate([
            'chat_session_id' => 'required|exists:chat_sessions,id',
            'message'         => 'required|string',
            'model'           => 'required|string',
            'web_search'      => 'sometimes|boolean',
        ]);

        $userId    = Auth::id();
        $sessionId = $request->input('chat_session_id');
        $message   = $request->input('message');
        $model     = $request->input('model');
        $webSearch = $request->input('web_search', false);

        // Perform web search if enabled
        if ($webSearch) {
            try {
                $searchResponse = Http::get('https://html.duckduckgo.com/html/', [
                    'q' => $message,
                ]);

                if ($searchResponse->successful()) {
                    $crawler = new Crawler($searchResponse->body());

                    // Extract top 3 search results
                    $webResults = $crawler->filter('.result__title a')->each(function ($node, $i) {
                        if ($i < 3) { // Limit to 3 results
                            return $node->text() . " - " . $node->attr('href');
                        }
                    });

                    $webResultsText = implode("\n", array_filter($webResults));

                    // Save web search results without AI response
                    $chat = Chat::create([
                        'chat_session_id' => $sessionId,
                        'user_id'         => $userId,
                        'message'         => $message,
                        'response'        => "**Web Results:**\n" . $webResultsText,
                        'model'           => 'web_search',
                    ]);

                    return response()->json($chat);
                }
            } catch (\Exception $e) {
                return response()->json(['error' => 'Web search failed. Please try again.'], 500);
            }
        }

        // Retrieve past messages for AI response
        $chatHistory = Chat::where('chat_session_id', $sessionId)
            ->orderBy('created_at')
            ->get(['message', 'response']);

        $messages = [];
        foreach ($chatHistory as $chat) {
            $messages[] = ['role' => 'user', 'content' => $chat->message];
            $messages[] = ['role' => 'assistant', 'content' => $chat->response];
        }

        // Add current user message
        $messages[] = ['role' => 'user', 'content' => $message];

        // Call OpenRouter AI API
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . env('OPENROUTER_API_KEY'),
            ])->post('https://openrouter.ai/api/v1/chat/completions', [
                'model'       => $model,
                'temperature' => 0.3,
                'top_p'       => 0.85,
                'messages'    => $messages,
            ]);

            $botMessage = $response->json()['choices'][0]['message']['content'] ?? 'Error fetching response';

            // Save AI chat response
            $chat = Chat::create([
                'chat_session_id' => $sessionId,
                'user_id'         => $userId,
                'message'         => $message,
                'response'        => $botMessage,
                'model'           => $model,
            ]);

            return response()->json($chat);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch AI response. Please try again.'], 500);
        }
    }

    public function destroy($id)
    {
        $session = ChatSession::where('id', $id)->where('user_id', Auth::id())->first();

        if (! $session) {
            return response()->json(['success' => false, 'message' => 'Session not found'], 404);
        }

        try {
            // Delete related chat messages first
            Chat::where('chat_session_id', $id)->delete();

            // Delete the session
            $session->delete();

            return response()->json(['success' => true, 'message' => 'Session deleted']);
        } catch (\Exception $e) {
            \Log::error("Error deleting session: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to delete session'], 500);
        }
    }

    private function searchWeb($query)
    {
        $url      = "https://api.duckduckgo.com/?q=" . urlencode($query) . "&format=json&no_html=1";
        $response = Http::get($url);

        if ($response->successful()) {
            $data    = $response->json();
            $results = [];

            if (isset($data['RelatedTopics'])) {
                foreach ($data['RelatedTopics'] as $topic) {
                    if (isset($topic['Text']) && isset($topic['FirstURL'])) {
                        $results[] = $topic['Text'] . " - " . $topic['FirstURL'];
                    }
                }
            }

            return implode("\n", $results);
        }

        return null;
    }

}
