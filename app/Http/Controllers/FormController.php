<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\FormService;
use App\Models\Form;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\Log;

class FormController extends Controller
{
    protected FormService $formService;

    public function __construct(FormService $formService)
    {
        $this->formService = $formService;
    }

    public function index()
    {
        $forms = $this->formService->getAllForms();

        return $forms->map(fn($form) => $this->formatForm($form));
    }

    public function show(string $slug)
    {
        $form = $this->formService->getFormBySlug($slug);

        return $this->formatForm($form);
    }

    public function store(Request $request): JsonResponse
    {
        $data = [
            'title'                        => $request->input('title'),
            'description'                  => $request->input('description'),
            'submission_preference_enabled'=> $request->boolean('submission_preference_enabled'),
            'role_selection_enabled'       => $request->boolean('role_selection_enabled'),
            'rating_enabled'               => $request->boolean('rating_enabled'),
            'suggestions_enabled'         => $request->boolean('suggestions_enabled'),
        ];

        $form = $this->formService->createForm($data);

        return response()->json([
            'message' => 'Form created successfully',
            'form'    => $this->formatForm($form),
        ], 201);
    }

    public function update(Request $request, string $slug): JsonResponse
    {
        $form = $this->formService->getFormBySlug($slug);
        $updatedForm = $this->formService->updateForm($form, $request->all());

        return response()->json([
            'message' => 'Form updated successfully',
            'form'    => $this->formatForm($updatedForm),
        ]);
    }

    public function destroy(string $slug): JsonResponse
    {
        $form = $this->formService->getFormBySlug($slug);
        $this->formService->deleteForm($form);

        return response()->json([
            'message' => 'Form deleted successfully'
        ]);
    }

    private function formatForm(Form $form): array
    {
        return [
            'id'                           => $form->id,
            'title'                        => $form->title,
            'description'                  => $form->description,
            'slug'                         => $form->slug,
            'is_active'                    => $form->is_active,
            'submission_preference_enabled' => $form->submission_preference_enabled,
            'role_selection_enabled'        => $form->role_selection_enabled,
            'rating_enabled'               => $form->rating_enabled,
            'suggestions_enabled'          => $form->suggestions_enabled,
            'created_at'                   => $form->created_at?->toDateTimeString(),
            'updated_at'                   => $form->updated_at?->toDateTimeString(),
            'total_feedbacks'              => (int) ($form->total_feedbacks ?? 0),
            'new_feedbacks'                => (int) ($form->new_feedbacks ?? 0),
        ];
    }

    public function showWithFeedbacks(string $slug)
    {
        try {
            $form = $this->formService->getFormWithFeedbacks($slug);

            $this->formService->markFeedbacksAsRead($form);

            return response()->json([
                'id'                           => $form->id,
                'title'                        => $form->title,
                'description'                  => $form->description ?? null,
                'slug'                         => $form->slug,
                'is_active'                    => $form->is_active,
                'submission_preference_enabled' => $form->submission_preference_enabled,
                'role_selection_enabled'        => $form->role_selection_enabled,
                'rating_enabled'               => $form->rating_enabled,
                'suggestions_enabled'          => $form->suggestions_enabled,
                'created_at'                   => $form->created_at?->toDateTimeString(),
                'updated_at'                   => $form->updated_at?->toDateTimeString(),
                'total_feedbacks'              => (int) ($form->total_feedbacks ?? 0),
                'new_feedbacks'                => 0,
                'feedbacks'                    => $form->feedbacks->map(fn($feedback) => [
                    'id'             => $feedback->id,
                    'feedback'       => $feedback->feedback,
                    'role'           => $feedback->role,
                    'rating'         => $feedback->rating,
                    'suggestions'    => $feedback->suggestions,
                    'is_anonymous'   => $feedback->is_anonymous,
                    'is_read'        => $feedback->is_read,
                    'created_at'     => $feedback->created_at->toDateTimeString(),
                    'sender_email'   => $feedback->is_anonymous ? null : ($feedback->sender?->email ?? 'Unknown'),
                    'sender_avatar'  => $feedback->is_anonymous
                        ? null
                        : ($feedback->sender?->profile_picture
                            ?? 'https://ui-avatars.com/api/?name=' . urlencode($feedback->sender?->email ?? '') . '&background=6366f1&color=fff'),
                ])->toArray(),
            ]);
        } catch (\Exception $e) {
            Log::error('Error loading form with feedbacks: ' . $e->getMessage());

            return response()->json([
                'message' => 'Failed to load form feedbacks',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function qr(string $slug)
    {
        $form = $this->formService->getFormBySlug($slug);

        $qr = QrCode::size(300)->generate(route('feedback.public', $slug));

        return response($qr)->header('Content-type', 'image/svg+xml');
    }
}
