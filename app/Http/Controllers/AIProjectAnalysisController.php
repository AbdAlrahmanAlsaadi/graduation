<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Message;
use App\Services\DatabaseAIService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Exception;

class AIProjectAnalysisController extends Controller
{
    // 💬 إرسال سؤال
    public function chat(Request $request)
    {
        try {
            $request->validate([
                'question' => 'required|string|max:1000',
                'conversation_id' => 'nullable|exists:conversations,id',
            ]);

            // 1. تحديد المحادثة
            $conversation = null;
            if ($request->conversation_id) {
                $conversation = Conversation::with('messages')->find($request->conversation_id);
            }

            if (!$conversation) {
                $conversation = Conversation::create([
                    'title' => Str::limit($request->question, 50),
                ]);
            }

            // 2. جلب التاريخ (نحسب الـ role من الترتيب)
            $messages = $conversation->messages()->orderBy('id', 'asc')->get();
            $history = [];
            foreach ($messages as $index => $msg) {
                $history[] = [
                    'role' => ($index % 2 == 0) ? 'user' : 'assistant',
                    'text' => $msg->content
                ];
            }

            // 3. استدعاء الـ Service
            $aiService = new DatabaseAIService();
            $result = $aiService->ask($request->question, $history);

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'] ?? 'خطأ في التحليل.',
                ], 500);
            }

            // 4. حفظ السؤال والجواب
            Message::create([
                'conversation_id' => $conversation->id,
                'content' => $request->question,
            ]);
            Message::create([
                'conversation_id' => $conversation->id,
                'content' => $result['answer'],
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'conversation_id' => $conversation->id,
                    'question' => $request->question,
                    'answer' => $result['answer'],
                    'created_at' => now()->toDateTimeString(),
                ]
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    // 📋 قائمة المحادثات
    public function index()
    {
        try {
            $conversations = Conversation  ::withCount('messages')
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $conversations->map(function ($conv) {
                    return [
                        'id' => $conv->id,
                        'title' => $conv->title ?? $conv->first_question,
                        'messages_count' => $conv->messages_count,
                        'created_at' => $conv->created_at->toDateTimeString(),
                    ];
                })
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    // 🔍 عرض محادثة محددة
    public function show($id)
    {
        try {
            $conversation = Conversation::with('messages')->findOrFail($id);
            $messages = $conversation->messages()->orderBy('id', 'asc')->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $conversation->id,
                    'title' => $conversation->title,
                    'messages' => $messages->map(function ($msg, $index) {
                        return [
                            'role' => ($index % 2 == 0) ? 'user' : 'assistant',
                            'content' => $msg->content,
                            'created_at' => $msg->created_at->toDateTimeString(),
                        ];
                    }),
                    'created_at' => $conversation->created_at->toDateTimeString(),
                ]
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    // 🗑️ حذف محادثة
    public function destroy($id)
    {
        try {
            $conversation = Conversation::findOrFail($id);
            $conversation->delete();

            return response()->json([
                'success' => true,
                'message' => 'تم حذف المحادثة بنجاح.',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function newConversation(Request $request)
    {
        try {
            $title = $request->input('title', 'محادثة جديدة'); // إن لم يُرسل، استخدم الافتراضي

            $conversation = Conversation::create([
                'title' => $title,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'تم بدء محادثة جديدة.',
                'data' => ['conversation_id' => $conversation->id]
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    // 🧹 مسح ذاكرة المحادثة (اختياري)
    public function clearMemory(Request $request)
    {
        try {
            $conversationId = $request->input('conversation_id');
            if (!$conversationId) {
                return response()->json([
                    'success' => false,
                    'message' => 'لم يتم توفير conversation_id.'
                ], 400);
            }

            // حذف جميع رسائل المحادثة (مع الاحتفاظ بالمحادثة)
            Message::where('conversation_id', $conversationId)->delete();

            return response()->json([
                'success' => true,
                'message' => 'تم مسح ذاكرة المحادثة بنجاح.',
                'conversation_id' => $conversationId
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
