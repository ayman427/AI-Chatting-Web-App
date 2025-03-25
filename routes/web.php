<?php

use App\Http\Controllers\ChatController;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Volt::route('settings/profile', 'settings.profile')->name('settings.profile');
    Volt::route('settings/password', 'settings.password')->name('settings.password');
    Volt::route('settings/appearance', 'settings.appearance')->name('settings.appearance');

    Route::post('/chat', [ChatController::class, 'chat']);
    Route::get('/chat', function () {
        return view('chat'); // This serves the chat interface
    });

    Route::get('/sessions', [ChatController::class, 'fetchSessions']);
    Route::post('/sessions', [ChatController::class, 'createSession']);
    Route::get('/chats/{sessionId}', [ChatController::class, 'fetchChats']);
    Route::post('/chat', [ChatController::class, 'sendMessage']);
    Route::delete('/delete-session/{id}', [ChatController::class, 'destroy'])->name('chat.destroy');
});

require __DIR__ . '/auth.php';
