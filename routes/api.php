<?php
use App\Http\Controllers\AlertController;
use App\Http\Controllers\FireController;
use Illuminate\Http\Request;
use App\Models\Alert;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
});


Route::post('/water-alert', [AlertController::class, 'store']);

// Route::post('/send-alert', [AlertController::class, 'store']);


Route::get('/waters', [AlertController::class, 'index']);
Route::delete('/waters/{id}', [AlertController::class, 'destroy']);


Route::get('/flames', [FireController::class, 'index']);
Route::post('/flames', [FireController::class, 'store']);

Route::get('/test', function () {
    return response()->json(['message' => 'API is working!']);
});