<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;

class GeminiService
{
    public function analyze(array $context, string $question, string $language = 'ar'): array
    {
        $apiKey = env('GEMINI_API_KEY');

        $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key={$apiKey}";

        $prompt = $this->buildPrompt($context, $question, $language);

        $response = Http::timeout(90)->post($url, [
            'contents' => [
                [
                    'role' => 'user',
                    'parts' => [
                        ['text' => $prompt]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature' => 0.2,
                'topP' => 0.8,
                'maxOutputTokens' => 1024,
            ]
        ]);

        if (!$response->successful()) {
            return [
                'success' => false,
                'error' => $response->body()
            ];
        }

        $text = $response->json('candidates.0.content.parts.0.text');

        if (!$text) {
            return [
                'success' => false,
                'error' => 'Empty response from Gemini'
            ];
        }

        $text = preg_replace('/```json|```/', '', $text);
        $text = trim($text);

        return [
            'success' => true,
            'result' => [
                'answer' => $text,
                'language' => $language
            ]
        ];
    }

    private function buildPrompt(array $context, string $question, string $language): string
    {
        return <<<PROMPT
You are an embedded AI assistant inside a construction management system.

ROLE:
- You are NOT an analyst.
- You are NOT a report generator.
- You are ONLY a question-answering assistant.

STRICT RULES:
- Answer ONLY the user's question.
- Do NOT generate full reports or summaries unless explicitly requested.
- Do NOT add extra sections.
- Do NOT repeat project data unless directly relevant.
- If the answer is not in the context, respond exactly: "Insufficient data".
- Do NOT guess or infer missing information.
- Keep response short, precise, and direct.
- Language MUST be: {$language}

USER QUESTION:
{$question}

PROJECT CONTEXT (JSON):
```json
PROMPT
        . json_encode($context, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        . <<<PROMPT
        FINAL RULE:
Return ONLY the direct answer. No formatting. No markdown. No explanations.
PROMPT;
    }
}
