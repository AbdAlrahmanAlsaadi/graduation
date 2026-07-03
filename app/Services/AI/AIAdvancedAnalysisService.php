<?php

namespace App\Services\AI;

class AIAdvancedAnalysisService
{
    public function analyze(array $context): array
    {
        $workItems = $context['work_items'] ?? [];

        if (empty($workItems)) {
            return [
                'progress_analysis' => null,
                'delay_prediction' => null,
                'timeline_suggestion' => null,
                'engineering_notes' => ['No work items found'],
            ];
        }

        // =========================
        // Normalize statuses
        // =========================
        $completed = array_values(array_filter($workItems, fn($i) => $i['status'] === 'completed'));
        $inProgress = array_values(array_filter($workItems, fn($i) => $i['status'] === 'ongoing'));
        $pending = array_values(array_filter($workItems, fn($i) => $i['status'] === 'planned'));

        $total = count($workItems);

        // =========================
        // 1. Progress Analysis
        // =========================
        $completionPercentage = $total > 0
            ? round((count($completed) / $total) * 100, 2)
            : 0;

        $progress = [
            'completion_percentage' => $completionPercentage,
            'completed_items' => $completed,
            'in_progress_items' => $inProgress,
            'pending_items' => $pending,
        ];

        // =========================
        // 2. Delay Prediction (Improved)
        // =========================

        $isNotStarted = count($completed) === 0 && count($inProgress) === 0;

        $delayReasons = [];

        // NOT always delayed if not started → depends on project size
        if ($isNotStarted && $completionPercentage == 0) {
            $delayReasons[] = "Project has no recorded progress yet.";
        }

        foreach ($workItems as $item) {

            $duration = $item['duration_days'] ?? 0;

            if ($item['status'] === 'planned' && $duration >= 15) {
                $delayReasons[] = "High duration planned task may impact schedule: {$item['name']}";
            }

            if ($item['status'] === 'ongoing' && $duration >= 10) {
                $delayReasons[] = "Ongoing task exceeding expected duration: {$item['name']}";
            }
        }

        $delayLevel = 'low';

        if ($completionPercentage < 10 && $isNotStarted) {
            $delayLevel = 'medium';
        }

        if (count($delayReasons) > 2) {
            $delayLevel = 'high';
        }

        $delay = [
            'is_delayed' => count($delayReasons) > 0,
            'delay_level' => $delayLevel,
            'reasons' => $delayReasons,
        ];

        // =========================
        // 3. Timeline Suggestion (Improved ordering)
        // =========================

        usort($pending, fn($a, $b) => ($a['duration_days'] ?? 0) <=> ($b['duration_days'] ?? 0));

        $recommendedOrder = array_merge(
            array_map(fn($i) => $i['name'], $inProgress),
            array_map(fn($i) => $i['name'], $pending)
        );

        $timeline = [
            'next_steps' => $this->generateNextSteps($workItems),
            'recommended_order' => $recommendedOrder,
            'estimated_days_change' => $this->estimateDuration($workItems),
        ];

        // =========================
        // 4. Engineering Notes (Smarter)
        // =========================

        $notes = [];

        if ($completionPercentage == 0) {
            $notes[] = "Project has not started execution phase.";
        }

        if (count($pending) > count($inProgress)) {
            $notes[] = "Workload is heavily skewed toward pending tasks.";
        }

        if ($completionPercentage > 0 && $completionPercentage < 20) {
            $notes[] = "Early stage project - schedule risk is high if no execution acceleration occurs.";
        }

        return [
            'progress_analysis' => $progress,
            'delay_prediction' => $delay,
            'timeline_suggestion' => $timeline,
            'engineering_notes' => $notes,
        ];
    }

    private function generateNextSteps(array $workItems): array
    {
        return [
            "Start critical path execution tasks first",
            "Assign resources to ongoing and early-stage tasks",
            "Validate dependencies between electrical and plumbing works",
            "Prepare procurement for long-lead materials"
        ];
    }

    private function estimateDuration(array $workItems): int
    {
        return array_sum(array_map(fn($i) => $i['duration_days'] ?? 0, $workItems));
    }
}
