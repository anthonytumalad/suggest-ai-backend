<?php

namespace App\Http\Controllers;

use App\Services\FeedbackService;
use App\Models\Form;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FeedbackController extends Controller
{
    protected FeedbackService $feedbackService;

    public function __construct(FeedbackService $feedbackService)
    {
        $this->feedbackService = $feedbackService;
    }

    public function getSummaryForChart(string $slug)
{
    $form = Form::where('slug', $slug)->firstOrFail();

    $summary = $this->feedbackService->getSavedSummary($form);

    if (!$summary) {
        return response()->json([
            'error' => 'No summary available'
        ], 404);
    }

    return response()->json([
        'summary_data'   => $summary->summary_data,
        'feedback_count' => $summary->feedback_count,
        'model'          => $summary->model,
    ]);
}


    public function show(string $slug)
    {
        $form = Form::where('slug', $slug)->firstOrFail();

        return view('feedbackForm', compact('form'));
    }

    public function store(string $slug, Request $request)
    {
    
        try {
            $this->feedbackService->submitFeedback($slug, $request->only([
                'role',
                'rating',
                'feedback',
                'suggestions',
                'is_anonymous',
            ]));

            $form = Form::where('slug', $slug)->firstOrFail();

            return view('feedbackSuccess', compact('form'));

        } catch (ValidationException $e) {
            return back()
                ->withErrors($e->errors())
                ->withInput();
        }
    }

    public function saveSummary(Request $request, string $slug, FeedbackService $feedbackService)
    {
        $form = Form::where('slug', $slug)->firstOrFail();

        $request->validate([
            'summary_data'   => 'required|array',
            'feedback_count' => 'required|integer|min:0',
            'model'          => 'required|string|max:100',
        ]);

        $savedSummary = $this->feedbackService->saveSummary(
            $form,
            $request->input('summary_data'),
            $request->input('feedback_count'),
            $request->input('model')
        );

        return response()->json([
            'message' => 'Summary saved successfully',
            'summary' => $savedSummary,
        ]);
    }

    public function export(Request $request, string $slug, string $format)
    {
        try {
            Log::info("Export started for slug: {$slug}, format: {$format}");

            $form = Form::where('slug', $slug)->firstOrFail();
            Log::info("Form found: ID {$form->id}, Title {$form->title}");

            $feedbackCount = $form->feedbacks()->count();
            Log::info("Feedback count: {$feedbackCount}");

            if ($feedbackCount === 0) {
                Log::warning("No feedback to export for form {$slug}");
                return response()->json(['error' => 'No feedback to export'], 422);
            }

            return match ($format) {
                'csv' => $this->downloadCsv(
                    $form,
                    'feedback_' . $slug . '_' . now()->format('Ymd_His') . '.csv'
                ),
                'excel' => $this->downloadFile(
                    $this->feedbackService->exportAsExcel($form),
                    'temporary',
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
                ),
                'pdf' => $this->downloadFile(
                    $this->feedbackService->exportAsPdf($form),
                    'temporary',
                    'application/pdf'
                ),
                'clipboard' => $this->copyToClipboard($form),
                default => abort(400, 'Invalid format'),
            };
        } catch (\Exception $e) {
            Log::error("Export failed for {$format}: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'slug' => $slug,
            ]);
            return response()->json(['error' => 'Internal server error'], 500);
        }
    }

    private function downloadCsv(Form $form, string $fileName): StreamedResponse
    {
        $csvContent = $this->feedbackService->exportAsCsv($form);

        return response()->streamDownload(function () use ($csvContent) {
            echo $csvContent;
        }, $fileName, [
            'Content-Type' => 'text/csv',
        ]);
    }

    private function downloadFile(string $fileName, string $disk, string $contentType): StreamedResponse
    {
        $path = Storage::disk($disk)->path($fileName);

        return response()->streamDownload(function () use ($path) {
            readfile($path);
        }, $fileName, [
            'Content-Type' => $contentType,
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ], 'attachment');
    }

    private function copyToClipboard(Form $form): \Illuminate\Http\JsonResponse
    {
        $feedbacks = $this->feedbackService->getFeedbackForExport($form);

        $tsv = $feedbacks->map(function ($row) {
            return implode("\t", $row->toArray());
        })->prepend(implode("\t", array_keys($feedbacks->first()->toArray() ?? [])))
            ->implode("\n");

        return response()->json([
            'success' => true,
            'data' => $tsv,
            'message' => 'Data copied to clipboard format (TSV)',
        ]);
    }
}
