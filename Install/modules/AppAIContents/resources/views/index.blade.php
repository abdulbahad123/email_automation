@extends('layouts.app')

@section('sub_header')
    <x-sub-header
        title="{{ __('AI Contents') }}"
        description="{{ __('AI tools for efficient and creative content generation') }}"
    >
    </x-sub-header>
@endsection

@section('content')
    {{-- ===== COMING SOON OVERLAY BANNER ===== --}}
    <div style="position:fixed;top:0;left:0;right:0;bottom:0;z-index:9999;background:rgba(15,23,42,0.82);backdrop-filter:blur(4px);display:flex;align-items:center;justify-content:center;padding:20px;" id="comingSoonOverlay">
        <div style="background:#fff;border-radius:24px;max-width:560px;width:100%;padding:48px 40px;text-align:center;box-shadow:0 32px 80px rgba(0,0,0,0.35);position:relative;animation:csSlideUp .4s cubic-bezier(.22,.61,.36,1) both;">
            {{-- Glowing icon --}}
            <div style="width:80px;height:80px;border-radius:20px;background:linear-gradient(135deg,#7c3aed,#4f46e5);display:flex;align-items:center;justify-content:center;margin:0 auto 24px;box-shadow:0 16px 40px rgba(124,58,237,0.35);">
                <svg xmlns="http://www.w3.org/2000/svg" width="38" height="38" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            </div>
            {{-- Badge --}}
            <div style="display:inline-flex;align-items:center;gap:8px;background:linear-gradient(135deg,rgba(124,58,237,.1),rgba(79,70,229,.1));color:#7c3aed;border:1.5px solid rgba(124,58,237,.25);font-size:11px;font-weight:800;padding:6px 18px;border-radius:999px;margin-bottom:20px;text-transform:uppercase;letter-spacing:.08em;">
                <span>🚀</span>
                <span>Coming Soon</span>
            </div>
            {{-- Headline --}}
            <h2 style="font-family:'Inter',sans-serif;font-size:clamp(1.4rem,4vw,1.9rem);font-weight:800;color:#0f172a;margin:0 0 14px;line-height:1.25;">Feature Under Active Development</h2>
            {{-- Subtitle --}}
            <p style="font-size:15px;color:#64748b;line-height:1.7;margin:0 0 32px;max-width:400px;margin-left:auto;margin-right:auto;">We&apos;re putting the final touches on our next-gen AI capabilities. This module will be fully enabled in the upcoming update!</p>
            {{-- Progress bar --}}
            <div style="background:#f1f5f9;border-radius:999px;height:8px;margin:0 0 28px;overflow:hidden;">
                <div style="height:100%;width:78%;background:linear-gradient(90deg,#7c3aed,#4f46e5,#6366f1);border-radius:999px;animation:csProgress 1.8s ease-in-out both;"></div>
            </div>
            <p style="font-size:12px;color:#94a3b8;margin:0 0 28px;font-weight:600;">78% Complete &bull; Launching Soon</p>
            {{-- Back button --}}
            <a href="javascript:history.back()" style="display:inline-flex;align-items:center;gap:8px;background:linear-gradient(135deg,#7c3aed,#4f46e5);color:#fff;font-weight:700;font-size:14px;padding:13px 32px;border-radius:12px;text-decoration:none;box-shadow:0 8px 24px rgba(124,58,237,0.35);transition:transform .2s;" onmouseover="this.style.transform='scale(1.04)'" onmouseout="this.style.transform='scale(1)'">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
                Go Back
            </a>
        </div>
    </div>
    <style>
        @keyframes csSlideUp{from{opacity:0;transform:translateY(40px)}to{opacity:1;transform:translateY(0)}}
        @keyframes csProgress{from{width:0}to{width:78%}}
        @media(max-width:600px){#comingSoonOverlay>div{padding:32px 24px!important}}
    </style>
    {{-- ===== END COMING SOON ===== --}}

    <div class="container px-4 pb-5">
        <div class="d-flex gap-0 max-h-800 min-h-600 hp-100 b-r-6 border border-gray-300 bg-white">
            <div class="ai-cate col d-none d-lg-block border-end">
                <div class="d-flex flex-column flex-fill hp-100">
                    <div class="d-flex justify-content-between align-items-center p-3 border-bottom position-relative zIndex-3">
                        <div class="fs-16 fw-5">{{ __("AI Templates") }}</div>
                        <div class="d-block d-lg-none">
                            <div class="btn btn-icon btn-sm btn-light btn-hover-danger b-r-50 a-rotate closeAICate">
                                <i class="fa-light fa-xmark"></i>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex flex-column flex-column-fluid overflow-y-auto p-3 fs-12 hp-100 position-relative ai-template-data ajax-pages" data-url="{{ module_url("categories") }}" data-resp=".ai-template-data">
                        <div class="w-100 d-flex justify-content-center mt-120 fs-50 text-gray-600">
                            <i class="fa-light fa-loader fa-spin"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="ai-form col d-flex d-lg-block flex-column">
                <div class="d-flex flex-column flex-column-fluid ">
                    <div class="d-flex justify-content-between align-items-center p-3 border-bottom position-relative zIndex-3 d-block d-lg-none">
                        <div class="fs-16 fw-5"><i class="fal fa-lightbulb"></i> {{ __("AI Content Generation") }}</div>

                        <div>
                            <a class="btn btn-outline btn-info btn-sm openAICate" href="javascript:void(0);">
                                <span>{{ __('Templates') }}</span>
                            </a>
                        </div>
                    </div>
                    <form class="p-4 actionForm" action="{{ module_url("process") }}" data-content="ai-result-data" method="POST" data-call-success="AIContent.openResult();">
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label class="form-label">{{ __("Your prompt") }}</label>
                                <textarea class="form-control p-2" rows ="4" name="prompt"></textarea>
                            </div>

                            @include("appaicontents::options", [
                                "hashtags" => true,
                                "total_result" => true,
                            ])

                            <div class="col-md-12">
                                <button type="submit" class="btn btn-dark b-r-50 px-4">{{ __("Generate") }}</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <div class="ai-result col d-none d-lg-block border-start ai-result-data">
                <div class="d-flex flex-column flex-fill hp-100">
                    <div class="d-flex justify-content-between align-items-center p-3 border-bottom position-relative zIndex-3">
                        <div class="fs-16 fw-5">{{ __("Get started") }}</div>
                        <div class="d-block d-lg-none">
                            <div class="btn btn-icon btn-sm btn-light btn-hover-danger b-r-50 a-rotate closeAIResult">
                                <i class="fa-light fa-xmark"></i>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex flex-column flex-column-fluid overflow-y-auto p-3 fs-14 hp-100 position-relative">
                        <div class="mb-3">
                            {{ __("Start by choosing a prompt from the Prompt Templates panel on the left. You can either use the random prompt button or create one manually.") }}
                        </div>
                        <div class="mb-3">
                            {{ __("Craft or modify your prompt to specify the desired AI output. Click the Generate button to start the generation process.") }}
                        </div>
                        <div class="mb-3">
                            {{ __("Results have been generated for your prompt.") }}
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
@endsection
