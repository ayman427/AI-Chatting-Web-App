<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chat_sessions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('name'); // Custom session name
            $table->timestamps();
        });

        Schema::table('chats', function (Blueprint $table) {
            $table->unsignedBigInteger('chat_session_id')->after('id');
            $table->foreign('chat_session_id')->references('id')->on('chat_sessions')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_sessions');
        Schema::table('chats', function (Blueprint $table) {
            $table->dropColumn('chat_session_id');
        });
    }
};
