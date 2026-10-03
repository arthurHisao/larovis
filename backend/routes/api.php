<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PostController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


// Rota com autenticação
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


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

    // Posts & Interações
    Route::get('/posts', [PostController::class, 'index']);
    Route::post('/posts', [PostController::class, 'store']); // Para criar posts
    Route::post('/posts/{post}/like', [PostController::class, 'toggleLike']);
    Route::post('/posts/{post}/comments', [PostController::class, 'storeComment']);
    Route::get('/posts/{post}/comments', [PostController::class, 'getPostComments']);
    Route::delete('/comments/{comment}', [PostController::class, 'destroyComment']);
    // Logout
    Route::post('/logout', [AuthController::class, 'logout']);
});