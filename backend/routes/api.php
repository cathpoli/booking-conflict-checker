<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\Admin\AdminBookingController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
    
    // Regular user booking routes
    Route::get('bookings/conflicts/report', [BookingController::class, 'conflictReport']);
    Route::apiResource('bookings', BookingController::class);
    
    // Admin-only routes
    Route::middleware('admin')->prefix('admin')->group(function () {
        Route::get('bookings', [AdminBookingController::class, 'index']);
        Route::get('bookings/conflicts/report', [AdminBookingController::class, 'conflictReport']);
    });
});
