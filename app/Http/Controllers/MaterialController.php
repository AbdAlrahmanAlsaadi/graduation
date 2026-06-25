<?php

namespace App\Http\Controllers;

use App\Http\Requests\Material\StoreWorkItemInvoiceRequest;
use App\Http\Requests\Material\StoreMaterialRequest;
use App\Http\Requests\Material\UpdateMaterialRequest;
use App\Http\Responses\Response;
use App\Models\Material;
use App\Services\MaterialService;
use App\Http\Resources\MaterialResource;
use App\Http\Resources\WorkItemDetailResource;
use App\Http\Resources\WorkItemInvoiceDetailsResource;
use App\Http\Resources\WorkItemInvoiceItemResource;
use App\Models\Project;
use App\Models\WorkItemInvoice;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Throwable;

class MaterialController extends Controller
{
    public function __construct(private MaterialService $materialService)
    {
    }

    public function index(): JsonResponse
    {
        try {
            $materials = $this->materialService->getAll();

            return Response::success('Materials fetched.', MaterialResource::collection($materials));
        } catch (Throwable $throwable) {
            return $this->handleException($throwable);
        }
    }

    public function store(StoreMaterialRequest $request): JsonResponse
    {
        try {
            $material = $this->materialService->create($request->validated());

            return Response::success('Material created.', MaterialResource::make($material), 201);
        } catch (Throwable $throwable) {
            return $this->handleException($throwable);
        }
    }

    public function show(Material $material): JsonResponse
    {
        try {
            $material = $this->materialService->findById((int) $material->id);


            return Response::success('Material fetched.', MaterialResource::make($material));
        } catch (Throwable $throwable) {
            return $this->handleException($throwable);
        }
    }

    public function update(UpdateMaterialRequest $request, Material $material): JsonResponse
    {
        try {
            $updated = $this->materialService->update((int) $material->id, $request->validated());

            return Response::success('Material updated.', MaterialResource::make($updated));
        } catch (Throwable $throwable) {
            return $this->handleException($throwable);
        }
    }

    public function destroy(Material $material): JsonResponse
    {
        try {
            $this->materialService->delete((int) $material->id);

            return Response::success('Material deleted.');
        } catch (Throwable $throwable) {
            return $this->handleException($throwable);
        }
    }

    private function handleException(Throwable $throwable): JsonResponse
    {
        if ($throwable instanceof ValidationException) {
            $status = $throwable->status ?? 422;

            return Response::Validation('Validation failed.', $throwable->errors(), $status);
        }

        $status = (int) $throwable->getCode();
        if ($status < 400 || $status >= 600) {
            $status = 500;
        }

        return Response::error($throwable->getMessage(), $status);
    }




    public function storeInvoice(
        StoreWorkItemInvoiceRequest $request
    ) {
        try {

            $data = $this->materialService
                ->storeInvoice($request);

            return Response::success(
                $data['message'],
                [
                    'invoice' => $data['invoice'],
                ],
                $data['status']
            );
        } catch (\Throwable $throwable) {

            return Response::error(
                $throwable->getMessage(),
                500
            );
        }
    }

    public function indexInvoice($projectId)
    {
        $result = $this->materialService
            ->getProjectInvoices($projectId);

        return Response::success(
            $result,
            $result['message'],
            $result['status']
        );



}
    public function destroyinvoice($invoiceId)
    {
        $result = $this->materialService
            ->deleteInvoice($invoiceId);

        return Response::success(
            $result['invoice'],
            $result['message'],
            $result['status']
        );
    }
    public function archived($projectId)
{
$result = $this->materialService
->getArchivedInvoices($projectId);


return Response::success(
    [
        'project' => $result['project'],
        'invoices' => $result['invoices'],
    ],
    $result['message'],
    $result['status']
);


}
    public function getUnits(): JsonResponse
    {
        return Response::success(
            'Units fetched successfully.',
            $this->materialService->getMaterialUnits()
        );
    }
    public function showInvoice(
        Project $project,
        WorkItemInvoice $invoice
    ) {
        try {

            $invoice = $this->materialService
                ->showInvoice(
                    $project,
                    $invoice
                );

            return Response::success(
                'Invoice details fetched successfully.',
                new WorkItemInvoiceDetailsResource(
                    $invoice
                )
            );
        } catch (Throwable $e) {

            return Response::error(
                $e->getMessage(),
                500
            );
        }
    }}
