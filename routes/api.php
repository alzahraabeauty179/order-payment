<?php
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\OrderController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:api')->group(function () {
    Route::prefix('orders')->group(function () {
        Route::post('/', [OrderController::class, 'makeOrder']);
        Route::get('/{order}', [OrderController::class, 'show']);
    });
});