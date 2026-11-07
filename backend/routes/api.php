<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CertificationController;
use App\Http\Controllers\Api\ExperienceController;
use App\Http\Controllers\Api\MessageController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\SkillController;
use App\Http\Controllers\Api\SocialLinkController;
use App\Http\Controllers\Api\SiteController;
use Illuminate\Support\Facades\Route;

// Public routes - no authentication required
Route::get('/site', [SiteController::class, 'show']);
Route::get('/profile', [ProfileController::class, 'show']);
Route::get('/socials', [SocialLinkController::class, 'index']);
Route::get('/projects', [ProjectController::class, 'index']);
Route::get('/projects/{project}', [ProjectController::class, 'show']);
Route::get('/skills', [SkillController::class, 'index']);
Route::get('/experiences', [ExperienceController::class, 'index']);
Route::get('/certifications', [CertificationController::class, 'index']);

// Max 5 messages per minute per visitor, to block spam
Route::post('/messages', [MessageController::class, 'store'])->middleware('throttle:5,1');

// AI Assistant routes
Route::get('/assistant', [\App\Http\Controllers\Api\AssistantController::class, 'show']);
Route::post('/chat', [\App\Http\Controllers\Api\ChatController::class, 'store'])->middleware('throttle:10,1');

// CV/Resume routes
Route::get('/cv', [\App\Http\Controllers\Api\CvController::class, 'show']);
Route::get('/cv/download', [\App\Http\Controllers\Api\CvController::class, 'download'])->middleware('throttle:30,1');

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
        Route::patch('/certifications/reorder', [\App\Http\Controllers\Admin\CertificationController::class, 'reorder']);
        Route::apiResource('certifications', \App\Http\Controllers\Admin\CertificationController::class)->except(['show']);

        // Messages management
        Route::get('/messages', [\App\Http\Controllers\Admin\MessageController::class, 'index']);
        Route::patch('/messages/{message}/read', [\App\Http\Controllers\Admin\MessageController::class, 'markAsRead']);
        Route::delete('/messages/{message}', [\App\Http\Controllers\Admin\MessageController::class, 'destroy']);

        // Image upload
        Route::post('/uploads', [\App\Http\Controllers\Admin\UploadController::class, 'store']);

        // AI Assistant management
        Route::get('/assistant', [\App\Http\Controllers\Admin\AssistantController::class, 'show']);
        Route::put('/assistant', [\App\Http\Controllers\Admin\AssistantController::class, 'update']);
        Route::get('/assistant/usage', [\App\Http\Controllers\Admin\AssistantController::class, 'usage']);

        // CV management
        Route::get('/cv', [\App\Http\Controllers\Admin\CvController::class, 'show']);
        Route::post('/cv', [\App\Http\Controllers\Admin\CvController::class, 'store']);
        Route::patch('/cv', [\App\Http\Controllers\Admin\CvController::class, 'togglePublic']);
        Route::delete('/cv', [\App\Http\Controllers\Admin\CvController::class, 'destroy']);
        Route::get('/cv/file', [\App\Http\Controllers\Admin\CvController::class, 'preview']);
    });
});
