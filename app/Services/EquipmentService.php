<?php

namespace App\Services;

use App\Models\Equipment;
use App\Models\EquipmentMaintenance;

class EquipmentService
{
    public function store($request): array
    {
        $request->validated();

        $identifierNo = $this->generateIdentifierNo();

        $equipment = Equipment::query()->create([
            'id' => $request->project_id,
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
            ->with(['project:id,name']); 

        if ($request->status !== 'all') {
            $query->where('status', $request->status);
        }

        $equipment = $query->get();

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
}
