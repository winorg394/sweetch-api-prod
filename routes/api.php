<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Api\Auth\RegisterController;
use App\Http\Controllers\Api\Auth\LoginController;
use App\Http\Controllers\VerificationController;
use App\Http\Controllers\DocVerificationsController;
use App\Http\Controllers\Api\FinanceController;
use App\Http\Controllers\Api\Utils\UtilsController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\TransfersController;
use App\Http\Controllers\NjanguiController;
use App\Http\Controllers\Api\TontineMemberController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->group(function () {
    Route::get('profile', [ProfileController::class, 'show']);
    Route::post('profile', [ProfileController::class, 'update']);

    // Finance routes
    Route::get('/wallet/balance', [FinanceController::class, 'getBalance']);
    Route::get('/wallet/transactions', [FinanceController::class, 'getUserTransaction']);

    // Njangui (Tontine) Routes
    Route::prefix('njangui')->group(function () {
        // List all tontines for current user
        Route::get('/', [NjanguiController::class, 'index']);

        // Create new tontine
        Route::post('/', [NjanguiController::class, 'store']);
        Route::post('{tontine}/members/order', [NjanguiController::class, 'updateOrder']);

        // Get tontine details
        Route::get('/{tontine}', [NjanguiController::class, 'show']);

        // Update tontine
        Route::put('/{tontine}', [NjanguiController::class, 'update']);

        // Delete tontine
        Route::delete('/{tontine}', [NjanguiController::class, 'destroy']);

        // Get members of a tontine
        Route::get('/{tontine}/members', [NjanguiController::class, 'members']);

        // Add member to tontine
        Route::post('/{tontine}/members', [NjanguiController::class, 'addMember']);

        // Remove member from tontine
        Route::delete('/{tontine}/members/{member}', [NjanguiController::class, 'removeMember']);

        // Add contribution
        Route::post('/{tontine}/contributions', [NjanguiController::class, 'addContribution']);

        // Get contributions
        Route::get('/{tontine}/contributions', [NjanguiController::class, 'getContributions']);

        // Mark contribution as paid
        Route::post('/{tontine}/contributions/{member}/mark-paid', [NjanguiController::class, 'markPaid']);

        // Get tontine statistics
        Route::get('/{tontine}/stats', [NjanguiController::class, 'stats']);

        // Get tontine history
        Route::get('/{tontine}/history', [NjanguiController::class, 'history']);

        // Invite to tontine
        Route::post('/{tontine}/invite', [NjanguiController::class, 'invite']);

        // Accept/Reject invitation
        Route::post('/invitations/{invitation}/respond', [NjanguiController::class, 'respondToInvitation']);
    });
    Route::group(['prefix' => 'tontine-members'], function () {
        Route::get('/', [TontineMemberController::class, 'index']);
        Route::post('/update-order', [TontineMemberController::class, 'updateOrder']);
    });
});

Route::post('deposit', [\App\Http\Controllers\Api\FinanceController::class, 'deposit'])->middleware('auth:sanctum');

// Auth Routes
Route::post('register', [RegisterController::class, 'register']);
Route::post('login', [LoginController::class, 'login']);
Route::post('/check-email', [LoginController::class, 'checkEmailExists']);
Route::post('logout', [LoginController::class, 'logout'])->middleware('auth:sanctum');
Route::post('verify-email', [VerificationController::class, 'verifyEmail']);
Route::post('verify-phone', [VerificationController::class, 'verifyPhone']);
Route::post('verify-otp', [RegisterController::class, 'verifyOtp']);
Route::post('send-email-otp', [VerificationController::class, 'sendEmailOtp']);
Route::post('send-phone-otp', [VerificationController::class, 'sendPhoneOtp']);

// Document Verification Routes
Route::middleware('auth:sanctum')->group(function () {
    // ID Card verification routes
    Route::post('/documents/id-card/upload', [DocVerificationsController::class, 'uploadIdCard']);
    Route::post('/documents/id-card/verify', [DocVerificationsController::class, 'verifyIdCard']);

    // NIU verification routes
    Route::post('/documents/niu/upload', [DocVerificationsController::class, 'uploadNiu']);
    Route::post('/documents/niu/verify', [DocVerificationsController::class, 'verifyNiu']);

    // Add this route
    Route::post('/transfer', [TransfersController::class, 'transfer']);

    Route::get('/countries', [TransactionController::class, 'getCountries']);
    Route::get('/payment-operators', [TransactionController::class, 'getPaymentOperators']);
    Route::get('/payment-operators-any', [TransactionController::class, 'getAnyToAnyOperators']);
    Route::get('/payment-operators-list-mobile-transfers', [TransactionController::class, 'getPaymentOperators']);
});
/* Route::post('/calculate-charges', [TransfersController::class, 'calculateCharges']); */
Route::post('/calculate-charges', [TransactionController::class, 'calculateCharges']);
Route::post('/users/find-by-phone', [UserController::class, 'findByPhone']);

Route::get('/utils/countries-operators', [UtilsController::class, 'getCountriesAndOperators'])/* ->middleware('auth:sanctum') */;
