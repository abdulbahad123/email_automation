@php
    $languages = Language::getLanguages();
    $currentLang = app()->getLocale();
@endphp

<section class="relative min-h-screen overflow-hidden bg-[#f7fbf8] px-4 py-10">
    <div class="absolute inset-0 pointer-events-none">
        <div class="absolute left-1/2 top-0 h-72 w-72 -translate-x-1/2 rounded-full bg-[#dff6e8] blur-3xl opacity-80"></div>
        <div class="absolute bottom-10 left-10 h-56 w-56 rounded-full bg-[#eef8f1] blur-3xl"></div>
        <div class="absolute right-10 top-24 h-56 w-56 rounded-full bg-[#edf7ff] blur-3xl"></div>
    </div>

    <div class="relative mx-auto flex min-h-[calc(100vh-5rem)] max-w-[560px] flex-col items-center justify-center">
        <div class="mb-10 flex w-full items-start justify-between gap-4">
            <a href="{{ url('') }}" class="inline-flex items-center gap-4">
                <span class="flex h-14 w-14 items-center justify-center rounded-full border border-[#cae8d3] bg-white shadow-[0_12px_30px_rgba(31,122,69,0.08)] text-[#1f7a45]">
                    <i class="fa-brands fa-whatsapp text-2xl"></i>
                </span>
                <div>
                    <div class="text-[2rem] font-bold tracking-tight text-[#16231b]">Whatsappbulk</div>
                    <div class="text-sm text-[#759081]">{{ __("WhatsApp Marketing Tool") }}</div>
                </div>
            </a>

            @if($languages->isNotEmpty())
                <div class="dropdown dropdown-hover dropdown-end">
                    <div tabindex="0" class="flex h-11 items-center gap-2 rounded-full border border-[#d9e9de] bg-white px-4 text-[#22352b] shadow-[0_10px_24px_rgba(15,23,42,0.04)] cursor-pointer">
                        <i class="fa-solid fa-globe"></i>
                        <span class="text-sm font-semibold">{{ strtoupper($currentLang) }}</span>
                        <i class="fa-solid fa-chevron-down text-xs"></i>
                    </div>
                    <ul tabindex="0" class="dropdown-content z-[999] mt-3 menu w-48 rounded-2xl border border-[#dcfce7] bg-white p-3 shadow-xl">
                        @foreach($languages as $language)
                            <li>
                                <a href="{{ url('lang/' . $language->code) }}" class="flex items-center gap-2 rounded-xl {{ $currentLang == $language->code ? 'text-white font-semibold' : 'font-medium text-[#22352b]' }}" style="{{ $currentLang == $language->code ? 'background:#ff5500;' : '' }}">
                                    @if($language->icon)
                                        <span class="size-4 text-center -mt-1"><i class="{{ $language->icon }}"></i></span>
                                    @endif
                                    <span class="truncate">{{ $language->name }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>

        <div class="w-full rounded-[2rem] border border-white/70 bg-white/95 p-10 text-center shadow-[0_30px_80px_rgba(27,67,45,0.10)] backdrop-blur md:p-12">
            <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-full {{ $status ? 'bg-[#effaf2] text-[#1f7a45]' : 'bg-[#fff4f2] text-[#d14343]' }}">
                <i class="fas {{ $status ? 'fa-check-circle' : 'fa-times-circle' }} text-4xl"></i>
            </div>

            <h1 class="font-bold tracking-tight text-[#16231b]" style="margin-top:32px;margin-bottom:16px;font-size:36px;line-height:1.1;">
                {{ $status ? __('Activation Successful') : __('Activation Failed') }}
            </h1>
            <p class="mt-5 text-base leading-8 text-[#698174]">
                {{ $message ?? ($status ? __('Your account has been activated. You can now log in.') : __('The activation link is invalid, expired, or your account was already activated.')) }}
            </p>

            <a href="{{ url('auth/login') }}" style="display:inline-flex;margin-top:32px;height:56px;align-items:center;justify-content:center;border-radius:18px;background:#1f7a45;padding:0 32px;color:#ffffff;font-size:16px;font-weight:600;box-shadow:0 18px 40px rgba(31,122,69,0.22);text-decoration:none;">
                <i class="fa fa-arrow-left mr-2" style="color:#ffffff;"></i><span style="color:#ffffff;">{{ __("Back to Login") }}</span>
            </a>
        </div>

        <p class="mt-10 text-center text-sm text-[#8ea195]">
            {{ __("All rights reserved.") }} &copy; {{ date('Y') }} Whatsappbulk
        </p>
    </div>
</section>
