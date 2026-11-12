<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ Language::getCurrent('dir') }}">
<head>
    <title>
        @hasSection('pagetitle')
            @yield('pagetitle') - Whatsappbulk
        @else
            {{ get_option("website_title") ?: "Whatsappbulk - WhatsApp operations, campaigns, and automation" }}
        @endif
    </title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="keywords" content="{{ get_option("website_keyword", "whatsapp marketing, bulk campaigns, whatsapp chatbot, whatsapp automation, auto reply") }}">
    <meta name="description" content="{{ get_option("website_description", "One WhatsApp workspace for campaigns, replies, bots, contacts, and reports. High-speed bulk sender and marketing automation.") }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/png" href="{{ theme_public_asset('images/whatsappbulklogo.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="{{ theme_public_asset('css/flags/flag-icon.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- Tailwind CSS CDN & Custom Emerald Theme Configuration -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            DEFAULT: '#059669',
                            50: '#ecfdf5',
                            100: '#d1fae5',
                            200: '#a7f3d0',
                            300: '#6ee7b7',
                            400: '#34d399',
                            500: '#10b981',
                            600: '#059669',
                            700: '#047857',
                            800: '#065f46',
                            900: '#064e3b',
                        },
                        emerald: {
                            50: '#ecfdf5',
                            100: '#d1fae5',
                            200: '#a7f3d0',
                            300: '#6ee7b7',
                            400: '#34d399',
                            500: '#10b981',
                            600: '#059669',
                            700: '#047857',
                            800: '#065f46',
                            900: '#064e3b',
                        }
                    },
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'sans-serif'],
                    }
                }
            }
        }
    </script>

    <!-- Theme fallback stylesheet -->
    <link rel="stylesheet" type="text/css" href="{{ theme_public_asset('css/app.css') }}">
    @if(file_exists(public_path('resources/themes/guest/waziper/public/css/tailwind/tailwind.min.css')))
        <link rel="stylesheet" type="text/css" href="{{ theme_public_asset('css/tailwind/tailwind.min.css') }}">
    @endif

    {!! Script::globals() !!}
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.13.3/dist/cdn.min.js" defer></script>
    <style>
        body {
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            color: #0f172a;
            -webkit-font-smoothing: antialiased;
        }
        ::selection {
            background-color: #10b981;
            color: #ffffff;
        }
        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }
        ::-webkit-scrollbar-track {
            background: #f1f5f9;
        }
        ::-webkit-scrollbar-thumb {
            background: #6ee7b7;
            border-radius: 4px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #059669;
        }
    </style>
</head>
<body class="antialiased text-slate-900 sm:overflow-x-hidden selection:bg-[#10b981] selection:text-white" style="background-color: #eafbf3;">
    @if( request()->segment(1) != "auth")
        @include('partials.header')
    @endif

    @yield('content')

    @if( request()->segment(1) != "auth")
        @include('partials.footer')
    @endif

    <script type="text/javascript" src="{{ theme_public_asset('js/jquery.min.js') }}"></script>
    <script type="text/javascript" src="{{ theme_public_asset('js/main.js') }}"></script>
</body>
</html>