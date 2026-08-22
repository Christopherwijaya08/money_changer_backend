<?php

use App\Http\Controllers\CustomerController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\ExchangeRateController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\TransactionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/transactions', [TransactionController::class, 'index']);
Route::post('/transactions', [TransactionController::class, 'store']);
Route::get('/transactions/{transaction}', [TransactionController::class, 'show']);

Route::get('/customers', [CustomerController::class, 'index']);
Route::post('/customers', [CustomerController::class, 'store']);
Route::get('/customers/{customer}', [CustomerController::class, 'show']);
Route::put('/customers/{customer}', [CustomerController::class, 'update']);
Route::delete('/customers/{customer}', [CustomerController::class, 'destroy']);
Route::post('/customers/{customer}/ktp-photo', [CustomerController::class, 'uploadKtpPhoto']);
Route::get('/customers/{customer}/ktp-photo', [CustomerController::class, 'ktpPhoto']);
Route::get('/customers/{customer}/transactions', [CustomerController::class, 'transactions']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/employees', [EmployeeController::class, 'index']);
    Route::post('/employees', [EmployeeController::class, 'store']);
    Route::put('/employees/{employee}', [EmployeeController::class, 'update']);
});

Route::get('/exchange-rates', [ExchangeRateController::class, 'index']);
Route::put('/exchange-rates/{currency}', [ExchangeRateController::class, 'update']);
Route::get('/exchange-rates/{currency}/history', [ExchangeRateController::class, 'history']);

Route::get('/settings/threshold', [SettingController::class, 'show']);
Route::put('/settings/threshold', [SettingController::class, 'update']);
