<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\GrokService;
use App\Jobs\AnalyzeFeedbackJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class GrokController extends Controller
{
    public function __construct(
        private GrokService $grokService
    ) {}

    public function analyzeFeedback(Request $request): JsonResponse
    {
        try {
            Log::info('Grok summarize endpoint called', ['input' => $request->all()]);

            $feedbacks = $request->input('feedbacks');

            if (empty($feedbacks)) {
                Log::warning('No feedbacks provided');
                return response()->json([
                    'success' => false,
                    'error' => 'No feedbacks provided'
                ], 400);
            }

            if (count($feedbacks) <= 10) {
                Log::info('Processing feedbacks synchronously', ['count' => count($feedbacks)]);
                $result = $this->grokService->analyzeFeedback($feedbacks);

                return response()->json([
                    'success' => $result['success'],
                    'data' => $result['results'] ?? null,
                    'error' => $result['error'] ?? null,
                    'model' => 'grok-4-latest'
                ]);
            }

            $jobId = uniqid();
            AnalyzeFeedbackJob::dispatch($feedbacks, $jobId);

            return response()->json([
                'success' => true,
                'message' => 'Analysis started in background',
                'job_id' => $jobId,
                'status_url' => url("/api/analysis/status/{$jobId}")
            ], 202);
        } catch (\Exception $e) {
            Log::error('Grok summarization failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to summarize feedback: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    public function getAnalysisStatus(string $jobId): JsonResponse
    {
        $result = cache()->get("analysis_result_{$jobId}");
        
        if (!$result) {
            return response()->json([
                'success' => true,
                'status' => 'processing',
                'message' => 'Analysis in progress'
            ]);
        }
        
        return response()->json([
            'success' => $result['success'],
            'data' => $result['results'] ?? null,
            'error' => $result['error'] ?? null,
            'status' => 'completed'
        ]);
    }

    public function analyzeSingle(Request $request): JsonResponse
    {
        try {
            $feedback = $request->input('feedback');
            
            if (empty($feedback)) {
                return response()->json([
                    'success' => false,
                    'error' => 'No feedback provided'
                ], 400);
            }

            $result = $this->grokService->analyzeFeedback([$feedback]);

            return response()->json([
                'success' => $result['success'],
                'data' => $result['results'] ?? null,
                'error' => $result['error'] ?? null
            ]);

        } catch (\Exception $e) {
            Log::error('Single feedback analysis failed', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Analysis failed: ' . $e->getMessage()
            ], 500);
        }
    }

    public function testConnection(): JsonResponse
    {
        try {
            $result = $this->grokService->testConnection();
            
            return response()->json($result);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
