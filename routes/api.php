<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\PostController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Protected routes
Route::group(['middleware' => ['auth:sanctum']], function() {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', function(Request $request) {
        return response(['user'=>$request->user()], 200);
    });


    //posts routes
Route::post('/posts', [PostController::class, 'index']);
Route::post('/post/{post}', [PostController::class, 'show']);
Route::post('/post/create', [PostController::class, 'store']);
Route::post('/posts/{post}/update', [PostController::class, 'update']);
Route::post('/posts/{post}/destroy', [PostController::class, 'destroy']);

});
