<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DepartmentController;
use App\Http\Controllers\Api\MessageController;
use App\Http\Controllers\Api\PostController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::post('/register', [AuthController::class, 'register']);


Route::middleware(['web'])->group(function() {
    Route::post('/login', [AuthController::class, 'login']);
});


/*
|--------------------------------------------------------------------------
| Rotas Protegidas pelo Sanctum
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {
    // Retorna os dados do usuário autenticado no sistema
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // Departamentos
    Route::get('/departments', [DepartmentController::class, 'index']);

    // Posts & Interações
    Route::get('/posts', [PostController::class, 'index']);
    Route::post('/posts', [PostController::class, 'store']); // Para criar posts
    Route::post('/posts/{post}/like', [PostController::class, 'toggleLike']);
    Route::delete('/posts/{post}', [PostController::class, 'destroyPost']);
    Route::put('/posts/{post}', [PostController::class, 'updatePost']);

    Route::post('/posts/{post}/comments', [PostController::class, 'storeComment']);
    Route::get('/posts/{post}/comments', [PostController::class, 'getPostComments']);
    Route::delete('/comments/{comment}', [PostController::class, 'destroyComment']);

    // Chat
    Route::get('/messages', [MessageController::class, 'index']);
    Route::post('/messages/{user}/room', [MessageController::class, 'ensureRoom']);
    Route::get('/messages/{user}', [MessageController::class, 'show']);
    Route::post('/messages', [MessageController::class, 'store']);

    // Grupos & Salas
    Route::get('/chats/{chat}/messages', [MessageController::class, 'showGroup']);
    Route::post('/chats/groups', [MessageController::class, 'storeGroup']);
    Route::post('/chats/{chat}/messages', [MessageController::class, 'sendGroupMessage']);
    Route::get('/chats/{chat}/members', [MessageController::class, 'groupMembers']);
    Route::post('/chats/{chat}/read', [MessageController::class, 'markAsRead']);


    Route::put('/chats/{chat}/groups', [MessageController::class, 'updateGroup']);
    Route::post('/chats/{chat}/groups', [MessageController::class, 'updateGroup']);
    Route::delete('/chats/{chat}/groups', [MessageController::class, 'deleteGroup']);

    // Logout
    Route::post('/logout', [AuthController::class, 'logout']);
});