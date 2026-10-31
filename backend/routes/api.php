<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ExperienceController;
use App\Http\Controllers\Api\MessageController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\SkillController;
use App\Http\Controllers\Api\SocialLinkController;
use Illuminate\Support\Facades\Route;

// Public routes - no authentication required
Route::get('/profile', [ProfileController::class, 'show']);
Route::get('/socials', [SocialLinkController::class, 'index']);
Route::get('/projects', [ProjectController::class, 'index']);
Route::get('/projects/{project}', [ProjectController::class, 'show']);
Route::get('/skills', [SkillController::class, 'index']);
Route::get('/experiences', [ExperienceController::class, 'index']);

// Max 5 messages per minute per visitor, to block spam
Route::post('/messages', [MessageController::class, 'store'])->middleware('throttle:5,1');

// Authentication routes (throttled to prevent brute force)
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');

// Protected routes - require authentication
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // Admin routes - prefix with "admin"
    Route::prefix('admin')->group(function () {
        // Profile management (single profile)
        Route::get('/profile', [\App\Http\Controllers\Admin\ProfileController::class, 'show']);
        Route::put('/profile', [\App\Http\Controllers\Admin\ProfileController::class, 'update']);

        // Resource routes for CRUD operations
        Route::apiResource('socials', \App\Http\Controllers\Admin\SocialLinkController::class)->except(['show']);
        Route::apiResource('projects', \App\Http\Controllers\Admin\ProjectController::class)->except(['show']);
        Route::apiResource('skills', \App\Http\Controllers\Admin\SkillController::class)->except(['show']);
        Route::apiResource('experiences', \App\Http\Controllers\Admin\ExperienceController::class)->except(['show']);

        // Messages management
        Route::get('/messages', [\App\Http\Controllers\Admin\MessageController::class, 'index']);
        Route::patch('/messages/{message}/read', [\App\Http\Controllers\Admin\MessageController::class, 'markAsRead']);
        Route::delete('/messages/{message}', [\App\Http\Controllers\Admin\MessageController::class, 'destroy']);

        // Image upload
        Route::post('/uploads', [\App\Http\Controllers\Admin\UploadController::class, 'store']);
    });
});
