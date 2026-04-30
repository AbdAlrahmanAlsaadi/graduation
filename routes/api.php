<?php

use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');






Route::post('auth/company/login', [AuthController::class, 'companySignIn']);
Route::post('auth/internal/login', [AuthController::class, 'internalSignIn']);
Route::middleware('auth:sanctum')->post('sign-out', [AuthController::class, 'signOut']);

Route::middleware(['auth:sanctum', 'role:company_admin'])->group(function () {
    Route::post('/internal-users', [AuthController::class, 'createInternalUser'])
        ->middleware('permission:users.create');

    Route::patch('internal-users/{userId}/toggle-status', [AuthController::class, 'toggleUserStatus'])
        ->middleware('permission:users.activate_deactivate');

    Route::delete('deleteUser/{userId}', [AuthController::class, 'deleteInternalUser']);
});
