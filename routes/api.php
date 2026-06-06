<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\ContractController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\EquipmentController;
use App\Http\Controllers\MaterialController;
use App\Http\Controllers\ProjectEngineerController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\SpaceController;
use App\Http\Controllers\WeatherController;
use App\Http\Controllers\WorkItemController;
use App\Http\Controllers\WorkItemMaterialController;
use App\Http\Controllers\WorkItemProgressController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');






Route::post('auth/company/login', [AuthController::class, 'companySignIn']);
Route::post('auth/internal/login', [AuthController::class, 'internalSignIn']);
Route::middleware('auth:sanctum')->post('sign-out', [AuthController::class, 'signOut']);

Route::middleware(['auth:sanctum'])->group(
    function () {
        Route::post('document', [DocumentController::class, 'store']);
        Route::post('document/{documentId}/versions', [DocumentController::class, 'addVersion']);
        Route::get('documents/{documentId}', [DocumentController::class, 'show']);
        Route::get('documents/versions/{versionId}/download', [DocumentController::class, 'downloadVersion']);
        Route::get('/projects/{projectId}/weather/today', [WeatherController::class, 'getTodayByProject']);
        Route::get('projects/{projectId}/weather/by-date',[WeatherController::class, 'getByDate']
        );
        Route::get('projects/{id}/documents',[DocumentController::class, 'getProjectDocuments']);
    });
Route::middleware(['auth:sanctum'])->group(function () {
    Route::middleware('role:company_admin|project_manager|assistant|project_owner')->group(function () {
        Route::apiResource('projects', ProjectController::class)->only(['index', 'show']);
        Route::get('projects/{project}/spaces', [SpaceController::class, 'index']);
        Route::get('projects/{project}/spaces/ceramic', [SpaceController::class, 'ceramicSpaces']);
        Route::get('projects/{project}/spaces/gypsum', [SpaceController::class, 'gypsumSpaces']);
        Route::get('projects/{project}/spaces/sanitary', [SpaceController::class, 'sanitarySpaces']);
         Route::get('projects/{project}/progress', [WorkItemProgressController::class, 'projectProgress']);
   
        //Route::get('spaces/{spaceId}', [SpaceController::class, 'show']);
        Route::apiResource('projects.work-items', WorkItemController::class)->only(['index']);
    });

    Route::middleware('role:company_admin|project_manager|assistant')->group(function () {
        
        Route::post('/projects/{project}/work-items/{workItem}/progress/{spaceId}',[WorkItemProgressController::class, 'updateRoom']);
        Route::post('projects/{project}/work-items/{workItem}/progress', [WorkItemProgressController::class, 'update']);
        Route::get('projects/{project}/progress', [WorkItemProgressController::class, 'projectProgress']);
    });

    Route::middleware('role:company_admin')->group(function () {
        Route::post('/internal-users', [AuthController::class, 'createInternalUser'])
            ->middleware('permission:users.create');

        Route::patch('internal-users/{userId}/toggle-status', [AuthController::class, 'toggleUserStatus'])
            ->middleware('permission:users.activate_deactivate');

        Route::delete('deleteUser/{userId}', [AuthController::class, 'deleteInternalUser']);
            Route::get('users/by-role', [AuthController::class, 'getUsersByRole']);

            Route::post('users/{id}/reset-password', [AuthController::class, 'resetPassword']);



        Route::post('contracts', [ContractController::class, 'store']);

        Route::get('contracts/{id}/export-pdf', [ContractController::class, 'exportPdf']);



        Route::post('Addequipment', [EquipmentController::class, 'store']);
        Route::post('equipment/maintenance', [EquipmentController::class, 'storeMaintenance']);
        Route::delete('equipment/{equipmentId}', [EquipmentController::class, 'destroy']);
        Route::get('equipment/by-status', [EquipmentController::class, 'getByStatus']);
        Route::post('equipment/maintenance/{maintenanceId}/close', [EquipmentController::class, 'closeMaintenance']);

        Route::apiResource('projects', ProjectController::class)
            ->only(['store', 'update', 'destroy']);
    });

    Route::middleware('role:company_admin|project_manager')->group(function () {
        Route::apiResource('materials', MaterialController::class);

        Route::get('projects/{project}/summary', [ProjectController::class, 'summary']);
        Route::post('projects/{project}/start', [ProjectController::class, 'start']);
        Route::post('projects/{project}/complete', [ProjectController::class, 'complete']);
        Route::post('projects/{project}/spaces', [SpaceController::class, 'store']);
        Route::put('spaces/{space}', [SpaceController::class, 'update']);
        Route::delete('spaces/{space}', [SpaceController::class, 'destroy']);
        Route::apiResource('projects.work-items', WorkItemController::class)
            ->shallow()
            ->only(['store', 'destroy']);
        Route::put('projects/{project}/work-items/reorder', [WorkItemController::class, 'reorder']);
        Route::post('projects/{project}/work-items/{workItem}', [WorkItemController::class, 'update']);
        Route::put('projects/{project}/work-items/{workItem}/details', [WorkItemController::class, 'updateDetails']);
        Route::post('projects/{project}/work-items/{workItem}/start', [WorkItemController::class, 'start']);
        Route::post('projects/{project}/work-items/{workItem}/complete', [WorkItemController::class, 'complete']);
        // Engineers payload: { user_id, role, assigned_at? }
        Route::get('projects/{project}/engineers', [ProjectEngineerController::class, 'index']);
        Route::post('projects/{project}/engineers', [ProjectEngineerController::class, 'store']);
        Route::delete('projects/{project}/engineers/{assignment}', [ProjectEngineerController::class, 'destroy']);
        
        Route::post('/projects/{project}/work-items/{workItem}/progress/{spaceId}',[WorkItemProgressController::class, 'updateRoom']);
        Route::post('projects/{project}/work-items/{workItem}/progress', [WorkItemProgressController::class, 'update']);
        Route::get('projects/{project}/progress', [WorkItemProgressController::class, 'projectProgress']);

        Route::get('work-items/{workItem}/materials', [WorkItemMaterialController::class, 'index']);
        Route::post('work-items/{workItem}/materials', [WorkItemMaterialController::class, 'store']);
        Route::put('work-items/{workItem}/materials/{material}', [WorkItemMaterialController::class, 'update']);
        Route::delete('work-items/{workItem}/materials/{material}', [WorkItemMaterialController::class, 'destroy']);
        //Route::post('work-items/{workItem}/materials/sync', [WorkItemMaterialController::class, 'sync']);
    });



    Route::post('work-items/{id}/comments', [CommentController::class, 'store'])
        ->middleware(['auth:sanctum']);
    Route::get('work-items/{id}/comments', [CommentController::class, 'index'])
        ->middleware(['auth:sanctum']);

    Route::post(
        'equipment-bookings', [EquipmentController::class, 'storebook']
    )->middleware(['auth:sanctum']);

    Route::post(
        'equipment-bookings/{id}/finish',
        [EquipmentController::class, 'finishBooking']
    )->middleware('auth:sanctum');



    Route::get('equipment/search', [EquipmentController::class, 'search'])
        ->middleware(['auth:sanctum']);

    Route::get('users/search', [AuthController::class, 'search'])
        ->middleware(['auth:sanctum']);

    Route::get('project/search', [ProjectController::class, 'search'])
        ->middleware(['auth:sanctum']);
});


Route::get( 'users/{userId}/statistics',[AuthController::class, 'statistics'])->middleware(['auth:sanctum','role:company_admin',]);

