<?php

namespace App\Services;

use App\Models\Form;
use App\Models\Feedback;
use App\Models\FeedbackSummary;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class FeedbackService
{
    public function submitFeedback(string $slug, array $data): Feedback
    {
        $form = Form::where('slug', $slug)->firstOrFail();

        $isLoggedIn = Auth::guard('sender')->check();

        $validated = validator($data, [
            'role'         => 'required|in:student,teacher,staff',
            'rating'       => 'required|integer|between:1,5',
            'feedback'     => 'required|string|min:10',
            'suggestions'  => 'nullable|string',
            'is_anonymous' => 'required|in:0,1,true,false',
        ])->validate();

        $isAnonymous = in_array($validated['is_anonymous'], ['1', 'true', true], true);

        $feedback = Feedback::create([
            'form_id'      => $form->id,
            'sender_id'    => $isLoggedIn && !$isAnonymous
                ? Auth::guard('sender')->id()
                : null,
            'role'         => $validated['role'],
            'rating'       => $validated['rating'],
            'feedback'     => $validated['feedback'],
            'suggestions'  => $validated['suggestions'] ?? null,
            'is_anonymous' => $isAnonymous,
            'is_read'      => false,
        ]);

        return $feedback->fresh();
    }

    public function saveSummary(
        Form $form, 
        array $summaryData, 
        int $feedbackCount,
         string $model = 'gpt-4o-mini'
    ): FeedbackSummary 
    {
        return FeedbackSummary::updateOrCreate(
            ['form_id' => $form->id],
            [
                'feedback_count' => $feedbackCount,
                'model' => $model,
                'summary_data' => $summaryData,
            ]
        );
    }

    public function getSavedSummary(Form $form): ?FeedbackSummary
    {
        return FeedbackSummary::where('form_id', $form->id)->first();
    }

    public function markFeedbackAsRead(Feedback $feedback): Feedback
    {
        $feedback->is_read = true;
        $feedback->save();

        return $feedback;
    }

    public function getFeedbackForExport(Form $form): Collection
    {
        return Feedback::where('form_id', $form->id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($feedback) {
                return [
                    'Date Submitted'   => $feedback->created_at->format('Y-m-d H:i'),
                    'Role'             => ucfirst($feedback->role),
                    'Rating'           => $feedback->rating . '/5',
                    'Feedback'         => $feedback->feedback,
                    'Suggestions'      => $feedback->suggestions ?? '—',
                    'Anonymous'        => $feedback->is_anonymous ? 'Yes' : 'No',
                    'Sender'           => $feedback->is_anonymous
                        ? 'Anonymous'
                        : ($feedback->sender?->name ?? 'Unknown'),
                ];
            });
    }

    public function exportAsCsv(Form $form): string
    {
        $feedbacks = $this->getFeedbackForExport($form);

        $headers = array_keys($feedbacks->first()->toArray());

        $csvContent = implode(',', $headers) . "\n";

        foreach ($feedbacks as $row) {
            $csvContent .= implode(',', array_map(function ($value) {
                return '"' . str_replace('"', '""', $value) . '"';
            }, $row->toArray())) . "\n";
        }

        return $csvContent;
    }

    public function exportAsExcel(Form $form): string
    {
        Log::info("Starting Excel export for form: {$form->slug}");
        $feedbacks = $this->getFeedbackForExport($form);
        Log::info("Fetched {$feedbacks->count()} feedbacks for export");

        $fileName = 'feedback_' . $form->slug . '_' . now()->format('Ymd_His') . '.xlsx';
        Log::info("Generated filename: {$fileName}");

        $export = new \App\Exports\FeedbackExport($feedbacks->toArray(), $form->title);
        Log::info("Created FeedbackExport instance");

        try {
            $stored = Excel::store($export, $fileName, 'temporary');
            Log::info("Excel::store result: " . ($stored ? 'true' : 'false'));

            if (!$stored) {
                throw new \Exception("Failed to store Excel file");
            }

            $fullPath = Storage::disk('temporary')->path($fileName);
            Log::info("Stored file path: {$fullPath}");
            if (!file_exists($fullPath)) {
                throw new \Exception("File not found after storing: {$fullPath}");
            }

            return $fileName;
        } catch (\Exception $e) {
            Log::error("Excel export failed: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            throw $e;
        }
    }

    public function exportAsPdf(Form $form): string
    {
        $feedbacks = $this->getFeedbackForExport($form);

        $fileName = 'feedback_report_' . $form->slug . '_' . now()->format('Ymd_His') . '.pdf';


        $pdf = Pdf::loadView('exports.feedback-pdf', [
            'formTitle' => $form->title,
            'responseCount' => $feedbacks->count(),
            'feedbacks' => $feedbacks->toArray(),
            'exportDate' => now()->format('F j, Y'),
        ]);

        Storage::disk('temporary')->put($fileName, $pdf->output());

        return $fileName;
    }
}
