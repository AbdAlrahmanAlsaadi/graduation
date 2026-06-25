<?php

namespace App\Http\Controllers;

use App\Http\Requests\Calculation\CalculatePaintCostRequest;
use App\Http\Requests\Calculation\CalculatePlasterCostRequest;
use App\Http\Requests\Calculation\CalculateTileCostRequest;
use App\Services\WorkshopCostCalculationService;

class WorkshopCostCalculationController extends Controller
{
    public function __construct(
        private WorkshopCostCalculationService $service
    ) {}

    public function plaster(
        CalculatePlasterCostRequest $request,
        int $projectId
    ) {
        return response()->json(
            $this->service->calculatePlasterCost(
                $projectId,
                $request->price_per_meter,
                $request->beams_count
            )
        );
    }

    public function paint(
        CalculatePaintCostRequest $request,
        int $projectId
    ) {
        return response()->json(
            $this->service->calculatePaintingCost(
                $projectId,
                $request->price_per_meter,
                $request->beams_count
            )
        );
    }

    public function tile(
    CalculateTileCostRequest $request,
    int $projectId
) {
    return response()->json(
        $this->service->calculateTileCost(
            $projectId,
            $request->price_per_meter,
            $request->skirting_factor,
            $request->sink_installation_cost
        )
    );

    }

}
