<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\DiseaseController;
use App\Http\Controllers\DrugController;
use App\Http\Controllers\HospitalController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\RecordController;
use App\Http\Controllers\ReplyController;
use App\Http\Controllers\SymptomController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::get('/posts/{post}', [PostController::class, 'show']);

//retrieve all posts
Route::get('/posts', [PostController::class, 'index']);

//retrieve all hospitals
Route::get('/hospitals', [HospitalController::class, 'index']);

//diseases routes
Route::get('/diseases', [DiseaseController::class, 'index']);

//retrievee all disease categories
Route::get('/diseases/categories', [CategoryController::class, 'index']);

// Protected routes
Route::group(['middleware' => ['auth:sanctum']], function () {
    // create a new route for logout
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/user', function (Request $request) {
        return response(['user' => $request->user()], 200);
    });

    Route::get('/users', [AuthController::class, 'index']);


    //posts routes
    Route::post('/posts/store', [PostController::class, 'store']);
    Route::put('/posts/{post}/update', [PostController::class, 'update']);
    Route::delete('/posts/{post}/destroy', [PostController::class, 'destroy']);

    //comments routes
    Route::post('/posts/{post}/comments', [CommentController::class, 'store']);
    Route::delete('/comments/{comment}/destroy', [CommentController::class, 'destroy']);

    //reply routes
    Route::post('/comments/{comment}/replies', [ReplyController::class, 'store']);
    Route::delete('/replies/{reply}/destroy', [ReplyController::class, 'destroy']);

    //records
    Route::get('/records', [RecordController::class, 'index']);
    Route::get('/month-disease-data', [RecordController::class, 'monthDiseaseData']);
    Route::get('/day-disease-data', [RecordController::class, 'dayDiseaseData']);
    Route::get('/year-disease-data', [RecordController::class, 'yearDiseaseData']);
    Route::post('/records/{patient}/store', [RecordController::class, 'store']);

    // symptoms
    Route::get('/symptoms', [SymptomController::class, 'index']);

    //drugs
    Route::get('/drugs', [DrugController::class, 'index']);

    //general analytics
    Route::get('/records/analytics/general/{year}/months', [RecordController::class, 'monthsVsDiseasesPerYearSelected']);
    Route::get('/records/analytics/general/{start}/{end}/years', [RecordController::class, 'yearsVsDiseaseData']);
});
