@extends('layouts.app')

@section('content')
<div class="flex justify-center min-h-screen p-10">
    <div class="w-full max-w-xl space-y-3 italic contain-content">

        {{-- Errors --}}
        @if ($errors->any())
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                <strong>Submission failed:</strong>
                <ul class="mt-2 list-disc pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>

                {{-- CSRF/session error --}}
                @if ($errors->has('__csrf'))
                    <p class="mt-2">
                        <strong>Session expired.</strong>
                        Please
                        <a href="{{ route('google.login') }}" class="underline text-blue-600">
                            log in again
                        </a>.
                    </p>
                @endif
            </div>
        @endif

        {{-- Form Info --}}
        <div class="bg-white tracking-normal text-[#222831] border border-[#222832]/15 rounded shadow-sm">
            <div class="p-6 space-y-4">
                <h1 class="text-2xl font-bold">
                    {{ $form->title ?? 'Feedback Form' }}
                </h1>
                <p class="text-base text-[#545454]">
                    {{ $form->description ?? 'No description available.' }}
                </p>
            </div>

            <div class="border-t border-[#222832]/15 bg-gray-50 px-6 py-4 text-sm">
                Logged in as:
                <span class="text-blue-600 font-medium">
                    {{ $sender['email'] ?? 'Unknown User' }}
                </span>
                @if(!empty($sender['name']))
                    ({{ $sender['name'] }})
                @endif
            </div>
        </div>

        {{-- Info --}}
        <div class="bg-white p-6 border border-[#222832]/15 rounded shadow-sm">
            <ul class="list-disc pl-5 text-base space-y-2">
                <li>Only verified school users can access this form.</li>
                <li>You may submit feedback anonymously.</li>
            </ul>
        </div>

        {{-- FORM --}}
        <form
            action="{{ route('feedback.store', $form->slug) }}"
            method="POST"
            class="space-y-6"
        >
            <!-- @csrf -->

            {{-- Submission Preference --}}
            @if(!empty($form->submission_preference_enabled))
                <div class="bg-white p-6 border border-[#222832]/15 rounded shadow-sm">
                    <div class="font-semibold mb-4">
                        Submission Preference <span class="text-red-500">*</span>
                    </div>

                    <label class="flex gap-3 items-center cursor-pointer">
                        <input type="radio" name="is_anonymous" value="1" x-model="isAnonymous" required>
                        <span>Submit anonymously</span>
                    </label>

                    <label class="flex gap-3 items-center cursor-pointer mt-2">
                        <input type="radio" name="is_anonymous" value="0" x-model="isAnonymous">
                        <span>Share my identity</span>
                    </label>
                </div>
            @else
                <input type="hidden" name="is_anonymous" value="1">
            @endif

            {{-- Role --}}
            @if(!empty($form->role_selection_enabled))
                <div class="bg-white p-6 border border-[#222832]/15 rounded shadow-sm">
                    <div class="font-semibold mb-4">
                        Your Role <span class="text-red-500">*</span>
                    </div>

                    @foreach(['student' => 'Student', 'teacher' => 'Teacher', 'staff' => 'Staff'] as $value => $label)
                        <label class="flex gap-3 items-center cursor-pointer mb-2">
                            <input type="radio" name="role" value="{{ $value }}" required>
                            <span>{{ $label }}</span>
                        </label>
                    @endforeach
                </div>
            @endif

            {{-- Rating --}}
            @if(!empty($form->rating_enabled))
                <div class="bg-white p-6 border border-[#222832]/15 rounded shadow-sm">
                    <div class="font-semibold mb-4">
                        Overall Experience <span class="text-red-500">*</span>
                    </div>

                    @foreach([
                        5 => 'Very Positive',
                        4 => 'Positive',
                        3 => 'Neutral',
                        2 => 'Negative',
                        1 => 'Very Negative'
                    ] as $value => $label)
                        <label class="flex gap-3 items-center cursor-pointer mb-2">
                            <input type="radio" name="rating" value="{{ $value }}" required>
                            <span>{{ $label }}</span>
                        </label>
                    @endforeach
                </div>
            @endif

            {{-- Feedback --}}
            <div class="bg-white p-6 border border-[#222832]/15 rounded shadow-sm">
                <label class="font-semibold mb-2 block">
                    Overall Feedback <span class="text-red-500">*</span>
                </label>
                <textarea
                    name="feedback"
                    rows="5"
                    required
                    class="w-full px-4 py-3 bg-gray-100 rounded-lg focus:outline-none"
                    placeholder="Share your feedback..."
                ></textarea>
            </div>

            {{-- Suggestions --}}
            @if(!empty($form->suggestions_enabled))
                <div class="bg-white p-6 border border-[#222832]/15 rounded shadow-sm">
                    <label class="font-semibold mb-2 block">
                        Suggestions (Optional)
                    </label>
                    <textarea
                        name="suggestions"
                        rows="5"
                        class="w-full px-4 py-3 bg-gray-100 rounded-lg focus:outline-none"
                        placeholder="Any suggestions?"
                    ></textarea>
                </div>
            @endif

            {{-- Buttons --}}
            <div class="flex justify-between pt-4">
                <button
                    type="submit"
                    class="bg-amber-500 hover:bg-amber-600 text-white px-8 py-2 rounded-lg text-sm">
                    Submit
                </button>

                <button
                    type="button"
                    @click="clearForm()"
                    class="text-sm text-gray-500 hover:text-amber-600">
                    Clear Form
                </button>
            </div>

        </form>
    </div>
</div>
@endsection
