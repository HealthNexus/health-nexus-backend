<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\PostController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::get('/posts/{post}', [PostController::class, 'show']);

//retrieve all posts
Route::get('/posts', [PostController::class, 'index']);

// Protected routes
Route::group(['middleware' => ['auth:sanctum']], function() {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', function(Request $request) {
        return response(['user'=>$request->user()], 200);
    });


    //posts routes
Route::post('/posts/create', [PostController::class, 'store']);
Route::put('/posts/{post}/update', [PostController::class, 'update']);
Route::delete('/posts/{post}/destroy', [PostController::class, 'destroy']);

});
