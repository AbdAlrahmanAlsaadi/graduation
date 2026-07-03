<?php

namespace App\Services\AI;

class AIProjectService
{
    public function __construct(
        private AIContextBuilderService $contextBuilder,
        private AIAdvancedAnalysisService $engine,
        private GeminiService $gemini
    ) {}

    public function analyze(int $projectId, string $language = 'ar', ?string $question = null): array
    {
        // 1. Build context
        $context = $this->contextBuilder->build($projectId);

        // 2. Always run local analysis (fast system)
        $local = $this->engine->analyze($context);

        // 3. If question exists → QA mode
        if ($question) {

            $ai = $this->gemini->analyze($context, $question, $language);

            if (!$ai['success']) {
                return [
                    'success' => false,
                    'local_result' => $local,
                    'error' => $ai['error']
                ];
            }

            return [
                'success' => true,
                'mode' => 'qa',
                'result' => [
                    'answer' => $ai['result']['answer'],
                    'language' => $language
                ]
            ];
        }

        // 4. No question → full analysis mode
        return [
            'success' => true,
            'mode' => 'analysis',
            'result' => array_merge($local, [
                'language' => $language
            ])
        ];
    }
}
