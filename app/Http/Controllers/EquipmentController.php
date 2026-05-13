<?php

namespace App\Http\Controllers;

use App\Http\Requests\CloseEquipmentMaintenanceRequest;
use App\Http\Requests\FilterEquipmentByStatusRequest;
use App\Http\Requests\FinishEquipmentBookingRequest;
use App\Http\Requests\StoreEquipmentBookingRequest;
use App\Http\Requests\StoreEquipmentRequest;
use App\Http\Responses\Response;
use App\Services\EquipmentService;
use App\Http\Requests\StoreEquipmentMaintenanceRequest;
use Throwable;

class EquipmentController extends Controller
{
    private EquipmentService $equipmentService;

    public function __construct(EquipmentService $equipmentService)
    {
        $this->equipmentService = $equipmentService;
    }

    public function store(StoreEquipmentRequest $request)
    {
        try {
            $data = $this->equipmentService->store($request);
            return Response::success($data['message'], $data['equipment'], (int) $data['status']);
        } catch (Throwable $throwable) {
            $code = is_int($throwable->getCode()) && $throwable->getCode() > 0
                ? $throwable->getCode()
                : 500;

            return Response::error($throwable->getMessage(), $code);
        }
    }

    public function destroy($equipmentId)
    {
        try {
            $data = $this->equipmentService->delete($equipmentId);
            return Response::success($data['message'], $data['equipment'], (int) $data['status']);
        } catch (Throwable $throwable) {
            $code = is_int($throwable->getCode()) && $throwable->getCode() > 0
                ? $throwable->getCode()
                : 500;

            return Response::error($throwable->getMessage(), $code);
        }
    }

    public function storeMaintenance(StoreEquipmentMaintenanceRequest $request)
    {
        try {
            $data = $this->equipmentService->storeMaintenance($request);
            return Response::success($data['message'], $data['maintenance'], (int) $data['status']);
        } catch (Throwable $throwable) {
            $code = is_int($throwable->getCode()) && $throwable->getCode() > 0
                ? $throwable->getCode()
                : 500;

            return Response::error($throwable->getMessage(), $code);
        }
    }


    public function getByStatus(FilterEquipmentByStatusRequest $request)
    {
        try {
            $data = $this->equipmentService->getByStatus($request);
            return Response::success($data['message'], $data['equipment'], (int) $data['status']);
        } catch (Throwable $throwable) {
            $code = is_int($throwable->getCode()) && $throwable->getCode() > 0
                ? $throwable->getCode()
                : 500;

            return Response::error($throwable->getMessage(), $code);
        }
    }

    public function closeMaintenance(CloseEquipmentMaintenanceRequest $request, $maintenanceId)
    {
        try {
            $data = $this->equipmentService->closeMaintenance($maintenanceId, $request);
            return Response::success($data['message'], $data['maintenance'], (int) $data['status']);
        } catch (Throwable $throwable) {
            $code = is_int($throwable->getCode()) && $throwable->getCode() > 0
                ? $throwable->getCode()
                : 500;

            return Response::error($throwable->getMessage(), $code);
        }
    }

    public function storebook(StoreEquipmentBookingRequest $request)
    {
        try {

            $data = $this->equipmentService
                ->storebook($request);

            return Response::success(
                $data['message'],
                [
                    'booking' => $data['booking'],
                ],
                (int) $data['status']
            );
        } catch (Throwable $throwable) {

            $code = is_int($throwable->getCode())
                && $throwable->getCode() > 0
                ? $throwable->getCode()
                : 500;

            return Response::error(
                $throwable->getMessage(),
                $code
            );
        }
    }

    public function finishBooking(
        FinishEquipmentBookingRequest $request,
        $bookingId
    ) {
        try {

            $data = $this->equipmentService
                ->finishBooking($request, $bookingId);

            return Response::success(
                $data['message'],
                [
                    'booking' => $data['booking'],
                ],
                (int) $data['status']
            );
        } catch (Throwable $throwable) {

            $code = is_int($throwable->getCode())
                && $throwable->getCode() > 0
                ? $throwable->getCode()
                : 500;

            return Response::error(
                $throwable->getMessage(),
                $code
            );
        }
    }
}

