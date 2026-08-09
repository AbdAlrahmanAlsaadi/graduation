<?php

namespace App\Http\Controllers;

use App\Http\Requests\GenerateImageRequest;
use App\Services\AI\AiVisualizationService;

class AiVisualizationController extends Controller
{
    public function __construct(
        private AiVisualizationService $service
    ) {}

    public function generate(GenerateImageRequest $request)
    {
        $result = $this->service->generate($request);

        return response()->json($result);
    }
}
