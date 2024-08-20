<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\CaisseController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\RecapController;
use App\Http\Controllers\EmployerController;


/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/
Route::post('register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout']);

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});



Route::middleware(['auth:sanctum', 'role:saisie'])->post('/rakitra', [TransactionController::class, 'store']);

Route::middleware(['auth:sanctum', 'role:saisie'])->get('/rakitra', [TransactionController::class, 'listrakitra']);

// Endpoint pour obtenir les billets initiaux
Route::middleware(['auth:sanctum', 'role:saisie'])->get('/initial-billets/{id}', [TransactionController::class, 'getInitialBillets']);

// Endpoint pour modifier les billets
Route::middleware('auth:sanctum')->post('/modification-billets/{rakitraId}', [TransactionController::class, 'modifyBillets']);

// Endpoint pour obtenir la liste des rakitra avec leurs billets
Route::middleware(['auth:sanctum', 'role:saisie'])->get('/rakitra-with-billets', [TransactionController::class, 'getRakitraWithBillets']);

Route::middleware(['auth:sanctum'])->delete('/rakitra/{rakitraId}', [TransactionController::class, 'deleteRakitra']);

Route::middleware(['auth:sanctum'])->get('/caisses', [CaisseController::class, 'index']);

Route::middleware(['auth:sanctum'])->post('/transactions/ajout-voady', [TransactionController::class, 'ajoutVoady']);

Route::middleware(['auth:sanctum'])->get('/voady', [TransactionController::class, 'getVoadyList']);

Route::middleware(['auth:sanctum'])->get('/voady/{voadyId}', [TransactionController::class, 'getVoadyDetails']);

Route::middleware(['auth:sanctum'])->delete('/voady/{voadyId}', [TransactionController::class, 'deleteVoady']);

Route::middleware(['auth:sanctum'])->put('/voady/{voadyId}', [TransactionController::class, 'update']);

Route::middleware(['auth:sanctum'])->post('/transactions/ajout-depense', [TransactionController::class, 'ajoutDepense']);

Route::middleware(['auth:sanctum'])->get('/depense', [TransactionController::class, 'getDepenseList']);

Route::middleware(['auth:sanctum'])->get('/depense/{depenseId}', [TransactionController::class, 'getDepenseDetails']);

Route::middleware(['auth:sanctum'])->put('/depense/{depenseId}', [TransactionController::class, 'updateDep']);

Route::middleware(['auth:sanctum'])->delete('/depense/{depenseId}', [TransactionController::class, 'deleteDepense']);

Route::middleware(['auth:sanctum'])->post('/logout', [AuthController::class, 'logout']);

Route::middleware(['auth:sanctum'])->get('/monthly-balance-by-caisse', [TransactionController::class, 'getMonthlyBalanceByCaisse']);

Route::middleware(['auth:sanctum', 'role:admin'])->get('/recap-weekly/{year}', [RecapController::class, 'recapWeekly']);
Route::middleware(['auth:sanctum', 'role:admin'])->get('/recap-monthly/{year}', [RecapController::class, 'recapMonthly']);

Route::middleware(['auth:sanctum', 'role:admin'])->get('/transactions/search', [TransactionController::class, 'search']);

Route::middleware(['auth:sanctum'])->post('/employer/{id}/payment', [EmployerController::class, 'makePayment']);
Route::middleware(['auth:sanctum'])->get('/employer/{id}/payments', [EmployerController::class, 'getPayments']);
Route::middleware('auth:sanctum')->get('/employers', [EmployerController::class, 'index']);
Route::middleware(['auth:sanctum'])->get('/paymentsToday', [EmployerController::class, 'getPaymentsToday']);
Route::middleware(['auth:sanctum'])->put('/payments/{id}', [EmployerController::class, 'updatePayment']);
Route::middleware(['auth:sanctum'])->delete('/payments/{id}', [EmployerController::class, 'deletePayment']);
Route::middleware(['auth:sanctum'])->get('/payment-status/{year}', [EmployerController::class, 'getPaymentStatus']);

Route::middleware(['auth:sanctum', 'role:admin'])->get('/employers/{id}', [EmployerController::class, 'show']);
Route::middleware(['auth:sanctum', 'role:admin'])->post('/employers', [EmployerController::class, 'store']);
Route::middleware(['auth:sanctum', 'role:admin'])->put('/employers/{id}', [EmployerController::class, 'update']);
Route::middleware(['auth:sanctum', 'role:admin'])->delete('/employers/{id}', [EmployerController::class, 'destroy']);
Route::middleware(['auth:sanctum'])->put('/transactions/{id}', [TransactionController::class, 'modifyTransaction']);
Route::middleware(['auth:sanctum'])->delete('/transactions/{id}', [TransactionController::class, 'destroyTransaction']);
Route::middleware(['auth:sanctum'])->post('/transactions', [TransactionController::class, 'storeTr']);









