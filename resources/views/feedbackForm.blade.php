@extends('layouts.app')

@section('content')
<div class="flex justify-center min-h-screen p-10 bg-gray-50">
    <div class="w-full max-w-xl space-y-3 italic contain-content">
        
        <!-- Faculty Info Card -->
        <div class="bg-white tracking-normal text-[#222831] border border-[#222832]/15 rounded shadow-sm">
            <div class="p-6 space-y-4">
                <h1 class="text-2xl font-semibold">{{ $form->title }}</h1>
                <p class="text-base font-normal text-[#545454]">
                    {{ $form->description ?? 'No Description' }}
                </p>
            </div>
            <div class="border-t border-[#222832]/15 bg-gray-50">
                <div class="px-6 py-4">
                    <span class="text-sm tracking-normal text-[#222831]">
                        Logged in as: 
                        <span class="text-blue-600 font-medium">
                            {{ $sender?->email ?? 'Unknown User' }}
                        </span>
                        @if($sender?->name)
                            ({{ $sender->name }})
                        @endif
                    </span>
                </div>
            </div>
        </div>

        <!-- Info List -->
        <div class="bg-white p-6 border border-[#222832]/15 rounded shadow-sm">
            <ul class="list-disc pl-5 tracking-normal text-[#222831] text-base font-normal space-y-2">
                <li>Only verified school users can access this form.</li>
                <li>You may choose to submit your feedback anonymously.</li>
            </ul>
        </div>

        <!-- Form -->
        <form
            action="{{ route('feedback.store', $form->slug) }}"
            method="POST"
            x-data="{
                isAnonymous: true,
                role: '',
                rating: '',
                feedback: '',
                suggestions: '',
                clearForm() {
                    this.isAnonymous = true;
                    this.role = '';
                    this.rating = '';
                    this.feedback = '';
                    this.suggestions = '';
                    document.querySelectorAll('input[type=radio]').forEach(el => el.checked = false);
                    document.querySelectorAll('textarea').forEach(el => el.value = '');
                }
            }"
            class="space-y-6"
            x-cloak>
            @csrf

            <!-- Submission Preference -->
            <div class="bg-white p-6 border border-[#222832]/15 rounded shadow-sm">
                <div class="text-base font-semibold text-[#222831] mb-4">
                    Submission Preference <span class="text-red-500">*</span>
                </div>
                <div class="space-y-4">
                    <label class="flex items-center gap-3 font-normal cursor-pointer">
                        <input type="radio" name="is_anonymous" value="1" x-model="isAnonymous" class="form-radio text-amber-500" />
                        <span>Submit anonymously</span>
                    </label>
                    <label class="flex items-center gap-3 font-normal cursor-pointer">
                        <input type="radio" name="is_anonymous" value="0" x-model="isAnonymous" class="form-radio text-amber-500" />
                        <span>Share my identity (optional)</span>
                    </label>
                </div>
                <p class="text-sm text-[#545454] mt-3">
                    If you choose anonymous, your identity will not be stored.
                </p>
            </div>

            <!-- Role Selection -->
            <div class="bg-white p-6 border border-[#222832]/15 rounded shadow-sm text-base text-[#222831]">
                <div class="font-semibold mb-4">
                    Your Role <span class="text-red-500">*</span>
                </div>
                <div class="space-y-4">
                    @foreach(['student' => 'Student', 'teacher' => 'Teacher', 'staff' => 'Staff'] as $value => $label)
                    <label class="flex items-center gap-3 font-normal cursor-pointer">
                        <input type="radio" name="role" value="{{ $value }}" x-model="role" required class="form-radio text-amber-500" />
                        <span>{{ $label }}</span>
                    </label>
                    @endforeach
                </div>
            </div>

            <!-- Overall Experience Rating -->
            <div class="bg-white p-6 border border-[#222832]/15 rounded shadow-sm">
                <div class="text-base font-semibold text-[#222831] mb-4">
                    Overall Experience <span class="text-red-500">*</span>
                </div>
                <div class="space-y-4">
                    @foreach([
                        5 => 'Very Positive',
                        4 => 'Positive',
                        3 => 'Neutral',
                        2 => 'Negative',
                        1 => 'Very Negative'
                    ] as $value => $label)
                    <label class="flex items-center gap-3 font-normal cursor-pointer">
                        <input type="radio" name="rating" value="{{ $value }}" x-model="rating" required class="form-radio text-amber-500" />
                        <span>{{ $label }}</span>
                    </label>
                    @endforeach
                </div>
            </div>

            <!-- Overall Feedback -->
            <div class="bg-white p-6 border border-[#222832]/15 rounded shadow-sm">
                <label for="feedback" class="block text-base font-semibold text-[#222831] mb-2">
                    Overall Feedback <span class="text-red-500">*</span>
                </label>
                <textarea
                    name="feedback"
                    id="feedback"
                    rows="5"
                    x-model="feedback"
                    required
                    placeholder="Share your detailed feedback here..."
                    class="w-full px-4 py-3 bg-gray-100 rounded-lg focus:outline-none focus:bg-amber-50 focus:ring-2 focus:ring-amber-300 transition"></textarea>
            </div>

            <!-- Suggestions -->
            <div class="bg-white p-6 border border-[#222832]/15 rounded shadow-sm">
                <label for="suggestions" class="block text-base font-semibold text-[#222831] mb-2">
                    Suggestions (Optional)
                </label>
                <textarea
                    name="suggestions"
                    id="suggestions"
                    rows="5"
                    x-model="suggestions"
                    placeholder="Any suggestions for improvement?"
                    class="w-full px-4 py-3 bg-gray-100 rounded-lg focus:outline-none focus:bg-amber-50 focus:ring-2 focus:ring-amber-300 transition"></textarea>
            </div>

            <!-- Submit Buttons -->
            <div class="flex justify-between items-center pt-4">
                <button
                    type="submit"
                    class="bg-amber-500 hover:bg-amber-600 text-white font-medium py-2 px-8 rounded-lg shadow transition">
                    Submit Feedback
                </button>
                <button
                    type="button"
                    @click="clearForm()"
                    class="text-[#545454] hover:text-amber-600 font-medium py-3 px-8 rounded-lg transition">
                    Clear Form
                </button>
            </div>
        </form>

        <div class="text-center mt-16">
            <p class="text-md font-semibold text-[#222831] not-italic">Powered by SuggestAI</p>
        </div>
    </div>
</div>
@endsection