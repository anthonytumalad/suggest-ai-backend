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
    ){}

    /**
     * Execute the job.
     */
    public function handle(GrokService $grokService): void
    {
        Log::info('Processing feedback analysis job', ['job_id' => $this->jobId]);

        $result = $grokService->analyzeFeedback($this->feedbacks);
        cache()->put("analysis_result_{$this->jobId}", $result, now()->addHours(1));

        Log::info('Feedback analysis job completed', ['job_id' => $this->jobId]);
    }

    public function failed(\Throwable $exception) : void
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
