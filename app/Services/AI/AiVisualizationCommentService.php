<?php

namespace App\Services\AI;

use App\Services\Notification\NotificationService;

use App\Http\Requests\StoreAiVisualizationCommentRequest;
use App\Models\AiVisualization;
use App\Models\AiVisualizationComment;
use App\Models\User;

class AiVisualizationCommentService
{

    public function __construct(
        protected NotificationService $notificationService
    ) {}



    public function store(
        StoreAiVisualizationCommentRequest $request,
        AiVisualization $aiVisualization
    ): array {

        // حفظ التعليق

        $comment = AiVisualizationComment::create([

            'ai_visualization_id' => $aiVisualization->id,

            'user_id' => auth()->id(),

            'comment' => $request->comment,

        ]);



        // جلب المشروع

        $project = $aiVisualization
            ->projectImage
            ->project;



        $notificationData = [

            'sender_id' => auth()->id(),

            'project_id' => $project->id,

            'type' => 'ai_comment',


            'title' => 'تعليق جديد على تصميم AI',


            'body' => auth()->user()->name .
                ' أضاف تعليق على أحد التصاميم.',


            'data' => [

                'ai_visualization_id' => $aiVisualization->id,

                'project_image_id' => $aiVisualization->project_image_id,

            ],

        ];



        // إرسال للـ Project Manager

        if ($project->projectManager) {

            $this->notificationService->send(

                $project->projectManager,

                $notificationData

            );
        }




        $admins = User::role('company_admin')->get();


        foreach ($admins as $admin) {


            $this->notificationService->send(

                $admin,

                $notificationData

            );
        }

        return [

            'success' => true,

            'message' => 'تم إضافة التعليق بنجاح.',

            'data' => $comment

        ];
    }





    public function index(
        AiVisualization $aiVisualization
    ) {

        return $aiVisualization
            ->comments()
            ->with('user:id,name')
            ->latest()
            ->get();
    }


    public function delete(
        AiVisualizationComment $comment
    ): array {

        $comment->delete();


        return [

            'success' => true,

            'message' => 'تم حذف التعليق بنجاح.'

        ];
    }
}
