<section class="relative w-screen min-h-screen flex items-stretch overflow-hidden bg-white overflow-x-hidden">
    @include("partials/login-screen", ["name" => __("Sign In to Your Account")])

    <div class="relative flex flex-col justify-center flex-1 px-6 md:px-8 py-16 bg-blueGray-100 z-10" style="background-image: url({{ theme_public_asset('images/pattern-light-big.svg') }}); background-position: center;">
        <div class="absolute inset-0 pointer-events-none" style="background:
            radial-gradient(circle at 25% 30%, rgba(79,70,229,.08) 0%, rgba(79,70,229,0) 22%),
            radial-gradient(circle at 78% 75%, rgba(59,130,246,.08) 0%, rgba(59,130,246,0) 24%);"></div>
        <div class="relative w-full max-w-lg mx-auto">
            <div class="show-on-mobile mb-6 text-center">
                <a class="inline-block" href="{{ url('') }}">
                    <img class="h-10" src="{{ url( get_option("website_logo_brand_dark", asset('public/img/logo-brand-dark.png')) ) }}" alt="">
                </a>
            </div>
            <form class="actionForm w-full bg-white rounded-3xl border border-white shadow-2xl p-8 md:p-12" action="{{ module_url('do_login') }}" method="POST">
                @csrf
                <div class="mb-10 text-center">
                    <span class="inline-flex items-center px-4 py-2 mb-5 text-xs font-semibold uppercase tracking-widest rounded-full" style="background-color:#eef2ff;color:#4338ca; letter-spacing:0.22em;">
                        {{ __("Secure login") }}
                    </span>
                    <h1 class="mb-4 font-bold font-heading tracking-tight text-gray-900" style="font-size:2.5rem; line-height:1.1;">{{ __("Welcome back") }}</h1>
                    <p class="text-lg leading-8 text-gray-600">{{ __("Sign in to continue managing content, publishing, and campaign performance.") }}</p>
                </div>

                <label class="block mb-5">
                    <p class="mb-2 text-gray-700 font-semibold leading-normal">{{ __("Email or Username") }}</p>
                    <input type="text" id="username" name="username" class="px-4 py-3.5 w-full text-gray-700 font-medium placeholder-gray-400 bg-white outline-none border border-gray-300 rounded-lg focus:ring focus:ring-indigo-300" placeholder="{{ __('Enter username or email address') }}">
                </label>

                <label class="block mb-6">
                    <div class="flex items-center justify-between mb-2 gap-4">
                        <p class="text-gray-700 font-semibold leading-normal">{{ __("Password") }}</p>
                        <a href="{{ url('auth/forgot-password') }}" class="text-indigo-600 hover:text-indigo-700 font-medium">{{ __("Forgot Password?") }}</a>
                    </div>
                    <input id="password" type="password" name="password" class="px-4 py-3.5 w-full text-gray-700 font-medium placeholder-gray-400 bg-white outline-none border border-gray-300 rounded-lg focus:ring focus:ring-indigo-300" placeholder="{{ __('Enter your Password') }}">
                </label>

                <div class="mb-3">
                    {!! Captcha::render(); !!}
                </div>

                <div class="flex items-center mb-6">
                    <input class="w-4 h-4" id="remember" type="checkbox" name="remember" value="1">
                    <label class="ml-2 text-gray-700 font-medium" for="remember">{{ __("Remember Me") }}</label>
                </div>

                <div class="msg-error mb-2"></div>

                <button type="submit" class="mb-8 py-4 px-9 w-full text-white text-lg font-semibold border border-indigo-700 rounded-2xl shadow-4xl focus:ring focus:ring-indigo-300 bg-indigo-600 hover:bg-indigo-700 transition ease-in-out duration-200">
                    {{ __("Sign In") }}
                </button>

                @php
                    $socials = [
                        'google' => [
                            'status' => get_option('auth_google_login_status', 0),
                            'url'    => url('auth/login/google'),
                            'icon'   => '<img src="'.theme_public_asset('images/google.png').'" class="size-6">',
                            'label'  => __("Continue with Google"),
                        ],
                        'facebook' => [
                            'status' => get_option('auth_facebook_login_status', 0),
                            'url'    => url('auth/login/facebook'),
                            'icon'   => '<i class="fa-brands fa-square-facebook text-2xl" style="color:#1877F2;"></i>',
                            'label'  => __("Continue with Facebook"),
                        ],
                        'x' => [
                            'status' => get_option('auth_x_login_status', 0),
                            'url'    => url('auth/login/x'),
                            'icon'   => '<i class="fab fa-x-twitter text-2xl" style="color:#000;"></i>',
                            'label'  => __("Continue with X"),
                        ],
                    ];
                @endphp

                @if(collect($socials)->where('status', 1)->count())
                    <p class="mb-5 text-sm text-gray-500 font-medium text-center">{{ __("Or continue with") }}</p>
                    <div class="flex flex-wrap justify-center -m-2">
                        @foreach($socials as $s)
                            @if($s['status'])
                                <a href="{{ $s['url'] }}" class="flex items-center justify-center p-4 bg-white hover:bg-gray-50 border border-gray-200 rounded-2xl transition ease-in-out duration-200 gap-2 w-full mb-3">
                                    {!! $s['icon'] !!}
                                    <span class="font-semibold leading-normal">{{ $s['label'] }}</span>
                                </a>
                            @endif
                        @endforeach
                    </div>
                @endif

                @if(get_option("auth_signup_page_status", 1))
                    <p class="text-center pt-5 text-gray-600">
                        {{ __("Don't have an account?") }}
                        <a href="{{ url('auth/signup') }}" class="text-indigo-600 hover:text-indigo-700 font-medium">{{ __("Sign up") }}</a>
                    </p>
                @endif
            </form>
        </div>
    </div>
</section>
