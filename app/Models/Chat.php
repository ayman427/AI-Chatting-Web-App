<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Chat extends Model
{
    use HasFactory;

    protected $fillable = ['chat_session_id', 'user_id', 'message', 'response', 'model'];

    public function session()
    {
        return $this->belongsTo(ChatSession::class, 'chat_session_id');
    }
}
