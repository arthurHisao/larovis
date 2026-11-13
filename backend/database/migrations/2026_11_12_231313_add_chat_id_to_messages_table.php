<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->foreignId('chat_id')
                  ->after('id')
                  ->constrained('chats')
                  ->onDelete('cascade');

            $table->dropColumn('receiver_id');
            $table->unsignedBigInteger('sender_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropForeign(['chat_id']);
            $table->dropColumn('chat_id');

            $table->foreignId('receiver_id')
                  ->after('sender_id')
                  ->nullable()
                  ->constrained('users')
                  ->onDelete('cascade');

            $table->unsignedBigInteger('sender_id')->nullable(false)->change();
        });
    }
};