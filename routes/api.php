<?php

use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');






Route::post('auth/company/login', [AuthController::class, 'companySignIn']);
Route::post('auth/internal/login', [AuthController::class, 'internalSignIn']);
Route::middleware(['auth:sanctum', 'role:company_admin'])->group(function () {
    Route::get('/admin-dashboard', function () {
        return response()->json(['message' => 'Welcome Company Admin']);
    });
});
