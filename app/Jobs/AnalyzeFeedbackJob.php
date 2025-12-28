<?php

namespace App\Jobs;

use App\Services\GrokService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class AnalyzeFeedbackJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 120;
    public $maxExceptions = 2;

    /**
     * Create a new job instance.
     */
    public function __construct(
        private array $feedbacks,
        private string $jobId
    ) {}

    /**
     * Execute the job.
     */
    public function handle(GrokService $grokService): void
    {
        Log::info('Processing feedback analysis job', ['job_id' => $this->jobId]);

        try {
            $rawResult = $grokService->analyzeFeedback($this->feedbacks);

            if (!$rawResult['success']) {
                throw new \Exception($rawResult['error'] ?? 'Analysis failed');
            }

            $grokSummary = $rawResult['results']['summary'];

            $parsedSummary = is_string($grokSummary)
                ? json_decode($grokSummary, true)
                : $grokSummary;

            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::warning('Failed to parse Grok JSON summary', [
                    'job_id' => $this->jobId,
                    'error' => json_last_error_msg()
                ]);
                $parsedSummary = ['raw_summary' => $grokSummary];
            }

            $summaryData = [
                'summary' => $parsedSummary,
                'model' => $rawResult['results']['model'] ?? 'grok-4-latest',
                'feedback_count' => $rawResult['results']['feedback_count'] ?? count($this->feedbacks),
                'usage' => $rawResult['results']['usage'] ?? null,
            ];

            cache()->put("analysis_result_{$this->jobId}", [
                'success' => true,
                'results' => $summaryData,
                'error' => null,
            ], now()->addHours(1));

            Log::info('Feedback analysis job completed and cached', ['job_id' => $this->jobId]);
        } catch (\Throwable $e) {
            Log::error('Feedback analysis job failed', [
                'job_id' => $this->jobId,
                'error' => $e->getMessage()
            ]);

            cache()->put("analysis_result_{$this->jobId}", [
                'success' => false,
                'results' => null,
                'error' => 'Analysis failed: ' . $e->getMessage(),
            ], now()->addHours(1));
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Feedback analysis job failed', [
            'job_id' => $this->jobId,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);

        cache()->put("analysis_result_{$this->jobId}", [
            'success' => false,
            'error' => $exception->getMessage(),
            'results' => null
        ], now()->addHours(1));
    }
}
