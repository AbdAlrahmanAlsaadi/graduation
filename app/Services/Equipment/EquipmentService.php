<?php

namespace App\Services\Equipment;

use App\Models\Equipment;
use App\Models\EquipmentBooking;
use App\Models\EquipmentMaintenance;
use App\Models\WorkItem;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class EquipmentService
{
    public function store($request): array
    {
        $request->validated();

        $identifierNo = $this->generateIdentifierNo();

        $equipment = Equipment::query()->create([

            'name' => $request->name,

            'type' => $request->type,

            'identifier_no' => $identifierNo,

            'status' => $request->status ?? 'Available',
        ]);
        return [
            'message' => 'Equipment created successfully.',
            'equipment' => $equipment,
            'status' => 201,
        ];
    }

    private function generateIdentifierNo(): string
    {
        do {
            $number = 'EQ-' . str_pad((string) random_int(1, 999999), 6, '0', STR_PAD_LEFT);
        } while (Equipment::query()->where('identifier_no', $number)->exists());

        return $number;
    }

    public function delete($equipmentId): array
    {
        $equipment = Equipment::query()->find($equipmentId);

        if (! $equipment) {
            throw new \Exception('Equipment not found.', 404);
        }

        $deletedEquipment = [
            'id' => $equipment->id,
            'name' => $equipment->name,
            'type' => $equipment->type,
            'identifier_no' => $equipment->identifier_no,
            'status' => $equipment->status,
            'project_id' => $equipment->project_id,
        ];

        $equipment->delete();

        return [
            'message' => 'Equipment deleted successfully.',
            'equipment' => $deletedEquipment,
            'status' => 200,
        ];
    }

    public function storeMaintenance($request): array
    {
        $request->validated();

        $equipment = Equipment::query()->find($request->equipment_id);

        if (! $equipment) {
            throw new \Exception('Equipment not found.', 404);
        }

        $openMaintenance = EquipmentMaintenance::query()
            ->where('equipment_id', $request->equipment_id)
            ->whereNull('end_date')
            ->first();

        if ($openMaintenance) {
            throw new \Exception('This equipment already has an active maintenance record.', 422);
        }

        $maintenance = EquipmentMaintenance::query()->create([
            'equipment_id' => $request->equipment_id,
            'start_date' => $request->start_date,
            'type' => $request->type,
            'description' => $request->description,
            'end_date' => null,
        ]);

        $equipment->status = 'Maintenance';
        $equipment->save();

        $maintenance->load('equipment');

        return [
            'message' => 'Equipment maintenance created successfully.',
            'maintenance' => $maintenance,
            'status' => 201,
        ];
    }

    public function getByStatus($request): array
    {
        $request->validated();

        $query = Equipment::query()
            ->with([
                'activeBooking.workItem.project'
            ]);

        if ($request->status !== 'all') {
            $query->where('status', $request->status);
        }

        $equipment = $query->get()->map(function ($equipment) {

            $response = [
                'id' => $equipment->id,
                'name' => $equipment->name,
                'type' => $equipment->type,
                'identifier_no' => $equipment->identifier_no,
                'status' => $equipment->status,
            ];

            if (
                $equipment->status === 'Booked' &&
                $equipment->activeBooking
            ) {

                $response['work_item'] = [
                    'id' => $equipment->activeBooking->workItem->id,
                    'name' => $equipment->activeBooking->workItem->name,
                ];

                $response['project'] = [
                    'id' => $equipment->activeBooking->workItem->project->id,
                    'name' => $equipment->activeBooking->workItem->project->name,
                ];
            }

            return $response;
        });

        return [
            'message' => 'Equipment fetched successfully.',
            'equipment' => $equipment,
            'status' => 200,
        ];
    }

    public function closeMaintenance($maintenanceId, $request): array
    {
        $request->validated();

        $maintenance = EquipmentMaintenance::query()->find($maintenanceId);

        if (! $maintenance) {
            throw new \Exception('Maintenance record not found.', 404);
        }

        if (! is_null($maintenance->end_date)) {
            throw new \Exception('This maintenance record is already closed.', 422);
        }

        if ($request->end_date < $maintenance->start_date) {
            throw new \Exception('The end date cannot be earlier than the start date.', 422);
        }

        $maintenance->end_date = $request->end_date;
        $maintenance->save();

        $equipment = Equipment::query()->find($maintenance->equipment_id);

        if ($equipment) {
            $equipment->status = 'Available';
            $equipment->save();
        }

        $maintenance->load('equipment');

        return [
            'message' => 'Equipment maintenance closed successfully.',
            'maintenance' => $maintenance,
            'status' => 200,
        ];
    }
    public function storebook($request): array
    {
        $request->validated();

        $user = Auth::user();

        $workItem = WorkItem::query()
            ->with('project')
            ->find($request->work_item_id);

        if (! $workItem) {
            throw new \Exception('Work item not found.', 404);
        }

        $project = $workItem->project;

        $isCompanyAdmin = $user->hasRole('company_admin');

        $isProjectManager =
            $user->hasRole('project_manager') &&
            $project->project_manager_id == $user->id;

        $isAssistant =
            $user->hasRole('assistant') &&
            $project->assistant_engineer_id == $user->id;

        if (! $isCompanyAdmin && ! $isProjectManager && ! $isAssistant) {
            throw new \Exception(
                'You are not allowed to book equipment for this project.',
                403
            );
        }

        $equipment = Equipment::query()->find($request->equipment_id);

        if (! $equipment) {
            throw new \Exception('Equipment not found.', 404);
        }

        if ($equipment->status === 'Maintenance') {
            throw new \Exception(
                'Equipment is under maintenance.',
                422
            );
        }


        if ($equipment->status === 'Booked') {
            throw new \Exception(
                'Equipment is already booked.',
                422
            );
        }

        $hasConflict = EquipmentBooking::query()

            ->where('equipment_id', $equipment->id)

            ->where('status', 'active')

            ->where(function ($query) use ($request) {

                $query
                    ->whereBetween('start_date', [
                        $request->start_date,
                        $request->end_date
                    ])

                    ->orWhereBetween('end_date', [
                        $request->start_date,
                        $request->end_date
                    ]);
            })

            ->exists();

        if ($hasConflict) {
            throw new \Exception(
                'Equipment is already booked for this period.',
                422
            );
        }

        $booking = EquipmentBooking::query()->create([

            'equipment_id' => $equipment->id,

            'work_item_id' => $workItem->id,

            'booked_by' => $user->id,

            'start_date' => $request->start_date,

            'end_date' => $request->end_date,

            'notes' => $request->notes,

            'status' => 'active',
        ]);

        $equipment->update([
            'status' => 'Booked',
        ]);

        $booking->load([
            'equipment',
            'bookedBy',
            'workItem.project',
        ]);

        return [

            'message' => 'Equipment booked successfully.',

            'booking' => [

                'id' => $booking->id,

                'status' => $booking->status,

                'start_date' => $booking->start_date,

                'end_date' => $booking->end_date,

                'notes' => $booking->notes,

                'equipment' => [
                    'id' => $booking->equipment->id,
                    'name' => $booking->equipment->name,
                    'type' => $booking->equipment->type,
                ],

                'work_item' => [
                    'id' => $booking->workItem->id,
                    'name' => $booking->workItem->name,
                ],

                'project' => [
                    'id' => $booking->workItem->project->id,
                    'name' => $booking->workItem->project->name,
                ],

                'booked_by' => [
                    'id' => $booking->bookedBy->id,
                    'name' => $booking->bookedBy->name,
                ],
            ],

            'status' => 201,
        ];
    }
    public function finishBooking($request, $bookingId): array
    {
        $request->validated();

        $user = Auth::user();

        $booking = EquipmentBooking::query()
            ->with([
                'equipment',
                'workItem.project',
                'bookedBy',
            ])
            ->find($bookingId);

        if (! $booking) {
            throw new \Exception(
                'Booking not found.',
                404
            );
        }

        if ($booking->status !== 'active') {
            throw new \Exception(
                'Booking is already completed.',
                422
            );
        }

        if ($request->end_date < $booking->start_date) {
            throw new \Exception(
                'End date cannot be before booking start date.',
                422
            );
        }

        $booking->update([

            'end_date' => $request->end_date,

            'status' => 'completed',
        ]);
        $project = $booking->workItem->project;

        $isCompanyAdmin = $user->hasRole('company_admin');

        $isProjectManager =
            $user->hasRole('project_manager') &&
            $project->project_manager_id == $user->id;

        $isAssistant =
            $user->hasRole('assistant') &&
            $project->assistant_engineer_id == $user->id;

        if (! $isCompanyAdmin && ! $isProjectManager && ! $isAssistant) {
            throw new \Exception(
                'You are not allowed to finish this booking.',
                403
            );
        }

        $booking->update([

            'end_date' => $request->end_date,

            'status' => 'completed',
        ]);

        $booking->equipment->update([
            'status' => 'Available',
        ]);

        return [

            'message' => 'Equipment booking completed successfully.',

            'booking' => [

                'id' => $booking->id,

                'status' => $booking->status,

                'start_date' => $booking->start_date,

                'end_date' => $booking->end_date,

                'equipment' => [
                    'id' => $booking->equipment->id,
                    'name' => $booking->equipment->name,
                ],

                'work_item' => [
                    'id' => $booking->workItem->id,
                    'name' => $booking->workItem->name,
                ],

                'project' => [
                    'id' => $project->id,
                    'name' => $project->name,
                ],
            ],

            'status' => 200,
        ];
    }
    public function search($request): array
    {
        $request->validated();

        $equipment = Equipment::query()
            ->with([
                'activeBooking.workItem.project',
            ])
            ->where('name', 'like', '%' . $request->keyword . '%')
            ->orWhere('type', 'like', '%' . $request->keyword . '%')
            ->orWhere('identifier_no', 'like', '%' . $request->keyword . '%')
            ->get()
            ->map(function ($equipment) {

                $response = [
                    'id' => $equipment->id,
                    'name' => $equipment->name,
                    'type' => $equipment->type,
                    'identifier_no' => $equipment->identifier_no,
                    'status' => $equipment->status,
                ];

                $booking = $equipment->activeBooking;

                if (
                    $equipment->status === 'Booked' &&
                    $booking &&
                    $booking->workItem
                ) {

                    $response['booking'] = [
                        'id' => $booking->id,
                        'start_date' => $booking->start_date,
                        'end_date' => $booking->end_date,
                    ];

                    $response['work_item'] = [
                        'id' => $booking->workItem->id,
                        'name' => $booking->workItem->name,
                    ];

                    $project = $booking->workItem->project;

                    $response['project'] = $project ? [
                        'id' => $project->id,
                        'name' => $project->name,
                        'location' => $project->location,
                        'status' => $project->status,
                    ] : null;
                }

                return $response;
            });

        return [
            'message' => 'Equipment search completed successfully.',
            'equipment' => $equipment,
            'status' => 200,
        ];
    }


    public function show($id): array
{
    $equipment = Equipment::query()
        ->with([
            'activeBooking.workItem.project',
            'activeBooking.bookedBy',

            'bookings.workItem.project',
            'bookings.bookedBy',

            'maintenances',
        ])
        ->find($id);

    if (! $equipment) {
        throw new \Exception(
            'Equipment not found.',
            404
        );
    }

    $currentBooking = null;

    if ($equipment->activeBooking) {

        $booking = $equipment->activeBooking;

        $duration = null;

        if ($booking->start_date && $booking->end_date) {
            $duration = Carbon::parse($booking->start_date)
                ->diffInDays(
                    Carbon::parse($booking->end_date)
                );
        }

        $currentBooking = [

            'id' => $booking->id,

            'start_date' => $booking->start_date,

            'end_date' => $booking->end_date,

            'duration_days' => $duration,

            'work_item' => [
                'id' => $booking->workItem->id,
                'name' => $booking->workItem->name,
            ],

            'project' => [
                'id' => $booking->workItem->project->id,
                'name' => $booking->workItem->project->name,
            ],

            'booked_by' => [
                'id' => $booking->bookedBy->id,
                'name' => $booking->bookedBy->name,
            ],
        ];
    }

    $currentMaintenance = $equipment
        ->maintenances()
        ->whereNull('end_date')
        ->latest('start_date')
        ->first();

    $maintenanceData = null;

    if ($currentMaintenance) {

        $duration = Carbon::parse(
            $currentMaintenance->start_date
        )->diffInDays(now());

        $maintenanceData = [

            'id' => $currentMaintenance->id,

            'type' => $currentMaintenance->type,

            'description' => $currentMaintenance->description,

            'start_date' => $currentMaintenance->start_date,

            'end_date' => $currentMaintenance->end_date,

            'duration_days' => $duration,

            'status' => 'active',
        ];
    }

    $bookingHistory = $equipment->bookings
        ->map(function ($booking) {

            return [

                'id' => $booking->id,

                'status' => $booking->status,

                'start_date' => $booking->start_date,

                'end_date' => $booking->end_date,

                'work_item' =>
                    $booking->workItem?->name,

                'project' =>
                    $booking->workItem?->project?->name,
            ];
        })
        ->values();

    $maintenanceHistory = $equipment->maintenances
        ->map(function ($maintenance) {

            return [

                'id' => $maintenance->id,

                'type' => $maintenance->type,

                'description' => $maintenance->description,

                'start_date' => $maintenance->start_date,

                'end_date' => $maintenance->end_date,

                'status' => $maintenance->end_date
                    ? 'completed'
                    : 'active',
            ];
        })
        ->values();

    return [

        'message' =>
            'Equipment details fetched successfully.',

        'equipment' => [

            'id' => $equipment->id,

            'name' => $equipment->name,

            'type' => $equipment->type,

            'identifier_no' =>
                $equipment->identifier_no,

            'status' => $equipment->status,

            'current_booking' =>
                $currentBooking,

            'current_maintenance' =>
                $maintenanceData,

            'booking_history' =>
                $bookingHistory,

            'maintenance_history' =>
                $maintenanceHistory,
        ],

        'status' => 200,
    ];
}
    public function completeBookingsForWorkItem(
        WorkItem $workItem
    ): void {

        $bookings = EquipmentBooking::query()
            ->with('equipment')
            ->where('work_item_id', $workItem->id)
            ->where('status', 'active')
            ->get();

        foreach ($bookings as $booking) {

            $booking->update([
                'status' => 'completed',
                'end_date' => now(),
            ]);

            if ($booking->equipment) {

                $booking->equipment->update([
                    'status' => 'Available',
                ]);
            }
        }
    }
}
