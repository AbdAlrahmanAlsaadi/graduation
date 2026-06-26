<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiInspectionController extends Controller
{
    public function inspect(Request $request)
    {
        $request->validate([
            'construction_image' => 'required|image|mimes:jpeg,png,jpg|max:20480',
            'inspection_type' => 'required|in:tiles,paint,cement_plaster,ceiling,electrical,plumbing,general',
        ]);

        try {

            $imageFile = $request->file('construction_image');
            $imageBase64 = base64_encode(
                file_get_contents($imageFile->getRealPath())
            );

            $prompt = $this->buildPrompt(
                $request->inspection_type
            );

            $payload = [
                'contents' => [
                    [
                        'parts' => [
                            [
                                'text' => $prompt
                            ],
                            [
                                'inline_data' => [
                                    'mime_type' => $imageFile->getClientMimeType(),
                                    'data' => $imageBase64,
                                ],
                            ],
                        ],
                    ],
                ],
            ];

            $apiKey = env('GEMINI_API_KEY');

            $url =
                "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key={$apiKey}";

            $response = Http::timeout(60)
                ->post($url, $payload);

            if (!$response->successful()) {

                Log::error('Gemini API Error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'فشل الاتصال بخدمة التحليل.',
                ], 500);
            }

            $data = $response->json();

            $rawText =
                $data['candidates'][0]['content']['parts'][0]['text']
                ?? null;

            if (!$rawText) {

                return response()->json([
                    'success' => false,
                    'message' => 'لم يتم استلام تقرير من الذكاء الاصطناعي.',
                ], 500);
            }

            $rawText = preg_replace('/```json|```/', '', $rawText);

            $report = json_decode(trim($rawText), true);

            if (!$report) {

                Log::error('Invalid JSON From Gemini', [
                    'response' => $rawText,
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'تم استلام رد غير صالح من الذكاء الاصطناعي.',
                    'raw_response' => $rawText,
                ], 500);
            }

            return response()->json([
                'success' => true,
                'message' => 'تم إصدار التقرير بنجاح.',
                'inspection_type' => $request->inspection_type,
                'report' => $report,
            ]);
        } catch (Exception $e) {

            Log::error('AI Inspection Exception', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    private function buildPrompt(string $type): string
    {
        $commonRules = '
أنت مهندس استشاري مختص بأعمال البناء والتشطيبات.

تعليمات مهمة:

- حلل الصورة فقط بناءً على العناصر الظاهرة.
- لا تفترض أي معلومات غير مرئية.
- لا تخترع عيوباً غير واضحة.
- لا تعتبر الاختلافات الجمالية أو اختلاف الأذواق عيوباً هندسية.
- ضع العيوب المؤكدة فقط ضمن confirmed_defects.
- ضع الملاحظات المحتملة وغير المؤكدة ضمن visual_observations.
- ضع العناصر التي لا يمكن التحقق منها من الصورة ضمن unverified_items.
- كن متحفظاً في منح الدرجات.
- لا تمنح درجة أعلى من 85 إلا إذا كانت جودة التنفيذ ممتازة والصورة واضحة جداً.
- إذا كانت الصورة غير واضحة أو لا تُظهر كامل العنصر المطلوب فحصه فقم بتخفيض confidence.

أعد النتيجة بصيغة JSON فقط دون أي نص إضافي:

{
    "status": "accepted|accepted_with_notes|rejected",
    "score": 0,
    "confidence": 0,
    "summary": "",
    "confirmed_defects": [],
    "visual_observations": [],
    "unverified_items": [],
    "recommendations": []
}

تعريف الحالات:

accepted:
لا توجد عيوب مؤكدة.

accepted_with_notes:
توجد ملاحظات أو عيوب بسيطة لا تمنع الاستلام.

rejected:
توجد عيوب واضحة وجوهرية تمنع الاستلام.
';

        return match ($type) {

            'tiles' => $commonRules . '

افحص فقط أعمال البلاط:

1- استقامة البلاط.
2- انتظام الفواصل.
3- وجود كسور.
4- وجود تشققات.
5- تطابق التركيب.
6- جودة التشطيب الظاهرة.

لا يمكن التأكد من:
- استواء الأرضية.
- وجود فراغات تحت البلاط.
- جودة التثبيت الداخلية.

ضع العناصر غير القابلة للتحقق ضمن unverified_items.
',

            'paint' => $commonRules . '

افحص فقط أعمال الدهان:

1- تجانس اللون.
2- آثار الرول أو الفرشاة.
3- التشققات.
4- التقشر.
5- التبقعات.
6- جودة الحواف والزوايا.
7- آثار الرطوبة الظاهرة.

ضع العناصر غير القابلة للتحقق ضمن unverified_items.
',

            'cement_plaster' => $commonRules . '

افحص فقط أعمال الطينة الإسمنتية (Cement Plaster):

1- استواء السطح الظاهر.
2- وجود شروخ أو تشققات.
3- وجود انفصال أو تقشر.
4- وجود تعشيش أو فراغات ظاهرة.
5- جودة الزوايا والحواف.
6- انتظام التشطيب النهائي للطينة.
7- آثار الرطوبة أو التبقعات.
8- جودة التنفيذ بشكل عام.

لا تعتبر اختلاف الظلال أو الإضاءة أو تغير اللون البسيط عيباً ما لم يكن مرتبطاً بعيب واضح.

لا يمكن التأكد من:
- سماكة الطينة.
- قوة التماسك الداخلية.
- جودة المعالجة بالماء.
- استواء كامل الحائط إذا لم يظهر كاملاً.

إذا ظهرت شروخ أو انفصال واضح اعتبرها confirmed_defects.

إذا كانت مجرد ملاحظة محتملة ضعها ضمن visual_observations.
',

            'ceiling' => $commonRules . '

افحص فقط أعمال السقف:

1- استقامة السقف.
2- التشققات.
3- الفواصل.
4- التموجات.
5- جودة التشطيب.
6- آثار الرطوبة أو التسرب الظاهرة.

ضع العناصر غير القابلة للتحقق ضمن unverified_items.
',

            'electrical' => $commonRules . '

افحص فقط الأعمال الكهربائية الظاهرة:

1- جودة تركيب المفاتيح.
2- جودة تركيب المقابس.
3- انتظام المحاذاة.
4- جودة التشطيب حول القطع الكهربائية.
5- وجود تلف ظاهر.

لا تفترض سلامة التمديدات الداخلية لأنها غير مرئية.
',

            'plumbing' => $commonRules . '

افحص فقط الأعمال الصحية الظاهرة:

1- جودة تركيب الأدوات الصحية.
2- المحاذاة.
3- التشطيب حول التمديدات.
4- وجود تسربات ظاهرة.
5- وجود تشققات أو كسور ظاهرة.

لا تفترض حالة الشبكة الداخلية لأنها غير مرئية.
',

            default => $commonRules . '

قم بإجراء تقييم عام لجميع العناصر الظاهرة بالصورة فقط.

صنف النتائج إلى:
- confirmed_defects
- visual_observations
- unverified_items
',
        };
    }}
