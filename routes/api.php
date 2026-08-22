<?php

use App\Http\Controllers\BranchController;
use App\Http\Controllers\CashBalanceController;
use App\Http\Controllers\CashDepositController;
use App\Http\Controllers\CashReconciliationController;
use App\Http\Controllers\CurrencyController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\ExchangeRateController;
use App\Http\Controllers\ReportController;
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

Route::get('/branches', [BranchController::class, 'index']);
Route::post('/branches', [BranchController::class, 'store']);
Route::get('/branches/{branch}', [BranchController::class, 'show']);
Route::put('/branches/{branch}', [BranchController::class, 'update']);
Route::delete('/branches/{branch}', [BranchController::class, 'destroy']);

Route::get('/currencies', [CurrencyController::class, 'index']);
Route::post('/currencies', [CurrencyController::class, 'store']);
Route::get('/currencies/{currency}', [CurrencyController::class, 'show']);
Route::put('/currencies/{currency}', [CurrencyController::class, 'update']);
Route::delete('/currencies/{currency}', [CurrencyController::class, 'destroy']);

Route::get('/cash-deposits', [CashDepositController::class, 'index']);
Route::post('/cash-deposits', [CashDepositController::class, 'store']);

Route::get('/cash-balances', [CashBalanceController::class, 'index']);

Route::get('/cash-reconciliations', [CashReconciliationController::class, 'index']);
Route::post('/cash-reconciliations', [CashReconciliationController::class, 'store']);

Route::get('/reports/profit-loss', [ReportController::class, 'profitLoss']);
Route::get('/reports/profit-loss/export', [ReportController::class, 'profitLossExport']);
Route::get('/reports/employee-performance', [ReportController::class, 'employeePerformance']);
Route::get('/reports/employee-performance/export', [ReportController::class, 'employeePerformanceExport']);

Route::get('/dashboard/summary', [DashboardController::class, 'summary']);
