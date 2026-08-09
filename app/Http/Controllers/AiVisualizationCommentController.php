<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAiVisualizationCommentRequest;
use App\Models\AiVisualization;
use App\Models\AiVisualizationComment;
use App\Services\AI\AiVisualizationCommentService;

class AiVisualizationCommentController extends Controller
{

    public function __construct(
        protected AiVisualizationCommentService $service
    ) {}



    public function store(
        StoreAiVisualizationCommentRequest $request,
        $id
    ) {

        $aiVisualization = AiVisualization::find($id);


        if (!$aiVisualization) {

            return response()->json([

                'success' => false,

                'message' => 'التصميم غير موجود.'

            ], 404);
        }



        $result = $this->service->store(
            $request,
            $aiVisualization
        );


        return response()->json($result);
    }





    public function index($id)
    {

        $aiVisualization = AiVisualization::find($id);


        if (!$aiVisualization) {

            return response()->json([

                'success' => false,

                'message' => 'التصميم غير موجود.'

            ], 404);
        }


        $comments = $this->service->index(
            $aiVisualization
        );


        return response()->json([

            'success' => true,

            'data' => $comments

        ]);
    }
    public function destroy($id)
    {
        $comment = AiVisualizationComment::find($id);


        if (!$comment) {

            return response()->json([

                'success' => false,

                'message' => 'التعليق غير موجود.'

            ], 404);
        }


        $result = $this->service->delete($comment);


        return response()->json($result);
    }
}
