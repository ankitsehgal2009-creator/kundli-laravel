<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\KundliController;
use App\Http\Middleware\KundliAccess;
use App\Http\Middleware\KundliAdmin;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/setup', [AuthController::class, 'setupForm']);
    Route::post('/setup', [AuthController::class, 'setup'])->middleware('throttle:10,1');
    Route::get('/login', [AuthController::class, 'loginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');
    Route::get('/register', [AuthController::class, 'registerForm']);
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:5,1');
    Route::get('/forgot', [AuthController::class, 'forgotForm']);
    Route::post('/forgot/email', [AuthController::class, 'forgotEmail'])->middleware('throttle:5,1');
    Route::post('/forgot/questions', [AuthController::class, 'forgotQuestions'])->middleware('throttle:10,1');
    Route::post('/forgot/answer', [AuthController::class, 'forgotAnswer'])->middleware('throttle:5,1');
    Route::get('/reset-password/{token}', [AuthController::class, 'resetForm'])->name('password.reset');
    Route::post('/reset-password', [AuthController::class, 'reset'])->middleware('throttle:5,1');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/pending', [AuthController::class, 'pending']);
    Route::get('/account', [AccountController::class, 'show']);
    Route::post('/account/password', [AccountController::class, 'password']);
    Route::post('/account/questions', [AccountController::class, 'questions']);
    Route::post('/account/key', [AccountController::class, 'activate'])->middleware('throttle:10,1');

    Route::middleware(KundliAccess::class)->group(function () {
        Route::get('/', [KundliController::class, 'app']);
        Route::get('/api/clients', [KundliController::class, 'index']);
        Route::put('/api/clients/{key}', [KundliController::class, 'upsert']);
        Route::delete('/api/clients/{key}', [KundliController::class, 'destroy']);
        Route::post('/api/report-log', [KundliController::class, 'logReport'])->middleware('throttle:60,1');
    });

    Route::middleware(KundliAdmin::class)->prefix('admin')->group(function () {
        Route::get('/', [AdminController::class, 'index']);
        Route::post('/users/{user}', [AdminController::class, 'userAction']);
        Route::post('/keys', [AdminController::class, 'generate']);
        Route::post('/keys/{key}', [AdminController::class, 'keyAction']);
        Route::post('/brand', [AdminController::class, 'brand']);
        Route::get('/logs.csv', [AdminController::class, 'logsCsv']);
    });
});
