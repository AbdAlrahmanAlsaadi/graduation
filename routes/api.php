<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\EquipmentController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\SpaceController;
use App\Http\Controllers\WorkItemController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');






Route::post('auth/company/login', [AuthController::class, 'companySignIn']);
Route::post('auth/internal/login', [AuthController::class, 'internalSignIn']);
Route::middleware('auth:sanctum')->post('sign-out', [AuthController::class, 'signOut']);

Route::middleware(['auth:sanctum'])->group(function () {
    Route::middleware('role:company_admin')->group(function () {
        Route::post('/internal-users', [AuthController::class, 'createInternalUser'])
            ->middleware('permission:users.create');

        Route::patch('internal-users/{userId}/toggle-status', [AuthController::class, 'toggleUserStatus'])
            ->middleware('permission:users.activate_deactivate');

        Route::delete('deleteUser/{userId}', [AuthController::class, 'deleteInternalUser']);

        Route::post('Addequipment', [EquipmentController::class, 'store']);
        Route::post('equipment/maintenance', [EquipmentController::class, 'storeMaintenance']);
        Route::delete('equipment/{equipmentId}', [EquipmentController::class, 'destroy']);
        Route::get('equipment/by-status', [EquipmentController::class, 'getByStatus']);
        Route::post('equipment/maintenance/{maintenanceId}/close', [EquipmentController::class, 'closeMaintenance']);

        Route::apiResource('projects', ProjectController::class)
            ->only(['index', 'store', 'show', 'update']);
        Route::get('projects/{project}/summary', [ProjectController::class, 'summary']);

        Route::get('projects/{project}/spaces', [SpaceController::class, 'index']);
        Route::post('projects/{project}/spaces', [SpaceController::class, 'store']);
        Route::patch('spaces/{space}', [SpaceController::class, 'update']);
        Route::delete('spaces/{space}', [SpaceController::class, 'destroy']);

        Route::get('projects/{project}/work-items', [WorkItemController::class, 'index']);
        Route::post('projects/{project}/work-items', [WorkItemController::class, 'store']);
        Route::delete('work-items/{workItem}', [WorkItemController::class, 'destroy']);
        Route::put('projects/{project}/work-items/reorder', [WorkItemController::class, 'reorder']);
    });

    Route::middleware('role:project_manager')->group(function () {
        Route::apiResource('projects', ProjectController::class)->only(['index', 'show']);
        Route::get('projects/{project}/summary', [ProjectController::class, 'summary']);

        Route::get('projects/{project}/spaces', [SpaceController::class, 'index']);
        Route::post('projects/{project}/spaces', [SpaceController::class, 'store']);
        Route::patch('spaces/{space}', [SpaceController::class, 'update']);
        Route::delete('spaces/{space}', [SpaceController::class, 'destroy']);

        Route::get('projects/{project}/work-items', [WorkItemController::class, 'index']);
        Route::post('projects/{project}/work-items', [WorkItemController::class, 'store']);
        Route::delete('work-items/{workItem}', [WorkItemController::class, 'destroy']);
        Route::put('projects/{project}/work-items/reorder', [WorkItemController::class, 'reorder']);
    });

    Route::middleware('role:assistant_engineer')->group(function () {
        Route::apiResource('projects', ProjectController::class)->only(['index', 'show']);
        Route::get('projects/{project}/spaces', [SpaceController::class, 'index']);
        Route::get('projects/{project}/work-items', [WorkItemController::class, 'index']);
    });

    Route::middleware('role:owner')->group(function () {
        Route::apiResource('projects', ProjectController::class)->only(['index', 'show']);
        Route::get('projects/{project}/spaces', [SpaceController::class, 'index']);
        Route::get('projects/{project}/work-items', [WorkItemController::class, 'index']);
    });
});

