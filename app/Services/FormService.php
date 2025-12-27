<?php

namespace App\Services;

use App\Models\Form;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

class FormService
{
    public function getFormBySlug(string $slug): ?Form
    {
        return Form::where('slug', $slug)
            ->withFeedbackCounts()
            ->firstOrFail();
    }

    public function getFormWithFeedbacks(string $slug): Form
    {
        return Form::where('slug', $slug)
            ->with(['feedbacks' => fn($query) => $query->latest()])
            ->withFeedbackCounts()
            ->firstOrFail();
    }

    public function getAllForms(): Collection
    {
        return Form::withFeedbackCounts()
            ->latest()
            ->get();
    }

    /**
     * Create a new form
     *
     * @param array $data
     * @return Form
     * @throws ValidationException
     */
    public function createForm(array $data): Form
    {
        $validated = validator($data, [
            'title'          => 'required|string|max:255',
            'description'    => 'nullable|string',
            'is_active'      => 'sometimes|boolean',
            'is_read'        => 'sometimes|boolean',
            'allow_anonymous'=> 'sometimes|boolean',
        ])->validate();

        return Form::create($validated);
    }

    /**
     * Update an existing form
     *
     * @param Form $form
     * @param array $data
     * @return Form
     * @throws ValidationException
     */
    public function updateForm(Form $form, array $data): Form
    {
        $validated = validator($data, [
            'title'          => 'sometimes|required|string|max:255',
            'description'    => 'sometimes|nullable|string',
            'slug'           => 'sometimes|required|string|unique:forms,slug,' . $form->id,
            'is_active'      => 'sometimes|boolean',
            'is_read'        => 'sometimes|boolean',
            'allow_anonymous'=> 'sometimes|boolean',
        ])->validate();

        $form->update($validated);

        return $form->refresh();
    }

    public function deleteForm(Form $form): void
    {
        $form->delete();
    }

    public function markFeedbacksAsRead(Form $form): void
    {
        $form->feedbacks()->update(['is_read' => true]);
    }
}
