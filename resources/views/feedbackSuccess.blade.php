@extends('layouts.app')

@section('content')
<div class="flex justify-center items-center p-10">
    <div class="w-full max-w-xl italic">
        <div class="bg-white border border-[#222832]/15 rounded shadow-sm">
            <div class="space-y-6 p-6">
                <h1 class="text-2xl text-[#222831] tracking-normal font-bold">{{ $form->title }}</h1>
                <div class="space-y-3">
                    <h1 class="text-lg font-bold text-[#222831] tracking-normal">
                        Thank You!
                    </h1>
                    <p class="text-lg text-[#545454] tracking-normal">
                        Your feedback has been successfully submitted. We truly appreciate your time and input — it helps us improve!
                    </p>
                </div>
            </div>
            <div class="border-t border-[#222832]/15 bg-gray-50 px-6 py-4">
                <a 
                    href="{{ route('feedback.public', $form->slug) }}" 
                    class="text-sm text-blue-500 hover:underline"
                >
                    Submit Another Feedback
                </a>
            </div>
        </div>
    </div>
</div>
@endsection