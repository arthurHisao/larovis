<?php

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\DB;

// Broadcast::channel('chat.{id}', function ($user, $id) {
//     return (int) $user->id === (int) $id;
// });
Broadcast::channel('chat.{chatId}', function ($user, $chatId) {
    return DB::table('chat_user')
        ->where('chat_id', $chatId)
        ->where('user_id', $user->id)
        ->exists();
});


Broadcast::channel('online-users', function ($user) {
    return [
        'id'     => $user->id,
        'name'   => $user->name,
        'avatar' => $user->avatar,   
        // 'role' => $department->name
    ];
});