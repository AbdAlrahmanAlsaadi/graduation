<?php

namespace App\Http\Controllers;

use App\Services\DatabaseAIService;
use Illuminate\Http\Request;
use Exception;

class AIProjectAnalysisController extends Controller
{
    public function chat(Request $request, DatabaseAIService $aiService)
    {
        try {
            $request->validate([
                'question' => 'required|string|max:1000'
            ]);

            $result = $aiService->ask($request->question);

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'] ?? 'حدث خطأ أثناء التحليل.',
                ], 500);
            }

            // التعديل هنا: أصبحنا نعيد 'answer' بدلاً من الحقول المنفصلة
            return response()->json([
                'success' => true,
                'message' => 'تم التحليل بنجاح.',
                'answer' => $result['answer'], // <-- هذا هو المفتاح الجديد
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
