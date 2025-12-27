<?php

namespace App\Http\Controllers;

use App\Services\FeedbackService;
use App\Models\Form;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class FeedbackController extends Controller
{
    protected FeedbackService $feedbackService;

    public function __construct(FeedbackService $feedbackService)
    {
        $this->feedbackService = $feedbackService;
    }

    public function show(string $slug)
    {
        $form = Form::where('slug', $slug)->firstOrFail();

        $sender = auth('sender')->user();

        return view('feedbackForm', compact('form', 'sender'));
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

            return redirect()
                ->route('feedback.public', $slug)
                ->with('success', 'Thank you! Your feedback has been submitted successfully.');

        } 
        catch (ValidationException $e) {
            return redirect()
                ->route('feedback.public', $slug)
                ->withErrors($e->errors())
                ->withInput()
                ->with(
                    $e->errors()['feedback'][0] ?? null === 'You have already submitted feedback for this location.'
                        ? 'already_submitted'
                        : null,
                    'You have already submitted feedback for this location. Thank you!'
                );
        }
    }

    public function submitPublicFeedback(string $slug, Request $request)
    {
        $feedback = $this->feedbackService->submitFeedback($slug, $request->all());

        return response()->json([
            'message'  => 'Feedback submitted successfully',
            'feedback' => $feedback,
        ]);
    }
}
