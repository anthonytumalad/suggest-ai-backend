<?php

namespace App\Services;

use App\Models\Form;
use App\Models\Feedback;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class FeedbackService
{
    public function submitFeedback(string $slug, array $data): Feedback
    {
        $form = Form::where('slug', $slug)->firstOrFail();

        $validated = validator($data, [
            'role'         => 'required|in:student,teacher,staff',
            'rating'       => 'required|integer|between:1,5',
            'feedback'     => 'required|string|min:10',
            'suggestions'  => 'nullable|string',
            'is_anonymous' => 'sometimes|boolean',
        ])->validate();

        $feedback = new Feedback([
            'form_id'      => $form->id,
            'sender_id'    => Auth::check() && !$validated['is_anonymous'] ? Auth::id() : null,
            'role'         => $validated['role'],
            'rating'       => $validated['rating'],
            'feedback'     => $validated['feedback'],
            'suggestions'  => $validated['suggestions'] ?? null,
            'is_anonymous' => $validated['is_anonymous'] ?? false,
        ]);

        $feedback->save();

        return $feedback;
    }


    public function markFeedbackAsRead(Feedback $feedback): Feedback
    {
        $feedback->is_read = true;
        $feedback->save();

        return $feedback;
    }
}
