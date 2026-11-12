@php
    $captcha = Captcha::render();
    $languages = Language::getLanguages();
    $currentLang = app()->getLocale();
    $socials = [
        'google' => [
            'status' => get_option('auth_google_login_status', 0),
            'url'    => url('auth/login/google'),
            'icon'   => '<img src="'.theme_public_asset('images/google.png').'" class="h-5 w-5">',
            'label'  => 'Google',
        ],
        'facebook' => [
            'status' => get_option('auth_facebook_login_status', 0),
            'url'    => url('auth/login/facebook'),
            'icon'   => '<i class="fa-brands fa-facebook-f text-[#1877F2]"></i>',
            'label'  => 'Facebook',
        ],
        'x' => [
            'status' => get_option('auth_x_login_status', 0),
            'url'    => url('auth/login/x'),
            'icon'   => '<i class="fa-brands fa-x-twitter text-[#111827]"></i>',
            'label'  => 'X',
        ],
    ];
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

        <div class="w-full rounded-[2rem] border border-white/70 bg-white/95 p-10 pb-14 shadow-[0_30px_80px_rgba(27,67,45,0.10)] backdrop-blur md:p-12 md:pb-16">
            <div class="text-center" style="margin-bottom:48px;">
                <span class="inline-flex items-center rounded-full bg-[#effaf2] px-4 py-2 text-xs font-semibold uppercase tracking-[0.2em] text-[#1f7a45]" style="margin-bottom:18px;">
                    {{ __("Secure login") }}
                </span>
                <h1 class="font-bold tracking-tight text-[#16231b]" style="margin-top:0;margin-bottom:16px;font-size:36px;line-height:1.1;">{{ __("Welcome back") }}</h1>
                <p class="text-base leading-8 text-[#698174]" style="margin-top:0;">
                    {{ __("Sign in to manage campaigns, conversations, and automation in one place.") }}
                </p>
            </div>

            <form class="actionForm" action="{{ module_url('do_login') }}" method="POST">
                @csrf
                <div class="space-y-5">
                    <div>
                        <label for="username" class="mb-2 block text-sm font-semibold text-[#2e4337]">{{ __("Email or Username") }}</label>
                        <input type="text" id="username" name="username" class="h-14 w-full rounded-[1.15rem] border border-[#dcebe0] bg-[#fbfdfb] px-4 text-[#16231b] outline-none transition focus:border-[#1f7a45] focus:bg-white" placeholder="{{ __('Enter your email or username') }}">
                    </div>

                    <div>
                        <div class="mb-2 flex items-center justify-between gap-3">
                            <label for="password" class="block text-sm font-semibold text-[#2e4337]">{{ __("Password") }}</label>
                            <a href="{{ url('auth/forgot-password') }}" class="text-sm font-semibold text-[#1f7a45] hover:text-[#176338]">{{ __("Forgot password?") }}</a>
                        </div>
                        <input id="password" type="password" name="password" class="h-14 w-full rounded-[1.15rem] border border-[#dcebe0] bg-[#fbfdfb] px-4 text-[#16231b] outline-none transition focus:border-[#1f7a45] focus:bg-white" placeholder="{{ __('Enter your password') }}">
                    </div>

                    @if($captcha)
                        <div class="pt-1">{!! $captcha !!}</div>
                    @endif

                    <label for="remember" class="inline-flex items-center gap-3 text-sm text-[#5f7568]">
                        <input class="h-4 w-4 rounded border-[#cfe0d4] text-[#1f7a45] focus:ring-[#1f7a45]" id="remember" type="checkbox" name="remember" value="1">
                        <span>{{ __("Remember me") }}</span>
                    </label>
                </div>

                <div style="padding-top:32px;">
                    <div class="msg-error mb-4 min-h-[1px]"></div>
                    <button type="submit" style="display:flex;width:100%;height:56px;align-items:center;justify-content:center;border:none;border-radius:18px;background:#1f7a45;color:#ffffff;font-size:16px;font-weight:600;box-shadow:0 18px 40px rgba(31,122,69,0.22);cursor:pointer;">
                        <span style="color:#ffffff;">{{ __('Login') }}</span>
                    </button>
                </div>

                @if(collect($socials)->where('status', 1)->count())
                    <div class="flex items-center gap-4" style="margin-top:36px;margin-bottom:36px;">
                        <span class="h-px flex-1 bg-[#e5eee8]"></span>
                        <span class="text-sm text-[#7b8d82]">{{ __("or continue with") }}</span>
                        <span class="h-px flex-1 bg-[#e5eee8]"></span>
                    </div>

                    <div class="grid gap-3 {{ collect($socials)->where('status', 1)->count() > 1 ? 'grid-cols-3' : 'grid-cols-1' }}">
                        @foreach($socials as $social)
                            @if($social['status'])
                                <a href="{{ $social['url'] }}" style="display:flex;height:48px;align-items:center;justify-content:center;gap:8px;border:1px solid #dcebe0;border-radius:16px;background:#fbfdfb;color:#22352b;font-size:14px;font-weight:600;">
                                    {!! $social['icon'] !!}
                                    <span style="color:#22352b;">{{ $social['label'] }}</span>
                                </a>
                            @endif
                        @endforeach
                    </div>
                @endif
            </form>

            @if(get_option("auth_signup_page_status", 1))
                <p class="text-center text-sm text-[#66796d]" style="margin-top:28px;">
                    {{ __("Don't have an account?") }}
                    <a href="{{ url('auth/signup') }}" class="font-semibold text-[#1f7a45] hover:text-[#176338]">{{ __("Sign up") }}</a>
                </p>
            @endif
        </div>

        <p class="text-center text-sm text-[#8ea195]" style="margin-top:40px;">
            {{ __("All rights reserved.") }} &copy; {{ date('Y') }} Whatsappbulk
        </p>
    </div>
</section>
