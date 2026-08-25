<?php

use App\Http\Controllers\Api\V1\Auth\LoginController;
use App\Http\Controllers\Api\V1\Auth\LogoutController;
use App\Http\Controllers\Api\V1\Auth\MeController;
use App\Http\Controllers\Api\V1\Auth\RegisterStudentController;
use App\Http\Controllers\Api\V1\Auth\RegisterTeacherController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::prefix('auth')->group(function (): void {
        Route::post('register/student', RegisterStudentController::class);
        Route::post('register/teacher', RegisterTeacherController::class);
        Route::post('login', LoginController::class);

        Route::middleware('auth:sanctum')->group(function (): void {
            Route::post('logout', LogoutController::class);
        });
    });

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::get('me', MeController::class);
    });
});
