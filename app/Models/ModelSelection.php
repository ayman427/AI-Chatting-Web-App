<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ModelSelection extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'api_key', 'api_url'];

    public function chats()
    {
        return $this->hasMany(Chat::class, 'model_id');
    }
}
