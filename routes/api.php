<?php

use App\Http\Controllers\AlertController;
use App\Http\Controllers\FireController;
use App\Http\Controllers\GasController;
use Illuminate\Http\Request;
use App\Models\Alert;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\WaterController;

Route::post('/signup', [AuthController::class, 'register']);
Route::post('/signin', [AuthController::class, 'login']);
Route::get('/users', [AuthController::class, 'index']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
});


Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
});


Route::post('/waters', [WaterController::class, 'store']);
Route::get('/waters', [WaterController::class, 'index']);
Route::delete('/waters/{id}', [WaterController::class, 'destroy']);


Route::get('/flames', [FireController::class, 'index']);
Route::post('/flames', [FireController::class, 'store']);
Route::delete('flames/{id}', [FireController::class, 'destroy']);

Route::get('/gas', [GasController::class, 'index']);
Route::post('/gas', [GasController::class, 'store']);
Route::delete('/gas/{id}', [GasController::class, 'destroy']);


Route::get('/test', function () {
    return response()->json(['message' => 'API is working!']);
});
