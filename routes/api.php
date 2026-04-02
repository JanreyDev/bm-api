<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\Community\CommentController as CommunityCommentController;
use App\Http\Controllers\Api\Community\PostController as CommunityPostController;
use App\Http\Controllers\Api\Jobs\HiringPostController;
use App\Http\Controllers\Api\Jobs\JobApplicationController;
use App\Http\Controllers\Api\Jobs\JobHunterProfileController;
use App\Http\Controllers\Api\Jobs\JobHunterInvitationController;
use App\Http\Controllers\Api\Jobs\SavedJobController;
use App\Http\Controllers\Api\Market\MerchantRegistrationController;
use App\Http\Controllers\Api\LocationController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
Route::post('/resend-otp', [AuthController::class, 'resendOtp']);
Route::get('/locations', [LocationController::class, 'index']);
Route::get('/locations/address-directory', [LocationController::class, 'index']);
Route::get('/locations/directory', [LocationController::class, 'index']);

Route::middleware('auth:api')->group(function (): void {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/activation/complete', [AuthController::class, 'completeActivation']);
    Route::get('/community/posts', [CommunityPostController::class, 'index']);
    Route::post('/community/posts', [CommunityPostController::class, 'store']);
    Route::get('/community/posts/{postId}', [CommunityPostController::class, 'show']);
    Route::patch('/community/posts/{postId}', [CommunityPostController::class, 'update']);
    Route::delete('/community/posts/{postId}', [CommunityPostController::class, 'destroy']);
    Route::post('/community/posts/{postId}/likes/toggle', [CommunityPostController::class, 'toggleLike']);
    Route::post('/community/posts/{postId}/comments', [CommunityCommentController::class, 'store']);
    Route::get('/jobs/hiring-posts', [HiringPostController::class, 'index']);
    Route::post('/jobs/hiring-posts', [HiringPostController::class, 'store']);
    Route::get('/jobs/hunter-profiles', [JobHunterProfileController::class, 'index']);
    Route::post('/jobs/hunter-profiles', [JobHunterProfileController::class, 'store']);
    Route::get('/jobs/invitations', [JobHunterInvitationController::class, 'index']);
    Route::post('/jobs/invitations', [JobHunterInvitationController::class, 'store']);
    Route::get('/jobs/applications', [JobApplicationController::class, 'index']);
    Route::post('/jobs/applications', [JobApplicationController::class, 'store']);
    Route::get('/jobs/saved', [SavedJobController::class, 'index']);
    Route::post('/jobs/saved/toggle', [SavedJobController::class, 'toggle']);
    Route::get('/market/merchant-registration', [MerchantRegistrationController::class, 'show']);
    Route::post('/market/merchant-registration', [MerchantRegistrationController::class, 'store']);
});
