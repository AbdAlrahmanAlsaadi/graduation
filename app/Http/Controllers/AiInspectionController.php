<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiInspectionController extends Controller
{
    public function inspect(Request $request)
    {
        $request->validate([
            'construction_image' => 'required|image|mimes:jpeg,png,jpg|max:20480',
        ]);

        try {
            $imageFile = $request->file('construction_image');
            $imageBase64 = base64_encode(file_get_contents($imageFile->getRealPath()));

            // برومبت محسّن مع تنسيق واضح
            $prompt = "أنت مهندس استشاري أول. افحص صورة العمل المرفقة وأعِد تقريراً موجزاً (لا يتجاوز 200 كلمة) بالصيغة التالية تماماً:

التقييم العام: [جملة واحدة: ممتاز/مقبول/سيئ]

العيوب الفنية:
1. ...
2. ...
3. ...

النصائح :
1. ...
2. ...
3. ...

اكتب التقرير بهذه الصيغة فقط، دون أي مقدمات أو خاتمة، واستخدم النقاط المرقمة كما هو موضح.";

            $payload = [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt],
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

            // المفتاح مكتوب مباشرة
            $mySecretApiKey = "AQ.Ab8RN6J1n9or6sKia1YJj1C-onCrhdUVpNXCXejVEEkAjDBwMw";
            $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=" . $mySecretApiKey;

            $response = Http::timeout(60)->post($url, $payload);

            if ($response->successful()) {
                $data = $response->json();
                $rawText = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;

                if ($rawText) {
                    // تنظيف محسّن مع الاحتفاظ بالأرقام والنقاط
                    $cleanText = $this->cleanAndFormatText($rawText);

                    // زيادة الحد الأقصى إلى 1000 حرف
                    $maxLength = 1000;
                    $shortText = mb_substr($cleanText, 0, $maxLength);
                    if (mb_strlen($cleanText) > $maxLength) {
                        $shortText .= '...';
                    }

                    return response()->json([
                        'success' => true,
                        'message' => 'تم فحص جودة التشطيب وإصدار التقرير المختصر بنجاح.',
                        'inspection_report' => trim($shortText),
                    ]);
                }

                Log::warning('Gemini response missing text', $data);
                return response()->json([
                    'success' => false,
                    'message' => 'لم يتم العثور على التقرير في رد الخادم.',
                    'google_response' => $data,
                ], 500);
            }

            Log::error('Gemini API error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خادم جيميناي رفض معالجة الفحص.',
                'google_response' => $response->json(),
                'http_code' => $response->status(),
            ], 500);
        } catch (\Exception $e) {
            Log::error('AI Inspection exception', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ داخلي: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * تنظيف النص مع الاحتفاظ بالتنسيق الأساسي (أرقام، نقاط، سطور جديدة)
     */
    private function cleanAndFormatText(string $text): string
    {
        // إزالة علامات التنسيق الشائعة فقط (المؤثرة على القراءة)
        $text = preg_replace('/\*\*|__|[*_]{1,2}/', '', $text); // يزيل ** و __ و * و _
        // إزالة علامات التجزئة والشرطات الكبيرة (---, ###)
        $text = preg_replace('/#+\s*/', '', $text);
        $text = preg_replace('/-{3,}/', '', $text);
        // إزالة علامات > في بداية السطور
        $text = preg_replace('/^>\s*/m', '', $text);

        // توحيد فواصل الأسطر (إزالة الأسطر الفارغة المتعددة)
        $text = preg_replace('/\n{3,}/', "\n\n", $text);

        // إزالة المسافات الزائدة في بداية ونهاية كل سطر
        $lines = explode("\n", $text);
        $lines = array_map('trim', $lines);
        $text = implode("\n", $lines);

        // التأكد من وجود نقطة نهاية بعد الأرقام في القوائم (اختياري)
        return trim($text);
    }
}
