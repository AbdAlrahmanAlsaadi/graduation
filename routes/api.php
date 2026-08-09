<?php

use App\Http\Controllers\AIConstructionController;
use App\Http\Controllers\AIImageController;
use App\Http\Controllers\AIProjectAnalysisController;
use App\Http\Controllers\AiVisualizationCommentController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\ContractController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\EquipmentController;
use App\Http\Controllers\ImageGenerationController;
use App\Http\Controllers\MaterialController;
use App\Http\Controllers\OwnerProjectController;
use App\Http\Controllers\ProjectEngineerController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\SpaceController;
use App\Http\Controllers\WeatherController;
use App\Http\Controllers\WorkItemController;
use App\Http\Controllers\WorkItemMaterialController;
use App\Http\Controllers\WorkItemProgressController;
use App\Http\Controllers\ProgressUpdateRequestController;
use App\Http\Controllers\DurationExtensionController;
use App\Http\Controllers\ProjectImageController;
use App\Http\Controllers\ProjectReviewController;
use App\Http\Controllers\WorkshopCostCalculationController;
use App\Http\Controllers\ProjectMaterialEstimationController;
use App\Http\Controllers\ProjectWorkshopEstimationController;
use App\Http\Controllers\ProjectCostEstimationController;
use App\Http\Controllers\ReturnInvoiceController;
use App\Models\User;
use App\Services\Notification\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Kreait\Firebase\Contract\Messaging;

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
        Route::get('/projects/{project}/contracts',[DocumentController::class, 'getProjectContracts']);
    });
Route::middleware(['auth:sanctum'])->group(function () {
    Route::middleware('role:company_admin|project_manager|assistant|project_owner')->group(function () {
        Route::apiResource('projects', ProjectController::class)->only(['index', 'show']);


        Route::get('projects/{project}/spaces', [SpaceController::class, 'index']);
        Route::get('projects/{project}/spaces/ceramic', [SpaceController::class, 'ceramicSpaces']);
        Route::get('projects/{project}/spaces/gypsum', [SpaceController::class, 'gypsumSpaces']);
        Route::get('projects/{project}/spaces/sanitary', [SpaceController::class, 'sanitarySpaces']);
        Route::get('projects/{project}/progress', [WorkItemProgressController::class, 'projectProgress']);
        Route::get('projects/{project}/work-items/{workItem}/spaces-progress', [WorkItemProgressController::class, 'getSpacesProgress']);

        //Route::get('spaces/{spaceId}', [SpaceController::class, 'show']);
        Route::apiResource('projects.work-items', WorkItemController::class)->only(['index']);
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
        Route::get('equipment/{id}',[EquipmentController::class, 'show']
        );
        Route::apiResource('projects', ProjectController::class)
            ->only(['store', 'update', 'destroy']);
    });

    Route::middleware('role:company_admin|project_manager')->group(function () {
        Route::post('/projects/{project}/work-items/{workItem}/progress/{spaceId}',[WorkItemProgressController::class, 'updateRoom']);
        Route::post('projects/{project}/work-items/{workItem}/progress', [WorkItemProgressController::class, 'update']);
    });
    // ── Progress Update Requests (Approval Workflow) ────────────────────

    // Assistant submits progress update requests
    Route::middleware('role:assistant')->group(function () {
        Route::post('projects/{project}/work-items/{workItem}/progress-requests', [ProgressUpdateRequestController::class, 'store']);
        Route::post('projects/{project}/work-items/{workItem}/progress-requests/room/{spaceId}', [ProgressUpdateRequestController::class, 'storeRoom']);
    });

    // View progress update requests (all project roles)
    Route::middleware('role:company_admin|project_manager|assistant')->group(function () {
        Route::get('projects/{project}/work-items/{workItem}/progress-requests', [ProgressUpdateRequestController::class, 'index']);
        Route::get('progress-requests', [ProgressUpdateRequestController::class, 'all']);
        Route::get('progress-requests/{progressUpdateRequest}', [ProgressUpdateRequestController::class, 'show']);
        Route::get('my-progress-requests', [ProgressUpdateRequestController::class, 'getUserProgressRequests']);
        Route::get('projects/{project}/progress-requests', [ProgressUpdateRequestController::class, 'indexForProject']);
    });

    // Engineer approves or rejects
    Route::middleware('role:company_admin|project_manager')->group(function () {
        Route::post('progress-requests/{progressUpdateRequest}/approve', [ProgressUpdateRequestController::class, 'approve']);
        Route::post('progress-requests/{progressUpdateRequest}/reject', [ProgressUpdateRequestController::class, 'reject']);
    });

    // ── Duration Extension Requests ────────────────────────────────────

    // Assistant submits duration extension requests
    Route::middleware('role:assistant')->group(function () {
        Route::post('projects/{project}/work-items/{workItem}/duration-extensions', [DurationExtensionController::class, 'store']);
    });

    // View duration extension requests (all project roles)
    Route::middleware('role:company_admin|project_manager|assistant')->group(function () {
        Route::get('projects/{project}/work-items/{workItem}/duration-extensions', [DurationExtensionController::class, 'index']);
        Route::get('projects/{project}/duration-extensions', [DurationExtensionController::class, 'index']);
        Route::get('duration-extensions', [DurationExtensionController::class, 'all']);
    });

    // Engineer approves or rejects duration extension
    Route::middleware('role:company_admin|project_manager')->group(function () {
        Route::post('duration-extensions/{durationExtensionRequest}/approve', [DurationExtensionController::class, 'approve']);
        Route::post('duration-extensions/{durationExtensionRequest}/reject', [DurationExtensionController::class, 'reject']);
    });

    Route::middleware('role:company_admin|project_manager|assistant')->group(function () {
        Route::apiResource('materials', MaterialController::class);
        Route::get('engineer/projects', [ProjectController::class, 'listEngineerProjects']);

        Route::post('materials/{material}', [MaterialController::class, 'update']);

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
        Route::post('projects/{project}/work-items/{workItem}/details', [WorkItemController::class, 'updateDetails']);
        Route::post('projects/{project}/work-items/{workItem}/start', [WorkItemController::class, 'start']);
        Route::post('projects/{project}/work-items/{workItem}/complete', [WorkItemController::class, 'complete']);
        // Engineers payload: { user_id, role, assigned_at? }
        Route::get('projects/{project}/engineers', [ProjectEngineerController::class, 'index']);
        Route::post('projects/{project}/engineers', [ProjectEngineerController::class, 'store']);
        Route::delete('projects/{project}/engineers/{assignment}', [ProjectEngineerController::class, 'destroy']);

        Route::get('work-items/{workItemName}/materials', [WorkItemMaterialController::class, 'index'])->where('workItemName', '.*');
        Route::post('work-items/materials/attach', [WorkItemMaterialController::class, 'store']);
        //Route::post('work-items/{workItemName}/materials/{material}', [WorkItemMaterialController::class, 'update']);
        Route::delete('work-items/{workItemName}/materials/{material}', [WorkItemMaterialController::class, 'destroy']);
        //Route::post('work-items/{workItemName}/materials/sync', [WorkItemMaterialController::class, 'sync']);

        Route::get('work-item-details/pending',  [WorkItemController::class, 'pendingUpdates'] );

            Route::get('projects/{project}/work-items/list',[WorkItemController::class, 'workItems']
            );
            Route::get('/work-items/system',[WorkItemController::class, 'getSystemWorkItems'] );

        Route::post('work-item-invoices',  [MaterialController::class, 'storeInvoice']
        );

        Route::get('projects/{projectId}/invoices',[MaterialController::class, 'indexInvoice']
        );
        Route::delete('invoices/{invoiceId}', [MaterialController::class, 'destroyinvoice']
        );

        Route::get('/projects/{projectId}/archived-invoices', [MaterialController::class, 'archived']
);
            Route::get('/projects/{project}/invoices/{invoice}',  [MaterialController::class, 'showInvoice']
            );

            Route::get('/units',[MaterialController::class, 'getUnits']);

            Route::post(
                'projects/{projectId}/plaster',
                [WorkshopCostCalculationController::class, 'plaster']
            );

                    Route::post(
                'projects/{projectId}/paint',
                        [WorkshopCostCalculationController::class, 'paint']
                    );

                    Route::post(
                'projects/{projectId}/tile',
                        [WorkshopCostCalculationController::class, 'tile']
                    );

                    Route::get(
                        'projects/{project}/estimate-materials',
                        [ProjectMaterialEstimationController::class, 'estimate']
                    );

                    Route::get(
                        'projects/{project}/estimate-workshops',
                        [ProjectWorkshopEstimationController::class, 'estimate']
                    );

                    Route::get(
                        'projects/{project}/estimate-total-cost',
                        [ProjectCostEstimationController::class, 'estimateTotal']
                    );

                    Route::get(
                        'projects/{project}/compare-cost',
                        [ProjectCostEstimationController::class, 'compareCost']
                    );
                });

    Route::post('work-items/{id}/comments', [CommentController::class, 'store'])
        ->middleware(['auth:sanctum']);
    Route::get('work-items/{id}/comments', [CommentController::class, 'index'])
        ->middleware(['auth:sanctum']);

        Route::get(
            'notifications',
            [AuthController::class, 'myNotifications']
        )->middleware(['auth:sanctum']);

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

Route::middleware(['auth:sanctum','role:project_owner'])->group(function () {
    Route::get('owner/projects',[OwnerProjectController::class, 'myProjects']);
    Route::get('owner/projects/{project}',[OwnerProjectController::class, 'show']);
    Route::get('owner/projects/{project}/spaces',[OwnerProjectController::class, 'spaces']);
    Route::get('owner/projects/{project}/work-items',[OwnerProjectController::class, 'workItems']);

    Route::get('owner/account', [AuthController::class, 'account']
    );
});

Route::middleware('auth:sanctum')->post('fcm-token', [AuthController::class, 'Fcm']);

Route::middleware(['auth:sanctum'])->group(function () {

    Route::get('/assistant/account', [AuthController::class, 'profile']
    );
}   );



Route::post('/ai-inspect-job', [App\Http\Controllers\AiInspectionController::class, 'inspect2']);
Route::post('/ai-inspect-job2', [App\Http\Controllers\AiInspectionController::class, 'inspect']);

Route::post('ai-visualization', [App\Http\Controllers\AiVisualizationController::class, 'generate']);


Route::prefix('ai')->group(function () {
    Route::post('chat', [AIProjectAnalysisController::class, 'chat']);
    Route::get('/conversations', [AIProjectAnalysisController::class, 'index']);
    Route::get('/conversations/{id}', [AIProjectAnalysisController::class, 'show']);
    Route::delete('/conversations/{id}', [AIProjectAnalysisController::class, 'destroy']);
    Route::post('new', [AIProjectAnalysisController::class, 'newConversation']);
    Route::delete('clear', [AIProjectAnalysisController::class, 'clearMemory']);
});

Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('projects/{project}/work-items/{workItem}/expenses', [App\Http\Controllers\WorkItemExpenseController::class, 'store']);
    Route::get('projects/{project}/work-items/{workItem}/expenses', [App\Http\Controllers\WorkItemExpenseController::class, 'index']);
});







Route::middleware(['auth:sanctum'])->group(function (){

    Route::post('storeimage', [ProjectImageController::class, 'store']);

    Route::delete('deleteimage/{projectImage}', [ProjectImageController::class, 'destroy']);

    Route::get('/project-images/project/{project}', [ProjectImageController::class, 'index']);


    Route::get( 'project-images/{projectid}/visualizations',[ProjectImageController::class, 'index2']
    );
    Route::delete('ai-visualizations/{id}',[ProjectImageController::class, 'delete']);
});

Route::middleware(['auth:sanctum'])->group(
    function () {
      Route::post('ai-visualizations/{aiVisualization}/comments',[AiVisualizationCommentController::class, 'store']);

        Route::get('ai-visualizations/{aiVisualization}/comments',[AiVisualizationCommentController::class, 'index'] );
        Route::delete('ai-visualization-comments/{id}', [AiVisualizationCommentController::class, 'destroy']);
    });




Route::middleware('auth:sanctum')->group(function () {

    Route::post(
        'projects/{project}/review', [ProjectReviewController::class, 'store']);
    Route::get(
        '/project-reviews',[ProjectReviewController::class, 'statistics']);
         Route::get('/ongoing-projects', [ProjectController::class, 'getOngoingProjects']);
       Route::get('/delivery-rate', [ProjectController::class, 'getDeliveryRate']);
});





Route::middleware('auth:sanctum')->group(function () {




        Route::get('/projects/{projectId}/return-invoices', [ReturnInvoiceController::class, 'index']);
        Route::post('/projects/{projectId}/return-invoices', [ReturnInvoiceController::class, 'store']);
        Route::delete('/{id}', [ReturnInvoiceController::class, 'destroy']);
    });
