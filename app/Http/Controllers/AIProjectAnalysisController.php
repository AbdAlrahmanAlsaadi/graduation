<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Services\AI\AIAdvancedAnalysisService;
use App\Services\AI\AIChatService;
use App\Services\AI\AIProjectService;
use App\Services\AI\ProjectAnalysisService;
use Illuminate\Http\Request;

class AIProjectAnalysisController extends Controller
{
    public function analyze($id, Request $request, AIProjectService $service)
    {
        $language = $request->get('language', 'ar');
        $question = $request->get('question');

        $result = $service->analyze($id, $language, $question);

        return response()->json($result);
    }}
