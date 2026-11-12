<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
@php
    $captcha = Captcha::render();
    $languages = Language::getLanguages();
    $currentLang = app()->getLocale();
    $appDisplayName = trim((string) get_option('website_title', '')) ?: config('app.name', 'WhatsApp Bulk');

    $waNoticeImageRaw = get_option('otp_notice_image', 'assets/whatsappbulklogo.png');

    $waNoticeImg = asset('assets/whatsappbulklogo.png');
    if (!empty($waNoticeImageRaw)) {
        $waNoticeImg = str_starts_with($waNoticeImageRaw, 'http')
            ? $waNoticeImageRaw
            : (str_starts_with($waNoticeImageRaw, 'assets/') || str_starts_with($waNoticeImageRaw, 'uploads/') || str_starts_with($waNoticeImageRaw, 'img/')
                ? asset($waNoticeImageRaw)
                : asset('uploads/otp_notice/' . $waNoticeImageRaw));
    }

    $waNoticeText = get_option('otp_notice_text', 'Check your WhatsApp & Email to receive your verification OTP code');
@endphp

<style>
    /* Reset & Container */
    .wb-signup-wrapper {
        min-height: 100vh;
        background-color: #edf7ea;
        background-image: radial-gradient(at 10% 20%, rgba(34, 197, 94, 0.2) 0px, transparent 50%), radial-gradient(at 90% 80%, rgba(213, 240, 198, 0.6) 0px, transparent 50%);
        padding: 48px 20px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        box-sizing: border-box;
        width: 100%;
    }
    .wb-signup-container {
        width: 100%;
        max-width: 600px;
        margin: 0 auto;
        box-sizing: border-box;
    }
    .wb-signup-card {
        background: #ffffff;
        border-radius: 20px;
        border: 1.5px solid #dcf2e4;
        padding: 36px 32px;
        box-shadow: 0 15px 35px rgba(31, 122, 69, 0.07);
        box-sizing: border-box;
        width: 100%;
    }
    .wb-form-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 16px;
    }
    .wb-input-label {
        display: block;
        font-size: 13px;
        font-weight: 600;
        color: #2e4337;
        margin-bottom: 6px;
    }
    .wb-input {
        width: 100%;
        height: 48px;
        border-radius: 12px;
        border: 1.5px solid #cfe2d6;
        background-color: #fbfdfb;
        padding: 0 14px;
        font-size: 14px;
        color: #16231b;
        outline: none;
        transition: border-color 0.2s, box-shadow 0.2s;
        box-sizing: border-box;
    }
    .wb-input:focus {
        border-color: #1f7a45;
        background-color: #ffffff;
        box-shadow: 0 0 0 3px rgba(31, 122, 69, 0.12);
    }
    .wb-card-section {
        background: #f6fcf8;
        border: 1.5px solid #d7ece0;
        border-radius: 16px;
        padding: 16px 18px;
        margin-bottom: 18px;
        transition: all 0.2s;
        box-sizing: border-box;
    }
    .wb-otp-flex {
        display: flex !important;
        gap: 10px !important;
        align-items: center !important;
        width: 100% !important;
        box-sizing: border-box !important;
    }
    .wb-btn-send {
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        gap: 8px !important;
        height: 48px !important;
        min-height: 48px !important;
        padding: 0 20px !important;
        background: linear-gradient(135deg, #22c55e 0%, #15803d 100%) !important;
        color: #ffffff !important;
        font-size: 14px !important;
        font-weight: 700 !important;
        border-radius: 12px !important;
        border: none !important;
        cursor: pointer !important;
        white-space: nowrap !important;
        box-shadow: 0 4px 12px rgba(31, 122, 69, 0.22) !important;
        transition: all 0.2s ease !important;
        flex-shrink: 0 !important;
        min-width: 120px !important;
        box-sizing: border-box !important;
    }
    .wb-btn-send:hover {
        background: linear-gradient(135deg, #16a34a 0%, #14532d 100%) !important;
        transform: translateY(-1px);
    }
    .wb-btn-send:active {
        transform: translateY(0);
    }
    .wb-btn-send:disabled {
        opacity: 0.65 !important;
        cursor: not-allowed !important;
        transform: none !important;
    }
    .wb-badge-verified {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        background: #dcfce7;
        color: #15803d;
        font-size: 12px;
        font-weight: 700;
        padding: 3px 10px;
        border-radius: 20px;
        border: 1px solid #86efac;
    }
    .wb-btn-submit {
        width: 100% !important;
        height: 50px !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        background: linear-gradient(135deg, #22c55e 0%, #15803d 100%) !important;
        color: #ffffff !important;
        font-size: 15px !important;
        font-weight: 700 !important;
        border-radius: 14px !important;
        border: none !important;
        cursor: pointer !important;
        box-shadow: 0 10px 24px rgba(31, 122, 69, 0.26) !important;
        transition: all 0.2s ease !important;
        box-sizing: border-box !important;
    }
    .wb-btn-submit:hover {
        background: linear-gradient(135deg, #16a34a 0%, #14532d 100%) !important;
        box-shadow: 0 14px 28px rgba(31, 122, 69, 0.32) !important;
    }

    /* Mobile Responsive */
    @media (max-width: 640px) {
        .wb-signup-wrapper {
            padding: 24px 12px !important;
        }
        .wb-signup-card {
            padding: 24px 16px !important;
            border-radius: 16px !important;
        }
        .wb-form-grid {
            grid-template-columns: 1fr !important;
        }
        .wb-otp-flex {
            flex-direction: column !important;
            align-items: stretch !important;
        }
        .wb-btn-send {
            width: 100% !important;
        }
    }
</style>

<div class="wb-signup-wrapper">
    <div class="wb-signup-container">
        <!-- Top branding -->
        <div style="margin-bottom: 24px; display: flex; align-items: center; justify-content: space-between;">
            <a href="{{ url('') }}" style="display: inline-flex; align-items: center; gap: 12px; text-decoration: none;">
                <span style="display: flex; height: 44px; width: 44px; align-items: center; justify-content: center; border-radius: 50%; border: 1.5px solid #cae8d3; background: #ffffff; color: #1f7a45; font-size: 22px; box-shadow: 0 6px 16px rgba(31,122,69,0.1);">
                    <i class="fa-brands fa-whatsapp"></i>
                </span>
                <div>
                    <div style="font-size: 24px; font-weight: 800; color: #16231b; line-height: 1.1; letter-spacing: -0.5px;">Whatsappbulk</div>
                    <div style="font-size: 12px; color: #759081; font-weight: 500;">{{ __("WhatsApp Marketing Tool") }}</div>
                </div>
            </a>

            @if($languages->isNotEmpty())
                <div class="dropdown dropdown-hover dropdown-end" style="position: relative;">
                    <div tabindex="0" style="display: flex; height: 38px; align-items: center; gap: 8px; border-radius: 20px; border: 1px solid #d9e9de; background: #ffffff; padding: 0 14px; color: #22352b; font-size: 13px; font-weight: 600; cursor: pointer; box-shadow: 0 4px 12px rgba(0,0,0,0.03);">
                        <i class="fa-solid fa-globe"></i>
                        <span>{{ strtoupper($currentLang) }}</span>
                        <i class="fa-solid fa-chevron-down" style="font-size: 10px;"></i>
                    </div>
                    <ul tabindex="0" class="dropdown-content" style="position: absolute; right: 0; z-index: 999; margin-top: 8px; min-width: 170px; border-radius: 16px; border: 1px solid #dcfce7; background: #ffffff; padding: 8px; box-shadow: 0 12px 30px rgba(0,0,0,0.1); list-style: none;">
                        @foreach($languages as $language)
                            <li>
                                <a href="{{ url('lang/' . $language->code) }}" style="display: flex; align-items: center; gap: 8px; padding: 8px 12px; border-radius: 10px; text-decoration: none; font-size: 13px; {{ $currentLang == $language->code ? 'background:#1f7a45;color:#ffffff;font-weight:600;' : 'color:#22352b;font-weight:500;' }}">
                                    @if($language->icon)
                                        <span style="font-size: 14px;"><i class="{{ $language->icon }}"></i></span>
                                    @endif
                                    <span>{{ $language->name }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>

        <!-- Main Centered Signup Card -->
        <div class="wb-signup-card">
            <div style="text-align: center; margin-bottom: 24px;">
                <span style="display: inline-block; border-radius: 20px; background: #effaf2; padding: 4px 12px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.16em; color: #1f7a45; margin-bottom: 10px;">
                    {{ __("Create Workspace") }}
                </span>
                <h1 style="margin: 0 0 8px 0; font-size: 26px; font-weight: 800; color: #16231b; letter-spacing: -0.5px;">{{ __("Create your account") }}</h1>
                <p style="margin: 0; font-size: 13px; line-height: 1.5; color: #698174;">
                    {{ __("Sign up with your mobile number and email. Verification required to secure your workspace.") }}
                </p>
            </div>

            <form class="actionForm" id="signup-form" action="{{ module_url('do_signup') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <!-- Full Name & Username -->
                <div class="wb-form-grid" style="margin-bottom: 16px;">
                    <div>
                        <label for="fullname" class="wb-input-label">{{ __("Full Name") }}</label>
                        <input type="text" id="fullname" name="fullname" class="wb-input" placeholder="{{ __('Enter your full name') }}" required>
                    </div>
                    <div>
                        <label for="username" class="wb-input-label">{{ __("Username") }}</label>
                        <input type="text" id="username" name="username" class="wb-input" placeholder="{{ __('Choose a username') }}" required>
                    </div>
                </div>

                <!-- WhatsApp Mobile Number Section -->
                <div class="wb-card-section" style="margin-bottom: 16px;">
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px;">
                        <label for="phone" style="margin: 0; font-size: 13px; font-weight: 700; color: #1f7a45; display: flex; align-items: center; gap: 6px;">
                            <i class="fa-brands fa-whatsapp" style="font-size: 16px;"></i>
                            <span>{{ __("WhatsApp Mobile Number") }} <span style="color: #dc2626;">*</span></span>
                        </label>
                        <span id="wa-verified-badge" class="wb-badge-verified" style="display: none;">
                            <i class="fa-solid fa-circle-check"></i> {{ __("Verified") }}
                        </span>
                    </div>

                    <div style="display: flex; width: 100%; min-width: 0; border: 1.5px solid #cfe2d6; border-radius: 12px; background: #ffffff; overflow: hidden; box-sizing: border-box;">
                        <select id="country_code" name="country_code" style="height: 48px; border: none; border-right: 1px solid #e5e7eb; background: #f9fafb; padding: 0 10px; font-size: 13px; font-weight: 600; outline: none; cursor: pointer; max-width: 135px; flex-shrink: 0;">
                            <option value="+91" selected>🇮🇳 +91 (IN)</option>
                            <option value="+1">🇺🇸 +1 (US)</option>
                            <option value="+44">🇬🇧 +44 (UK)</option>
                            <option value="+971">🇦🇪 +971 (AE)</option>
                            <option value="+966">🇸🇦 +966 (SA)</option>
                            <option value="+65">🇸🇬 +65 (SG)</option>
                            <option value="+60">🇲🇾 +60 (MY)</option>
                            <option value="+61">🇦🇺 +61 (AU)</option>
                            <option value="+49">🇩🇪 +49 (DE)</option>
                            <option value="+33">🇫🇷 +33 (FR)</option>
                            <option value="+92">🇵🇰 +92 (PK)</option>
                            <option value="+880">🇧🇩 +880 (BD)</option>
                            <option value="+977">🇳🇵 +977 (NP)</option>
                            <option value="+94">🇱🇰 +94 (LK)</option>
                            <option value="+62">🇮🇩 +62 (ID)</option>
                            <option value="+63">🇵🇭 +63 (PH)</option>
                            <option value="+86">🇨🇳 +86 (CN)</option>
                            <option value="+81">🇯🇵 +81 (JP)</option>
                            <option value="+55">🇧🇷 +55 (BR)</option>
                            <option value="+27">🇿🇦 +27 (ZA)</option>
                            <option value="+234">🇳🇬 +234 (NG)</option>
                        </select>
                        <input type="tel" id="phone" name="phone" style="height: 48px; border: none; padding: 0 14px; font-size: 14px; width: 100%; min-width: 0; outline: none; background: transparent; box-sizing: border-box; color: #16231b;" placeholder="{{ __('Mobile number (e.g. 9876543210)') }}" required>
                    </div>
                </div>

                <!-- Email Address Section -->
                <div class="wb-card-section" style="margin-bottom: 16px;">
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px;">
                        <label for="email" style="margin: 0; font-size: 13px; font-weight: 700; color: #1f7a45; display: flex; align-items: center; gap: 6px;">
                            <i class="fa-solid fa-envelope" style="font-size: 15px;"></i>
                            <span>{{ __("Email Address") }} <span style="color: #dc2626;">*</span></span>
                        </label>
                        <span id="email-verified-badge" class="wb-badge-verified" style="display: none;">
                            <i class="fa-solid fa-circle-check"></i> {{ __("Verified") }}
                        </span>
                    </div>
                    <input type="email" id="email" name="email" class="wb-input" placeholder="{{ __('Enter your email address') }}" required>
                </div>

                <!-- Send OTP Action Button (Placed directly BELOW Email Address) -->
                <div style="margin-bottom: 16px;">
                    <button type="button" id="btn-send-wa-otp" class="wb-btn-send" style="width: 100% !important; justify-content: center; height: 50px !important;">
                        <i class="fa-solid fa-paper-plane" style="font-size: 15px;"></i>
                        <span>{{ __("Send OTP") }}</span>
                    </button>
                    <div id="wa-otp-msg" style="margin-top: 8px; font-size: 12.5px; font-weight: 600; min-height: 18px; text-align: center;"></div>
                </div>

                <!-- OTP Notice Box (Standardized with Website Builder & Ecom Builder: Left Logo + Right Notice Text) -->
                <div id="wa_otp_notice_badge" style="display: none; margin-bottom: 16px;">
                    <div style="display: flex; align-items: center; gap: 14px; background: #F0FDF4; border: 2px dashed #22C55E; border-radius: 14px; padding: 14px; box-shadow: 0 4px 12px rgba(22, 163, 74, 0.05); text-align: left;">
                        <div style="flex-shrink: 0; width: 52px; height: 52px; display: flex; align-items: center; justify-content: center; background: #ffffff; border-radius: 10px; border: 1px solid #dcfce7; padding: 4px;">
                            <img src="{{ $waNoticeImg }}" alt="Logo" style="max-width: 100%; max-height: 100%; object-fit: contain;">
                        </div>
                        <div style="flex-grow: 1;">
                            <p style="font-size: 13.5px; color: #166534; margin: 0; font-weight: 700; line-height: 1.45;">
                                {{ $waNoticeText }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- OTP Verification Input Box -->
                <div id="wa-otp-box" style="display: none; margin-bottom: 18px; padding: 16px; background: #ffffff; border: 1.5px solid #bbf7d0; border-radius: 14px; box-shadow: 0 4px 14px rgba(31, 122, 69, 0.05);">
                    <div style="font-size: 13px; color: #166534; font-weight: 700; margin-bottom: 8px;">
                        <i class="fa-solid fa-shield-halved" style="margin-right: 4px; color: #16a34a;"></i> {{ __("Enter 6-Digit OTP Code") }} <span style="color: #dc2626;">*</span>
                    </div>
                    <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                        <input type="text" id="wa-otp-input" maxlength="6" style="height: 44px; width: 140px; text-align: center; font-family: monospace; font-size: 18px; font-weight: 800; letter-spacing: 4px; border: 1.5px solid #1f7a45; border-radius: 10px; outline: none; background: #ffffff; color: #16231b;" placeholder="••••••">
                        <button type="button" id="btn-verify-wa-otp" style="height: 44px; padding: 0 20px; background-color: #1f7a45; color: #ffffff !important; border: none; border-radius: 10px; font-size: 13px; font-weight: 700; cursor: pointer; box-shadow: 0 2px 6px rgba(31,122,69,0.2);">
                            <i class="fa-solid fa-circle-check me-1"></i> {{ __("Verify OTP") }}
                        </button>
                        <button type="button" id="btn-resend-wa-otp" style="display: none; background: transparent; border: none; color: #1f7a45; text-decoration: underline; font-size: 12px; font-weight: 600; cursor: pointer;">
                            {{ __("Resend Code") }}
                        </button>
                        <span id="wa-timer" style="font-size: 12px; color: #6b7280; font-family: monospace; font-weight: 600; margin-left: auto;"></span>
                    </div>
                    <div id="wa-otp-verified-banner" style="display: none; margin-top: 12px; padding: 10px 14px; background: #ecfdf5; border: 1px solid #a7f3d0; border-radius: 10px; font-size: 13px; font-weight: 700; color: #065f46;">
                        <i class="fa-solid fa-circle-check text-success me-1"></i> {{ __("OTP Verified successfully! Your account is verified.") }}
                    </div>
                </div>

                <!-- Password & Confirm Password with Eye Toggle -->
                <div class="wb-form-grid" style="margin-bottom: 16px;">
                    <div>
                        <label for="password" class="wb-input-label">{{ __("Password") }}</label>
                        <div style="position: relative; width: 100%;">
                            <input type="password" id="password" name="password" class="wb-input" style="padding-right: 44px;" placeholder="{{ __('Enter your password') }}" required>
                            <button type="button" class="btn-toggle-password" data-target="password" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); background: transparent; border: none; cursor: pointer; color: #759081; font-size: 15px; padding: 6px; display: flex; align-items: center; justify-content: center; outline: none;" tabindex="-1" title="{{ __('Toggle password visibility') }}">
                                <i class="fa-solid fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    <div>
                        <label for="password_confirmation" class="wb-input-label">{{ __("Confirm Password") }}</label>
                        <div style="position: relative; width: 100%;">
                            <input type="password" id="password_confirmation" name="password_confirmation" class="wb-input" style="padding-right: 44px;" placeholder="{{ __('Confirm your password') }}" required>
                            <button type="button" class="btn-toggle-password" data-target="password_confirmation" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); background: transparent; border: none; cursor: pointer; color: #759081; font-size: 15px; padding: 6px; display: flex; align-items: center; justify-content: center; outline: none;" tabindex="-1" title="{{ __('Toggle password visibility') }}">
                                <i class="fa-solid fa-eye"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Timezone -->
                <div style="margin-bottom: 16px;">
                    <label for="timezone" class="wb-input-label">{{ __("Timezone") }}</label>
                    <select id="timezone" name="timezone" class="wb-input" style="cursor: pointer;" required>
                        <option value="">{{ __("Select your timezone") }}</option>
                        @foreach(timezone_identifiers_list() as $tz)
                            <option value="{{ $tz }}" {{ (old('timezone') == $tz || $tz === 'Asia/Kolkata') ? 'selected' : '' }}>{{ $tz }}</option>
                        @endforeach
                    </select>
                </div>

                @if($captcha)
                    <div style="padding-top: 4px; margin-bottom: 14px;">{!! $captcha !!}</div>
                @endif

                @php
                    $planCollection = collect($plans ?? []);
                    
                    // Normalize plans to match landing page names & pricing
                    $mappedPlans = $planCollection->map(function($p) {
                        $pObj = is_array($p) ? (object)$p : clone $p;
                        $name = $pObj->name ?? '';
                        if (empty($name) || stripos($name, 'Waziper Starter') !== false || stripos($name, 'Waziper') !== false) {
                            $pObj->name = 'WhatsApp Bulk Plan';
                        }
                        $rawPrice = (float)($pObj->price ?? 0);
                        if ($rawPrice <= 0 || $rawPrice == 99) {
                            $pObj->price = ($pObj->type == 2) ? 2999 : 350;
                        }
                        return $pObj;
                    });

                    $monthlyPlans = $mappedPlans->where('type', 1)->values();
                    $yearlyPlans = $mappedPlans->where('type', 2)->values();

                    $hasCycleSwitcher = ($monthlyPlans->isNotEmpty() && $yearlyPlans->isNotEmpty());

                    if (!$hasCycleSwitcher) {
                        if ($yearlyPlans->isNotEmpty()) {
                            $activeCycle = 'yearly';
                        } elseif ($monthlyPlans->isNotEmpty()) {
                            $activeCycle = 'monthly';
                        } else {
                            $yearlyPlans = collect([
                                (object)[
                                    'id' => 1,
                                    'id_secure' => 'default_yearly',
                                    'name' => 'WhatsApp Bulk Plan',
                                    'price' => 2999,
                                    'type' => 2,
                                    'free_plan' => 0,
                                    'featured' => 1
                                ]
                            ]);
                            $activeCycle = 'yearly';
                        }
                    } else {
                        $activeCycle = $cycleParam ?? 'yearly';
                    }

                    $targetCollection = ($activeCycle === 'yearly' && $yearlyPlans->isNotEmpty()) ? $yearlyPlans : ($monthlyPlans->isNotEmpty() ? $monthlyPlans : $yearlyPlans);

                    $defaultSelectedPlan = null;
                    if (!empty($planParam)) {
                        $defaultSelectedPlan = $targetCollection->first(function($p) use ($planParam) {
                            return strtolower($p->name) === strtolower($planParam) || $p->id == $planParam || $p->id_secure == $planParam;
                        });
                    }
                    if (!$defaultSelectedPlan) {
                        $defaultSelectedPlan = $targetCollection->firstWhere('featured', 1) ?? $targetCollection->first();
                    }
                    $defaultPlanId = $defaultSelectedPlan ? ($defaultSelectedPlan->id_secure ?: $defaultSelectedPlan->id) : '';
                    $defaultPlanDbId = $defaultSelectedPlan ? $defaultSelectedPlan->id : '';
                    $defaultPlanPrice = $defaultSelectedPlan ? (float)$defaultSelectedPlan->price : (($activeCycle === 'yearly') ? 2999 : 350);
                    $defaultPlanName = $defaultSelectedPlan ? $defaultSelectedPlan->name : 'WhatsApp Bulk Plan';
                    $defaultIsFree = $defaultSelectedPlan ? (!empty($defaultSelectedPlan->free_plan) || $defaultPlanPrice == 0) : false;
                @endphp
                <div class="wb-card-section" style="margin-bottom: 20px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; flex-wrap: wrap; gap: 8px;">
                        <label style="margin: 0; font-size: 13px; font-weight: 700; color: #1f7a45; display: flex; align-items: center; gap: 6px;">
                            <i class="fa-solid fa-crown" style="font-size: 15px;"></i>
                            <span>{{ __("Select Membership Plan") }}</span>
                        </label>
                        <span id="selected-plan-badge" style="background: #e8f5e9; color: #1b5e20; font-size: 12px; font-weight: 800; padding: 4px 12px; border-radius: 20px; border: 1px solid #bbf7d0;">
                            {{ $defaultPlanName }} • ₹{{ $defaultPlanPrice }} / {{ $activeCycle === 'monthly' ? 'month' : 'year' }}
                        </span>
                    </div>

                    @if(!empty($hasCycleSwitcher))
                    <!-- Recurring Billing Cycle Switcher (Monthly vs Yearly) -->
                    <div style="display: flex; justify-content: center; margin-bottom: 16px;">
                        <div style="display: inline-flex; background: #f0fdf4; border: 1.5px solid #bbf7d0; border-radius: 9999px; padding: 4px; gap: 4px; box-shadow: 0 1px 3px rgba(0,0,0,0.04);">
                            <button type="button" id="btn-cycle-monthly" onclick="window.switchCycle('monthly')" style="border: none; border-radius: 9999px; padding: 6px 18px; font-size: 12px; font-weight: 700; cursor: pointer; transition: all 0.2s ease; background: {{ $activeCycle === 'monthly' ? '#1f7a45' : 'transparent' }}; color: {{ $activeCycle === 'monthly' ? '#ffffff' : '#374151' }};">
                                {{ __("Monthly") }}
                            </button>
                            <button type="button" id="btn-cycle-yearly" onclick="window.switchCycle('yearly')" style="border: none; border-radius: 9999px; padding: 6px 18px; font-size: 12px; font-weight: 700; cursor: pointer; transition: all 0.2s ease; background: {{ $activeCycle === 'yearly' ? '#1f7a45' : 'transparent' }}; color: {{ $activeCycle === 'yearly' ? '#ffffff' : '#374151' }}; display: flex; align-items: center; gap: 6px;">
                                <span>{{ __("Yearly") }}</span>
                                
                            </button>
                        </div>
                    </div>
                    @endif

                    <!-- Hidden inputs for selected plan & payment details -->
                    <input type="hidden" name="plan" id="input_selected_plan" value="{{ $defaultPlanId }}">
                    <input type="hidden" name="plan_id" id="input_selected_plan_id" value="{{ $defaultPlanDbId }}">
                    <input type="hidden" name="plan_price" id="input_selected_price" value="{{ $defaultPlanPrice }}">
                    <input type="hidden" name="plan_name" id="input_selected_plan_name" value="{{ $defaultPlanName }}">
                    <input type="hidden" name="billing_cycle" id="input_billing_cycle" value="{{ $activeCycle }}">
                    <input type="hidden" name="payment_method" id="input_payment_method" value="upi">
                    <input type="hidden" name="razorpay_payment_id" id="hidden_razorpay_payment_id" value="">
                    <input type="hidden" name="razorpay_order_id" id="hidden_razorpay_order_id" value="">
                    <input type="hidden" name="razorpay_signature" id="hidden_razorpay_signature" value="">

                    <!-- Monthly Plans Grid -->
                    <div id="grid-plans-monthly" style="display: {{ $activeCycle === 'monthly' ? 'grid' : 'none' }}; grid-template-columns: repeat({{ count($monthlyPlans) > 0 ? count($monthlyPlans) : 2 }}, 1fr); gap: 10px;">
                        @forelse($monthlyPlans as $p)
                            @php
                                $pPrice = (float)$p->price;
                                $isFree = !empty($p->free_plan) || $pPrice == 0;
                                $planIdentifier = $p->id_secure ?: ($p->id ?: strtolower($p->name));
                                $isFeatured = !empty($p->featured);
                            @endphp
                            <div class="plan-option-card plan-monthly" 
                                 id="card-plan-monthly-{{ $planIdentifier }}"
                                 data-plan="{{ $planIdentifier }}" 
                                 data-id="{{ $p->id }}"
                                 data-price="{{ $pPrice }}" 
                                 data-name="{{ $p->name }}" 
                                 data-cycle="monthly"
                                 data-free="{{ $isFree ? '1' : '0' }}" 
                                 onclick="window.selectPlanCard(this)"
                                 style="position: relative; border: 2px solid #e5e7eb; border-radius: 14px; padding: 14px 10px; cursor: pointer; text-align: center; background: #ffffff; transition: all 0.2s ease;">
                                @if($isFeatured)
                                    <span style="position: absolute; top: -10px; left: 50%; transform: translateX(-50%); background: #1f7a45; color: #ffffff; font-size: 9px; font-weight: 800; padding: 2px 8px; border-radius: 10px; text-transform: uppercase; letter-spacing: 0.5px; white-space: nowrap;">Best Value</span>
                                @endif
                                <div style="font-size: 12px; font-weight: 700; text-transform: uppercase; color: {{ $isFree ? '#6b7280' : ($isFeatured ? '#1f7a45' : '#ff5500') }}; margin-bottom: 4px;">{{ $p->name }}</div>
                                <div style="font-size: 22px; font-weight: 900; color: #111827; line-height: 1.1;">₹{{ $pPrice }}</div>
                                <div style="font-size: 11px; color: #9ca3af; margin-top: 4px;">{{ $isFree ? 'Free / Month' : 'Billed Monthly' }}</div>
                            </div>
                        @empty
                            <div class="text-center text-muted py-3" style="grid-column: 1/-1;">{{ __('No monthly plans currently available.') }}</div>
                        @endforelse
                    </div>

                    <!-- Yearly Plans Grid -->
                    <div id="grid-plans-yearly" style="display: {{ $activeCycle === 'yearly' ? 'grid' : 'none' }}; grid-template-columns: repeat({{ count($yearlyPlans) > 0 ? count($yearlyPlans) : 2 }}, 1fr); gap: 10px;">
                        @forelse($yearlyPlans as $p)
                            @php
                                $pPrice = (float)$p->price;
                                $isFree = !empty($p->free_plan) || $pPrice == 0;
                                $planIdentifier = $p->id_secure ?: ($p->id ?: strtolower($p->name));
                                $isFeatured = !empty($p->featured);
                            @endphp
                            <div class="plan-option-card plan-yearly" 
                                 id="card-plan-yearly-{{ $planIdentifier }}"
                                 data-plan="{{ $planIdentifier }}" 
                                 data-id="{{ $p->id }}"
                                 data-price="{{ $pPrice }}" 
                                 data-name="{{ $p->name }}" 
                                 data-cycle="yearly"
                                 data-free="{{ $isFree ? '1' : '0' }}" 
                                 onclick="window.selectPlanCard(this)"
                                 style="position: relative; border: 2px solid #e5e7eb; border-radius: 14px; padding: 14px 10px; cursor: pointer; text-align: center; background: #ffffff; transition: all 0.2s ease;">
                                @if($isFeatured)
                                    <span style="position: absolute; top: -10px; left: 50%; transform: translateX(-50%); background: #1f7a45; color: #ffffff; font-size: 9px; font-weight: 800; padding: 2px 8px; border-radius: 10px; text-transform: uppercase; letter-spacing: 0.5px; white-space: nowrap;">Best Value</span>
                                @endif
                                <div style="font-size: 12px; font-weight: 700; text-transform: uppercase; color: {{ $isFree ? '#6b7280' : ($isFeatured ? '#1f7a45' : '#ff5500') }}; margin-bottom: 4px;">{{ $p->name }}</div>
                                <div style="font-size: 22px; font-weight: 900; color: #111827; line-height: 1.1;">₹{{ $pPrice }}</div>
                                <div style="font-size: 11px; color: #9ca3af; margin-top: 4px;">{{ $isFree ? 'Free / Year' : 'Billed Yearly' }}</div>
                            </div>
                        @empty
                            <div class="text-center text-muted py-3" style="grid-column: 1/-1;">{{ __('No yearly plans currently available.') }}</div>
                        @endforelse
                    </div>
                </div>

                <!-- ════════════════════════════════════════════════════════════════ -->
                <!-- Payment Gateway Section (Only shown for Paid Plans: ₹39 / ₹89) -->
                <!-- ════════════════════════════════════════════════════════════════ -->
                <div id="payment-gateway-section" style="margin-bottom: 20px; border: 1.5px solid #bbf7d0; background: #fcfdfd; border-radius: 18px; padding: 18px;">
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px;">
                        <span style="font-size: 13px; font-weight: 800; color: #166534; text-transform: uppercase; letter-spacing: 0.5px;">
                            <i class="fa-solid fa-credit-card me-1"></i> {{ __("Payment Method") }}
                        </span>
                        <span id="payment-amount-display" style="font-size: 14px; font-weight: 900; color: #1f7a45;">₹{{ number_format($defaultPlanPrice, 2) }}</span>
                    </div>

                    <!-- Payment Method Tabs (UPI QR vs Razorpay) -->
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-bottom: 16px;">
                        <button type="button" id="tab-pay-upi" class="btn-pay-tab active" data-tab="upi" onclick="window.switchPayTab('upi')" style="padding: 10px 12px; border-radius: 12px; border: 2px solid #1f7a45; background: #1f7a45; color: #ffffff; font-size: 13px; font-weight: 700; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px; transition: all 0.2s ease;">
                            <i class="fa-solid fa-qrcode"></i> <span>UPI / QR Code</span>
                        </button>
                        <button type="button" id="tab-pay-razorpay" class="btn-pay-tab" data-tab="razorpay" onclick="window.switchPayTab('razorpay')" style="padding: 10px 12px; border-radius: 12px; border: 2px solid #e5e7eb; background: #ffffff; color: #374151; font-size: 13px; font-weight: 700; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px; transition: all 0.2s ease;">
                            <i class="fa-solid fa-credit-card"></i> <span>Razorpay Online</span>
                        </button>
                    </div>

                    <!-- 1. UPI Payment Box (Identical to Website Builder) -->
                    <div id="box-method-upi" style="display: block; text-align: center; background: #ffffff; border: 1px solid #d1fae5; border-radius: 14px; padding: 16px;">
                        <span style="display: inline-block; background: #dcfce7; color: #15803d; font-size: 11px; font-weight: 800; padding: 3px 10px; border-radius: 12px; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px;">
                            Scan & Pay via Any UPI App
                        </span>
                        <h6 style="margin: 0 0 10px 0; font-size: 15px; font-weight: 800; color: #111827;">Whatsappbulk Subscription</h6>

                        @php
                            $dynamicInitPrice = (float)($defaultPlanPrice > 0 ? $defaultPlanPrice : 99);
                            $dynamicInitPriceFormatted = number_format($dynamicInitPrice, 2, '.', '');
                            $dynamicInitName = $defaultPlanName ?: 'Subscription';
                            $resolvedUpi = !empty($upiId) ? $upiId : env('PAYMENT_UPI_ID', '');
                            $dynamicInitUpiUrl = 'upi://pay?pa=' . $resolvedUpi . '&pn=' . urlencode($appDisplayName) . '&am=' . $dynamicInitPriceFormatted . '&cu=INR&tn=' . urlencode($appDisplayName . ' ' . $dynamicInitName . ' Subscription');
                        @endphp
                        <!-- Dynamic QR Code Container -->
                        <div style="margin: 8px auto 12px auto; display: inline-block; padding: 8px; background: #ffffff; border: 1.5px solid #e5e7eb; border-radius: 14px; box-shadow: 0 4px 12px rgba(0,0,0,0.04);">
                            <img id="dynamic-upi-qr" src="https://api.qrserver.com/v1/create-qr-code/?size=240x240&data={{ urlencode($dynamicInitUpiUrl) }}" alt="UPI QR Code" style="width: 190px; height: 190px; object-fit: contain; display: block;">
                            <div style="margin-top: 8px; text-align: center;">
                                <span id="upi-amount-badge" style="display: inline-block; background: #e8f5e9; color: #1b5e20; font-size: 13px; font-weight: 800; padding: 4px 14px; border-radius: 14px;">
                                    Amount to Pay: ₹{{ $dynamicInitPrice }}
                                </span>
                            </div>
                            <div style="margin-top: 8px; text-align: center;">
                                <button type="button" class="btn btn-sm" onclick="window.downloadUpiQr()" style="display: inline-flex; align-items: center; gap: 6px; padding: 5px 14px; font-size: 12px; font-weight: 700; color: #15803d; background: #dcfce7; border: 1px solid #86efac; border-radius: 20px; cursor: pointer; transition: all 0.2s ease;">
                                    <i class="fa-solid fa-download"></i> {{ __("Download QR Code") }}
                                </button>
                            </div>
                        </div>

                        <!-- UPI ID Display with Copy Button -->
                        <div style="display: flex; align-items: center; justify-content: center; gap: 8px; margin-bottom: 12px;">
                            <span style="font-size: 12px; font-weight: 700; color: #6b7280;">UPI ID:</span>
                            <code style="font-family: monospace; font-size: 14px; font-weight: 800; color: #111827; background: #f3f4f6; padding: 4px 8px; border-radius: 6px; border: 1px solid #e5e7eb;" id="text-upi-vpa">{{ $resolvedUpi ?: __('Configure in Admin') }}</code>
                            <button type="button" id="btn-copy-upi" onclick="window.copyUpiId()" style="padding: 4px 10px; font-size: 12px; font-weight: 700; color: #1f7a45; background: #ffffff; border: 1px solid #1f7a45; border-radius: 14px; cursor: pointer;">
                                <i class="fa-regular fa-copy me-1"></i> {{ __("Copy") }}
                            </button>
                        </div>

                        <p style="margin: 0 0 14px 0; font-size: 12px; color: #6b7280; line-height: 1.4;">
                            {{ __("Scan QR with PhonePe, Google Pay, Paytm, or BHIM, or download the QR code to pay. Upload payment screenshot below:") }}
                        </p>

                        <!-- Payment Proof Upload (Screenshot / Document / Receipt) -->
                        <div style="text-align: left; margin-bottom: 12px;">
                            <label style="font-size: 12px; font-weight: 700; color: #374151; margin-bottom: 4px; display: flex; align-items: center; justify-content: space-between;">
                                <span><i class="fa-solid fa-receipt text-success me-1"></i> {{ __("Payment Proof / Screenshot") }}</span>
                                <span style="font-size: 11px; font-weight: 600; color: #16a34a;">{{ __("Image or PDF") }}</span>
                            </label>
                            <div id="proof-dropzone" style="position: relative; border: 1.5px dashed #86efac; border-radius: 12px; padding: 12px 14px; background: #f0fdf4; text-align: center; cursor: pointer; transition: all 0.2s ease;" onclick="document.getElementById('payment_proof').click()">
                                <input type="file" id="payment_proof" name="payment_proof" accept="image/png,image/jpeg,image/jpg,image/webp,application/pdf" style="display: none;" onchange="window.handleProofSelect(this)">
                                <div id="proof-default-state">
                                    <i class="fa-solid fa-cloud-arrow-up text-success fs-20 mb-1 d-block"></i>
                                    <div style="font-size: 12px; font-weight: 700; color: #15803d;">{{ __("Click to upload payment screenshot or document") }}</div>
                                    <div style="font-size: 11px; color: #64748b; margin-top: 2px;">{{ __("JPG, PNG, WEBP or PDF (up to 5MB)") }}</div>
                                </div>
                                <div id="proof-preview-state" style="display: none; align-items: center; justify-content: center; gap: 8px;">
                                    <i class="fa-solid fa-file-circle-check text-success fs-18"></i>
                                    <span id="proof-preview-filename" style="font-size: 12px; font-weight: 700; color: #15803d; max-width: 220px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"></span>
                                    <button type="button" class="btn btn-sm text-danger p-0 ms-1" onclick="window.clearProofSelect(event)" title="{{ __('Remove') }}"><i class="fa-solid fa-circle-xmark fs-16"></i></button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 2. Razorpay Payment Box -->
                    <div id="box-method-razorpay" style="display: none; text-align: center; background: #ffffff; border: 1.5px solid #bfdbfe; border-radius: 14px; padding: 20px;">
                        <span style="display: inline-block; background: #dbeafe; color: #1d4ed8; font-size: 11px; font-weight: 800; padding: 3px 10px; border-radius: 12px; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px;">
                            Online Instant Checkout
                        </span>
                        <h6 style="margin: 0 0 8px 0; font-size: 15px; font-weight: 800; color: #111827;">Pay via Razorpay Secure Gateway</h6>
                        <p style="margin: 0 0 14px 0; font-size: 12px; color: #6b7280; line-height: 1.5;">
                            {{ __("Supports Credit/Debit Cards, NetBanking, UPI, and Digital Wallets.") }}
                        </p>
                        <div style="padding: 12px; background: #eff6ff; border-radius: 10px; border: 1px dashed #93c5fd; font-size: 12px; color: #1e40af; font-weight: 600;">
                            <i class="fa-solid fa-shield-halved me-1"></i> {{ __("Instant activation via secure Razorpay checkout.") }}
                        </div>
                    </div>
                </div>

                <!-- Terms -->
                <label for="accep_terms" style="display: flex; align-items: flex-start; gap: 8px; font-size: 13px; color: #5f7568; cursor: pointer; margin-bottom: 20px;">
                    <input id="accep_terms" name="accep_terms" type="checkbox" value="1" required style="margin-top: 3px; accent-color: #1f7a45; height: 16px; width: 16px;">
                    <span>
                        {{ __("I agree to the") }}
                        <a style="font-weight: 600; color: #1f7a45; text-decoration: underline;" href="{{ url('terms-of-service') }}" target="_blank">{{ __("Terms & Conditions") }}</a>
                    </span>
                </label>

                <!-- Submit Button -->
                <div>
                    <div class="msg-error" style="margin-bottom: 12px; min-height: 1px;"></div>
                    <button type="submit" id="btn-submit-signup" class="wb-btn-submit">
                        <span style="color: #ffffff;">{{ __('Complete Sign Up') }}</span>
                    </button>
                </div>
            </form>

            <p style="text-align: center; font-size: 13px; color: #66796d; margin: 20px 0 0 0;">
                {{ __("Already have an account?") }}
                <a href="{{ url('auth/login') }}" style="font-weight: 700; color: #1f7a45; text-decoration: none;">{{ __("Sign in") }}</a>
            </p>
        </div>

        <p style="text-align: center; font-size: 12px; color: #8ea195; margin-top: 24px;">
            {{ __("All rights reserved.") }} &copy; {{ date('Y') }} Whatsappbulk
        </p>
    </div>
</div>

<script type="text/javascript">
// ─────────────────────────────────────────────────────────────
// Global Plan, Billing Cycle & Payment State and Helper Functions
// ─────────────────────────────────────────────────────────────
window.currentCycle = '{{ $activeCycle ?? "yearly" }}';
window.currentPrice = {{ (float)($defaultPlanPrice > 0 ? $defaultPlanPrice : 99) }};
window.currentPlan = '{{ $defaultPlanId ?? "starter" }}';
window.currentMethod = 'upi';
window.upiVpa = "{{ $resolvedUpi }}";

window.updateSubmitButtonText = function() {
    const btnSubmit = document.getElementById('btn-submit-signup');
    if (!btnSubmit) return;
    if (window.currentPrice === 0) {
        btnSubmit.innerHTML = '<span style="color:#ffffff;display:inline-flex;align-items:center;gap:8px;">{{ __("Complete Free Sign Up") }} <i class="fa-solid fa-arrow-right"></i></span>';
    } else if (window.currentMethod === 'upi') {
        btnSubmit.innerHTML = '<span style="color:#ffffff;display:inline-flex;align-items:center;gap:8px;">{{ __("Complete Sign Up with UPI") }} (₹' + window.currentPrice + ') <i class="fa-solid fa-check"></i></span>';
    } else {
        btnSubmit.innerHTML = '<span style="color:#ffffff;display:inline-flex;align-items:center;gap:8px;"><i class="fa-solid fa-credit-card"></i> {{ __("Proceed to Razorpay Checkout") }} (₹' + window.currentPrice + ') <i class="fa-solid fa-arrow-right"></i></span>';
    }
};

window.switchCycle = function(cycle) {
    window.currentCycle = cycle || 'yearly';
    const inputCycle = document.getElementById('input_billing_cycle');
    if (inputCycle) inputCycle.value = window.currentCycle;

    const btnMonthly = document.getElementById('btn-cycle-monthly');
    const btnYearly = document.getElementById('btn-cycle-yearly');
    const badgeSave = document.getElementById('badge-yearly-save');
    const gridMonthly = document.getElementById('grid-plans-monthly');
    const gridYearly = document.getElementById('grid-plans-yearly');

    if (window.currentCycle === 'monthly' && !document.querySelector('#grid-plans-monthly .plan-option-card') && document.querySelector('#grid-plans-yearly .plan-option-card')) { window.currentCycle = 'yearly'; if (inputCycle) inputCycle.value = 'yearly'; }
    if (window.currentCycle === 'yearly' && !document.querySelector('#grid-plans-yearly .plan-option-card') && document.querySelector('#grid-plans-monthly .plan-option-card')) { window.currentCycle = 'monthly'; if (inputCycle) inputCycle.value = 'monthly'; }
    if (window.currentCycle === 'monthly') {
        if (btnMonthly) {
            btnMonthly.style.background = '#1f7a45';
            btnMonthly.style.color = '#ffffff';
            btnMonthly.style.boxShadow = '0 2px 6px rgba(31,122,69,0.2)';
        }
        if (btnYearly) {
            btnYearly.style.background = 'transparent';
            btnYearly.style.color = '#374151';
            btnYearly.style.boxShadow = 'none';
        }
        if (badgeSave) {
            badgeSave.style.background = '#dcfce7';
            badgeSave.style.color = '#15803d';
        }
        if (gridMonthly) gridMonthly.style.display = 'grid';
        if (gridYearly) gridYearly.style.display = 'none';

        const activeYearlyCard = document.querySelector('#grid-plans-yearly .plan-option-card.active');
        const planKey = activeYearlyCard ? (activeYearlyCard.getAttribute('data-name') || activeYearlyCard.getAttribute('data-plan')) : window.currentPlan;
        window.selectPlan(planKey, 'monthly');
    } else {
        if (btnYearly) {
            btnYearly.style.background = '#1f7a45';
            btnYearly.style.color = '#ffffff';
            btnYearly.style.boxShadow = '0 2px 6px rgba(31,122,69,0.2)';
        }
        if (btnMonthly) {
            btnMonthly.style.background = 'transparent';
            btnMonthly.style.color = '#374151';
            btnMonthly.style.boxShadow = 'none';
        }
        if (badgeSave) {
            badgeSave.style.background = '#ffffff';
            badgeSave.style.color = '#1f7a45';
        }
        if (gridMonthly) gridMonthly.style.display = 'none';
        if (gridYearly) gridYearly.style.display = 'grid';

        const activeMonthlyCard = document.querySelector('#grid-plans-monthly .plan-option-card.active');
        const planKey = activeMonthlyCard ? (activeMonthlyCard.getAttribute('data-name') || activeMonthlyCard.getAttribute('data-plan')) : window.currentPlan;
        window.selectPlan(planKey, 'yearly');
    }
};

window.selectPlanCard = function(card) {
    if (!card) return;
    const planIdentifier = card.getAttribute('data-plan');
    const cycle = card.getAttribute('data-cycle') || window.currentCycle;
    window.selectPlan(planIdentifier, cycle);
};

window.selectPlan = function(planKey, cycle) {
    cycle = cycle || window.currentCycle || 'yearly';
    planKey = (planKey || 'premium').toString().toLowerCase().trim();

    const currentGridId = (cycle === 'monthly') ? 'grid-plans-monthly' : 'grid-plans-yearly';
    const grid = document.getElementById(currentGridId);
    const planCards = grid ? grid.querySelectorAll('.plan-option-card') : document.querySelectorAll('.plan-option-card');

    let matchedCard = null;
    planCards.forEach(card => {
        const cardPlan = (card.getAttribute('data-plan') || '').toLowerCase();
        const cardName = (card.getAttribute('data-name') || '').toLowerCase();
        if (cardPlan === planKey || 
            (planKey === '0' && cardPlan.includes('start')) ||
            (planKey === '39' && cardPlan.includes('stand')) ||
            (planKey === '89' && cardPlan.includes('prem')) ||
            cardName.includes(planKey) || 
            (planKey.includes('prem') && (cardName.includes('prem') || cardPlan.includes('prem'))) || 
            (planKey.includes('stand') && (cardName.includes('stand') || cardPlan.includes('stand'))) || 
            (planKey.includes('start') && (cardName.includes('start') || cardPlan.includes('start'))) || 
            (planKey.includes('free') && (cardName.includes('start') || cardPlan.includes('start')))) {
            matchedCard = card;
        }
    });

    if (!matchedCard && planCards.length > 0) {
        matchedCard = planCards[planCards.length - 1]; // Default to highest/featured
    }

    if (!matchedCard) return;

    // Reset styles on all cards in both grids
    document.querySelectorAll('.plan-option-card').forEach(c => {
        c.classList.remove('active');
        c.style.borderColor = '#e5e7eb';
        c.style.background = '#ffffff';
        c.style.boxShadow = 'none';
    });

    const plan = matchedCard.getAttribute('data-plan') || 'starter';
    const price = parseFloat(matchedCard.getAttribute('data-price')) || 0;
    const name = matchedCard.getAttribute('data-name') || ('Whatsappbulk ' + plan.toUpperCase());

    window.currentPrice = price;
    window.currentPlan = plan;

    // Highlight selected card
    matchedCard.classList.add('active');
    matchedCard.style.borderColor = '#1f7a45';
    matchedCard.style.background = '#f0fdf4';
    matchedCard.style.boxShadow = '0 4px 14px rgba(31, 122, 69, 0.15)';

    const inputPlan = document.getElementById('input_selected_plan');
    const inputPlanId = document.getElementById('input_selected_plan_id');
    const inputPrice = document.getElementById('input_selected_price');
    const inputPlanName = document.getElementById('input_selected_plan_name');
    const inputCycle = document.getElementById('input_billing_cycle');
    const inputPaymentMethod = document.getElementById('input_payment_method');
    const planBadge = document.getElementById('selected-plan-badge');
    const paymentGatewaySection = document.getElementById('payment-gateway-section');
    const paymentAmountDisplay = document.getElementById('payment-amount-display');
    const dynamicUpiQr = document.getElementById('dynamic-upi-qr');
    const upiAmountBadge = document.getElementById('upi-amount-badge');

    const cardDbId = matchedCard.getAttribute('data-id') || '';
    if (inputPlan) inputPlan.value = plan;
    if (inputPlanId) inputPlanId.value = cardDbId;
    if (inputPrice) inputPrice.value = price;
    if (inputPlanName) inputPlanName.value = name;
    if (inputCycle) inputCycle.value = cycle;

    try {
        var cycleVal = (window.currentCycle === 'yearly' || cycle === 'yearly') ? 2 : 1;
        var payMethod = inputPaymentMethod ? inputPaymentMethod.value : (window.currentMethod || 'upi');
        localStorage.setItem('wb_pending_signup_plan', JSON.stringify({
            plan_id: plan,
            plan_db_id: cardDbId,
            plan_name: name,
            plan_price: price,
            billing_cycle: cycleVal,
            payment_method: payMethod,
            timestamp: Date.now()
        }));
    } catch(e) {}

    const cycleLabel = (cycle === 'monthly') ? '/ month' : '/ year';
    if (planBadge) {
        if (price === 0) {
            planBadge.textContent = name + ' • ₹0 / Free';
            planBadge.style.background = '#f3f4f6';
            planBadge.style.color = '#374151';
            planBadge.style.border = '1px solid #d1d5db';
        } else {
            planBadge.textContent = name + ' • ₹' + price + ' ' + cycleLabel;
            planBadge.style.background = '#e8f5e9';
            planBadge.style.color = '#1b5e20';
            planBadge.style.border = '1px solid #bbf7d0';
        }
    }

    if (paymentGatewaySection) {
        if (price === 0) {
            paymentGatewaySection.style.display = 'none';
            if (inputPaymentMethod) inputPaymentMethod.value = 'free';
            const utrErr = document.getElementById('utr-error-msg');
            if (utrErr) utrErr.style.display = 'none';
        } else {
            paymentGatewaySection.style.display = 'block';
            if (inputPaymentMethod) inputPaymentMethod.value = window.currentMethod;
            if (paymentAmountDisplay) paymentAmountDisplay.textContent = '₹' + price.toFixed(2);
            if (upiAmountBadge) upiAmountBadge.textContent = 'Amount to Pay: ₹' + price;

            if (dynamicUpiQr) {
                const upiUrl = 'upi://pay?pa=' + encodeURIComponent(window.upiVpa) + '&pn=Whatsappbulk&am=' + price.toFixed(2) + '&cu=INR&tn=' + encodeURIComponent(name + ' (' + cycle + ') Subscription');
                dynamicUpiQr.src = 'https://api.qrserver.com/v1/create-qr-code/?size=240x240&data=' + encodeURIComponent(upiUrl);
            }
        }
    }

    window.updateSubmitButtonText();
};

window.switchPayTab = function(method) {
    window.currentMethod = method || 'upi';
    const inputPaymentMethod = document.getElementById('input_payment_method');
    if (inputPaymentMethod) inputPaymentMethod.value = window.currentMethod;

    const tabUpi = document.getElementById('tab-pay-upi');
    const tabRazorpay = document.getElementById('tab-pay-razorpay');
    const boxUpi = document.getElementById('box-method-upi');
    const boxRazorpay = document.getElementById('box-method-razorpay');

    if (window.currentMethod === 'upi') {
        if (tabUpi) {
            tabUpi.classList.add('active');
            tabUpi.style.background = '#1f7a45';
            tabUpi.style.color = '#ffffff';
            tabUpi.style.borderColor = '#1f7a45';
        }
        if (tabRazorpay) {
            tabRazorpay.classList.remove('active');
            tabRazorpay.style.background = '#ffffff';
            tabRazorpay.style.color = '#374151';
            tabRazorpay.style.borderColor = '#e5e7eb';
        }
        if (boxUpi) boxUpi.style.display = 'block';
        if (boxRazorpay) boxRazorpay.style.display = 'none';
    } else {
        if (tabRazorpay) {
            tabRazorpay.classList.add('active');
            tabRazorpay.style.background = '#1f7a45';
            tabRazorpay.style.color = '#ffffff';
            tabRazorpay.style.borderColor = '#1f7a45';
        }
        if (tabUpi) {
            tabUpi.classList.remove('active');
            tabUpi.style.background = '#ffffff';
            tabUpi.style.color = '#374151';
            tabUpi.style.borderColor = '#e5e7eb';
        }
        if (boxUpi) boxUpi.style.display = 'none';
        if (boxRazorpay) boxRazorpay.style.display = 'block';
    }

    window.updateSubmitButtonText();
};

window.copyUpiId = function() {
    const btn = document.getElementById('btn-copy-upi');
    const vpa = window.upiVpa || '';
    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(vpa).then(() => {
            if (btn) {
                btn.innerHTML = '<i class="fa-solid fa-check me-1"></i> {{ __("Copied!") }}';
                btn.style.background = '#1f7a45';
                btn.style.color = '#ffffff';
                setTimeout(() => {
                    btn.innerHTML = '<i class="fa-regular fa-copy me-1"></i> {{ __("Copy") }}';
                    btn.style.background = '#ffffff';
                    btn.style.color = '#1f7a45';
                }, 2000);
            }
        }).catch(() => {});
    } else {
        const tempInput = document.createElement('input');
        tempInput.value = vpa;
        document.body.appendChild(tempInput);
        tempInput.select();
        document.execCommand('copy');
        document.body.removeChild(tempInput);
        if (btn) {
            btn.innerHTML = '<i class="fa-solid fa-check me-1"></i> {{ __("Copied!") }}';
            setTimeout(() => {
                btn.innerHTML = '<i class="fa-regular fa-copy me-1"></i> {{ __("Copy") }}';
            }, 2000);
        }
    }
};

window.downloadUpiQr = function() {
    var qrImg = document.getElementById('dynamic-upi-qr');
    if (qrImg && qrImg.src) {
        fetch(qrImg.src)
            .then(function(res) { return res.blob(); })
            .then(function(blob) {
                var blobUrl = window.URL.createObjectURL(blob);
                var a = document.createElement('a');
                a.style.display = 'none';
                a.href = blobUrl;
                a.download = 'whatsappbulk-upi-qr.png';
                document.body.appendChild(a);
                a.click();
                window.URL.revokeObjectURL(blobUrl);
                a.remove();
            })
            .catch(function() {
                window.open(qrImg.src, '_blank');
            });
    }
};

window.handleProofSelect = function(input) {
    if (input.files && input.files[0]) {
        const file = input.files[0];
        const defaultState = document.getElementById('proof-default-state');
        const previewState = document.getElementById('proof-preview-state');
        const filenameEl = document.getElementById('proof-preview-filename');
        const dropzone = document.getElementById('proof-dropzone');
        if (defaultState) defaultState.style.display = 'none';
        if (previewState) previewState.style.display = 'flex';
        if (filenameEl) filenameEl.textContent = file.name + ' (' + (file.size / 1024).toFixed(1) + ' KB)';
        if (dropzone) {
            dropzone.style.background = '#dcfce7';
            dropzone.style.borderColor = '#16a34a';
        }
    }
};

window.clearProofSelect = function(e) {
    if (e) {
        e.preventDefault();
        e.stopPropagation();
    }
    const input = document.getElementById('payment_proof');
    if (input) input.value = '';
    const defaultState = document.getElementById('proof-default-state');
    const previewState = document.getElementById('proof-preview-state');
    const dropzone = document.getElementById('proof-dropzone');
    if (defaultState) defaultState.style.display = 'block';
    if (previewState) previewState.style.display = 'none';
    if (dropzone) {
        dropzone.style.background = '#f0fdf4';
        dropzone.style.borderColor = '#86efac';
    }
};

document.addEventListener('DOMContentLoaded', function () {
    const csrfToken = document.querySelector('input[name="_token"]')?.value || '{{ csrf_token() }}';

    let isPhoneVerified = false;

    // Elements - WhatsApp
    const btnSendWaOtp = document.getElementById('btn-send-wa-otp');
    const btnVerifyWaOtp = document.getElementById('btn-verify-wa-otp');
    const btnResendWaOtp = document.getElementById('btn-resend-wa-otp');
    const waOtpBox = document.getElementById('wa-otp-box');
    const waOtpMsg = document.getElementById('wa-otp-msg');
    const waOtpInput = document.getElementById('wa-otp-input');
    const waVerifiedBadge = document.getElementById('wa-verified-badge');
    const phoneInput = document.getElementById('phone');
    const countryCodeSelect = document.getElementById('country_code');
    const waTimerSpan = document.getElementById('wa-timer');

    const signupForm = document.getElementById('signup-form');
    const utrInput = document.getElementById('utr_number');
    const utrErrorMsg = document.getElementById('utr-error-msg');
    const razorpayPaymentIdInput = document.getElementById('hidden_razorpay_payment_id');
    const inputPlanName = document.getElementById('input_selected_plan_name');
    const btnSubmit = document.getElementById('btn-submit-signup');

    // Countdown Helper
    function startCountdown(durationSec, timerEl, resendBtnEl, sendBtnEl, intervalRef) {
        let left = durationSec;
        if (resendBtnEl) resendBtnEl.style.display = 'none';
        if (sendBtnEl) sendBtnEl.disabled = true;
        if (timerEl) timerEl.textContent = '(' + left + 's)';

        if (intervalRef.val) clearInterval(intervalRef.val);
        intervalRef.val = setInterval(() => {
            left--;
            if (left <= 0) {
                clearInterval(intervalRef.val);
                if (timerEl) timerEl.textContent = '';
                if (resendBtnEl) resendBtnEl.style.display = 'inline-block';
                if (sendBtnEl) sendBtnEl.disabled = false;
            } else {
                if (timerEl) timerEl.textContent = '(' + left + 's)';
            }
        }, 1000);
    }

    const waIntervalRef = { val: null };

    // ─────────────────────────────────────────────────────────────
    // 1. Send WhatsApp OTP
    // ─────────────────────────────────────────────────────────────
    function doSendWaOtp() {
        const phone = phoneInput ? phoneInput.value.trim() : '';
        const countryCode = countryCodeSelect ? countryCodeSelect.value.trim() : '+91';
        const emailEl = document.getElementById('email');
        const emailVal = emailEl ? emailEl.value.trim() : '';

        if (!phone || phone.length < 7) {
            if (waOtpMsg) waOtpMsg.innerHTML = '<span style="color:#ef4444;"><i class="fa-solid fa-triangle-exclamation"></i> Please enter your mobile number.</span>';
            if (phoneInput) phoneInput.focus();
            return;
        }

        if (!emailVal || !emailVal.includes('@') || !emailVal.includes('.')) {
            if (waOtpMsg) waOtpMsg.innerHTML = '<span style="color:#ef4444;"><i class="fa-solid fa-triangle-exclamation"></i> Please enter a valid email address before sending OTP.</span>';
            if (emailEl) emailEl.focus();
            return;
        }

        if (btnSendWaOtp) {
            btnSendWaOtp.disabled = true;
            const originalText = btnSendWaOtp.innerHTML;
            btnSendWaOtp.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Sending...';
            if (waOtpMsg) waOtpMsg.innerHTML = '<span style="color:#6b7280;">Sending verification code to WhatsApp & Email...</span>';

            fetch('{{ url("auth/send_whatsapp_otp") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ phone: phone, country_code: countryCode, email: emailVal, _token: csrfToken })
            })
            .then(res => res.json())
            .then(data => {
                btnSendWaOtp.disabled = false;
                btnSendWaOtp.innerHTML = originalText;

                if (data.status === 1) {
                    if (waOtpMsg) waOtpMsg.innerHTML = '<span style="color:#16a34a;"><i class="fa-solid fa-check"></i> ' + (data.message || 'OTP sent successfully to both your WhatsApp number and Email!') + '</span>';
                    var noticeBadge = document.getElementById('wa_otp_notice_badge');
                    if (noticeBadge) noticeBadge.style.display = 'block';
                    if (waOtpBox) waOtpBox.style.display = 'block';
                    if (waOtpInput) waOtpInput.focus();
                    startCountdown(60, waTimerSpan, btnResendWaOtp, btnSendWaOtp, waIntervalRef);
                } else {
                    if (waOtpMsg) waOtpMsg.innerHTML = '<span style="color:#ef4444;"><i class="fa-solid fa-circle-exclamation"></i> ' + (data.message || 'Failed to send OTP') + '</span>';
                }
            })
            .catch(err => {
                btnSendWaOtp.disabled = false;
                btnSendWaOtp.innerHTML = originalText;
                if (waOtpMsg) waOtpMsg.innerHTML = '<span style="color:#ef4444;"><i class="fa-solid fa-circle-exclamation"></i> Network error. Please try again.</span>';
            });
        }
    }

    if (btnSendWaOtp) btnSendWaOtp.addEventListener('click', doSendWaOtp);
    if (btnResendWaOtp) btnResendWaOtp.addEventListener('click', doSendWaOtp);

    // ─────────────────────────────────────────────────────────────
    // 2. Verify WhatsApp OTP
    // ─────────────────────────────────────────────────────────────
    if (btnVerifyWaOtp) {
        btnVerifyWaOtp.addEventListener('click', function () {
            const otp = waOtpInput ? waOtpInput.value.trim() : '';
            const phone = phoneInput ? phoneInput.value.trim() : '';
            const countryCode = countryCodeSelect ? countryCodeSelect.value.trim() : '+91';

            if (!otp || otp.length < 6) {
                if (waOtpMsg) waOtpMsg.innerHTML = '<span style="color:#ef4444;">Please enter the 6-digit OTP received on WhatsApp / Email.</span>';
                if (waOtpInput) waOtpInput.focus();
                return;
            }

            btnVerifyWaOtp.disabled = true;
            btnVerifyWaOtp.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Verifying...';

            fetch('{{ url("auth/verify_whatsapp_otp") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ phone: phone, country_code: countryCode, otp: otp, _token: csrfToken })
            })
            .then(res => res.json())
            .then(data => {
                btnVerifyWaOtp.disabled = false;
                btnVerifyWaOtp.innerHTML = '<i class="fa-solid fa-circle-check me-1"></i> Verify OTP';

                if (data.status === 1) {
                    isPhoneVerified = true;
                    if (waIntervalRef.val) clearInterval(waIntervalRef.val);
                    if (waOtpMsg) waOtpMsg.innerHTML = '';

                    var verifiedBanner = document.getElementById('wa-otp-verified-banner');
                    if (verifiedBanner) {
                        verifiedBanner.style.display = 'block';
                        verifiedBanner.innerHTML = '<i class="fa-solid fa-circle-check text-success me-1"></i> ' + (data.message || 'OTP Verified successfully! Your account is verified.');
                    }

                    if (waOtpInput) {
                        waOtpInput.disabled = true;
                        waOtpInput.readOnly = true;
                        waOtpInput.style.backgroundColor = '#f1f5f9';
                        waOtpInput.style.color = '#334155';
                        waOtpInput.style.cursor = 'not-allowed';
                        waOtpInput.style.borderColor = '#10b981';
                    }

                    if (btnVerifyWaOtp) {
                        btnVerifyWaOtp.disabled = true;
                        btnVerifyWaOtp.style.cursor = 'default';
                        btnVerifyWaOtp.innerHTML = '<i class="fa-solid fa-circle-check me-1"></i> Verified';
                        btnVerifyWaOtp.style.backgroundColor = '#10b981';
                    }

                    if (btnSendWaOtp) {
                        btnSendWaOtp.disabled = true;
                        btnSendWaOtp.style.cursor = 'default';
                        btnSendWaOtp.innerHTML = '<i class="fa-solid fa-circle-check me-1"></i> Verified';
                        btnSendWaOtp.style.background = '#10b981';
                    }

                    if (btnResendWaOtp) btnResendWaOtp.style.display = 'none';
                    if (waTimerSpan) waTimerSpan.textContent = '';
                    if (waVerifiedBadge) waVerifiedBadge.style.display = 'inline-flex';

                    var emailVerifiedBadge = document.getElementById('email-verified-badge');
                    if (emailVerifiedBadge) emailVerifiedBadge.style.display = 'inline-flex';

                    if (phoneInput) {
                        phoneInput.readOnly = true;
                        phoneInput.style.backgroundColor = '#f0fdf4';
                        phoneInput.style.cursor = 'not-allowed';
                    }
                    if (countryCodeSelect) countryCodeSelect.disabled = true;

                    var emailEl = document.getElementById('email');
                    if (emailEl) {
                        emailEl.readOnly = true;
                        emailEl.style.backgroundColor = '#f0fdf4';
                        emailEl.style.cursor = 'not-allowed';
                    }
                } else {
                    if (waOtpMsg) waOtpMsg.innerHTML = '<span style="color:#ef4444;"><i class="fa-solid fa-circle-xmark"></i> ' + (data.message || 'Invalid code.') + '</span>';
                    if (waOtpInput) waOtpInput.select();
                }
            })
            .catch(err => {
                btnVerifyWaOtp.disabled = false;
                btnVerifyWaOtp.innerHTML = '<i class="fa-solid fa-circle-check me-1"></i> Verify OTP';
                if (waOtpMsg) waOtpMsg.innerHTML = '<span style="color:#ef4444;">Verification failed. Please try again.</span>';
            });
        });
    }

    // Password visibility toggle
    document.querySelectorAll('.btn-toggle-password').forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            const targetId = this.getAttribute('data-target');
            const targetInput = document.getElementById(targetId);
            const icon = this.querySelector('i');
            if (!targetInput) return;

            if (targetInput.type === 'password') {
                targetInput.type = 'text';
                if (icon) {
                    icon.classList.remove('fa-eye');
                    icon.classList.add('fa-eye-slash');
                }
                this.style.color = '#1f7a45';
            } else {
                targetInput.type = 'password';
                if (icon) {
                    icon.classList.remove('fa-eye-slash');
                    icon.classList.add('fa-eye');
                }
                this.style.color = '#759081';
            }
        });
    });

    // Strip whitespace from username
    const usernameInput = document.getElementById('username');
    if (usernameInput) {
        usernameInput.addEventListener('input', function () {
            this.value = this.value.replace(/\s+/g, '');
        });
        usernameInput.addEventListener('blur', function () {
            this.value = this.value.trim().replace(/\s+/g, '');
        });
    }

    // Auto-detect URL parameters ?plan= and ?cycle= or restore from localStorage
    const urlParams = new URLSearchParams(window.location.search);
    let initialCycle = urlParams.get('cycle');
    let initialPlan = urlParams.get('plan') || urlParams.get('plan_id');

    if (!initialPlan || !initialCycle) {
        try {
            const savedRaw = localStorage.getItem('wb_pending_signup_plan');
            if (savedRaw) {
                const saved = JSON.parse(savedRaw);
                if (!initialPlan && saved.plan_id) initialPlan = saved.plan_id;
                if (!initialPlan && saved.plan_name) initialPlan = saved.plan_name;
                if (!initialCycle && saved.billing_cycle) {
                    initialCycle = (saved.billing_cycle === 2 || saved.billing_cycle === 'yearly') ? 'yearly' : 'monthly';
                }
                if (saved.payment_method && typeof window.switchPayTab === 'function') {
                    window.switchPayTab(saved.payment_method);
                }
            }
        } catch(e) {}
    }

    if (!initialCycle) initialCycle = '{{ $activeCycle ?? "yearly" }}';
    if (!initialPlan) initialPlan = '{{ $defaultPlanId ?? ($planParam ?? "") }}';

    window.switchCycle(initialCycle);
    window.selectPlan(initialPlan, initialCycle);

    // ─────────────────────────────────────────────────────────────
    // Form Submission
    // ─────────────────────────────────────────────────────────────
    if (signupForm) {
        signupForm.addEventListener('submit', function (e) {
            e.preventDefault();
            e.stopImmediatePropagation();

            const errorContainer = signupForm.querySelector('.msg-error');

            if (errorContainer) {
                errorContainer.innerHTML = '';
            }

            if (!isPhoneVerified) {
                if (errorContainer) {
                    errorContainer.innerHTML = '<div style="background:#fef2f2;border:1px solid #fecaca;color:#dc2626;padding:12px 14px;border-radius:12px;font-size:13px;font-weight:600;margin-bottom:12px;"><i class="fa-solid fa-triangle-exclamation" style="margin-right:6px;"></i> Please verify your WhatsApp mobile number and email address before signing up.</div>';
                    errorContainer.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
                const btnSend = document.getElementById('btn-send-wa-otp');
                if (btnSend) btnSend.scrollIntoView({ behavior: 'smooth', block: 'center' });
                return false;
            }

            // Payment Validation & In-Page Standalone Razorpay Flow
            if (window.currentPrice > 0 && window.currentMethod === 'razorpay' && !(razorpayPaymentIdInput && razorpayPaymentIdInput.value)) {
                launchStandaloneRazorpaySignup();
                return false;
            }

            executeFormAjaxSubmit();
        });
    }

    function launchStandaloneRazorpaySignup() {
        const errorContainer = signupForm.querySelector('.msg-error');
        if (typeof Razorpay === 'undefined') {
            if (errorContainer) {
                errorContainer.innerHTML = '<div style="background:#fef2f2;border:1px solid #fecaca;color:#dc2626;padding:12px 14px;border-radius:12px;font-size:13px;font-weight:600;margin-bottom:12px;">Razorpay Checkout script failed to load. Please refresh or choose UPI.</div>';
            }
            return;
        }

        if (countryCodeSelect) countryCodeSelect.disabled = false;
        const formData = new FormData(signupForm);
        if (countryCodeSelect) countryCodeSelect.disabled = true;

        const origHtml = btnSubmit ? btnSubmit.innerHTML : '';
        if (btnSubmit) {
            btnSubmit.disabled = true;
            btnSubmit.innerHTML = '<span style="color:#ffffff;display:inline-flex;align-items:center;gap:8px;"><i class="fa-solid fa-circle-notch fa-spin"></i> {{ __("Preparing Razorpay...") }}</span>';
        }

        fetch('{{ url("auth/razorpay_order") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData
        })
        .then(res => res.json())
        .then(orderData => {
            if (!orderData || orderData.status !== 1 || !orderData.order_id) {
                if (btnSubmit) {
                    btnSubmit.disabled = false;
                    btnSubmit.innerHTML = origHtml;
                }
                let errMsg = (orderData && orderData.message) ? orderData.message : 'Unable to initiate Razorpay payment.';
                if (orderData && orderData.errors && typeof orderData.errors === 'object') {
                    errMsg = Object.values(orderData.errors).flat().join('<br>');
                }
                if (errorContainer) {
                    errorContainer.innerHTML = '<div style="background:#fef2f2;border:1.5px solid #fecaca;color:#dc2626;padding:12px 14px;border-radius:12px;font-size:13px;font-weight:600;margin-bottom:12px;"><i class="fa-solid fa-triangle-exclamation" style="margin-right:6px;"></i> ' + errMsg + '</div>';
                    errorContainer.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
                return;
            }

            const rzp = new Razorpay({
                key: orderData.key_id,
                amount: orderData.amount,
                currency: orderData.currency || 'INR',
                name: orderData.name || 'WhatsApp Bulk',
                description: orderData.description || 'Subscription',
                order_id: orderData.order_id,
                prefill: orderData.prefill || {},
                theme: { color: '#1f7a45' },
                handler: function (response) {
                    if (razorpayPaymentIdInput) razorpayPaymentIdInput.value = response.razorpay_payment_id || '';
                    const orderIdEl = document.getElementById('hidden_razorpay_order_id');
                    const sigEl = document.getElementById('hidden_razorpay_signature');
                    if (orderIdEl) orderIdEl.value = response.razorpay_order_id || '';
                    if (sigEl) sigEl.value = response.razorpay_signature || '';
                    executeFormAjaxSubmit();
                },
                modal: {
                    ondismiss: function () {
                        if (btnSubmit) {
                            btnSubmit.disabled = false;
                            btnSubmit.innerHTML = origHtml;
                        }
                    }
                }
            });
            rzp.open();
        })
        .catch(() => {
            if (btnSubmit) {
                btnSubmit.disabled = false;
                btnSubmit.innerHTML = origHtml;
            }
            if (errorContainer) {
                errorContainer.innerHTML = '<div style="background:#fef2f2;border:1.5px solid #fecaca;color:#dc2626;padding:12px 14px;border-radius:12px;font-size:13px;font-weight:600;margin-bottom:12px;">Network error connecting to Razorpay. Please try again.</div>';
            }
        });
    }

    function executeFormAjaxSubmit() {
        const errorContainer = signupForm.querySelector('.msg-error');
        if (countryCodeSelect) countryCodeSelect.disabled = false;
        const formData = new FormData(signupForm);
        if (countryCodeSelect) countryCodeSelect.disabled = true;

        if (btnSubmit) {
            btnSubmit.disabled = true;
            const originalBtnHtml = btnSubmit.innerHTML;
            btnSubmit.innerHTML = '<span style="color:#ffffff;display:inline-flex;align-items:center;gap:8px;"><i class="fa-solid fa-circle-notch fa-spin"></i> {{ __("Creating Account...") }}</span>';

            fetch(signupForm.action, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 1) {
                    try {
                        var pId = (inputPlan ? inputPlan.value : '') || window.currentPlan;
                        var pName = (inputPlanName ? inputPlanName.value : '') || window.currentPlan;
                        var pPrice = window.currentPrice || 0;
                        var pCycle = (window.currentCycle === 'yearly') ? 2 : 1;
                        var pMethod = (inputPaymentMethod ? inputPaymentMethod.value : '') || window.currentMethod || 'upi';

                        localStorage.setItem('wb_pending_signup_plan', JSON.stringify({
                            plan_id: pId,
                            plan_name: pName,
                            plan_price: pPrice,
                            billing_cycle: pCycle,
                            payment_method: pMethod,
                            timestamp: Date.now()
                        }));
                    } catch(e) {}

                    // Redirect if controller returned a destination immediately (e.g. direct activation)
                    if (data.redirect && window.currentMethod === 'razorpay') {
                        btnSubmit.innerHTML = '<span style="color:#ffffff;display:inline-flex;align-items:center;gap:8px;"><i class="fa-solid fa-circle-check"></i> {{ __("Payment Verified! Redirecting...") }}</span>';
                        window.location.href = data.redirect;
                        return;
                    }

                    btnSubmit.innerHTML = '<span style="color:#ffffff;"><i class="fa-solid fa-check"></i> {{ __("Account Created!") }}</span>';
                    if (errorContainer) {
                        errorContainer.innerHTML = '<div style="background:#f0fdf4;border:1.5px solid #86efac;color:#15803d;padding:14px;border-radius:12px;font-size:14px;font-weight:700;margin-bottom:12px;"><i class="fa-solid fa-circle-check" style="margin-right:6px;"></i> ' + (data.message || 'Account created successfully! Redirecting...') + '</div>';
                        errorContainer.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                    setTimeout(() => {
                        window.location.href = data.redirect || '{{ url("auth/login") }}';
                    }, 1500);
                } else {
                    btnSubmit.disabled = false;
                    btnSubmit.innerHTML = originalBtnHtml;

                    let errMsg = data.message || 'Signup failed. Please check the form errors.';
                    if (data.errors) {
                        if (typeof data.errors === 'object') {
                            errMsg = Object.values(data.errors).flat().join('<br>');
                        } else {
                            errMsg = data.errors;
                        }
                    }

                    if (errorContainer) {
                        errorContainer.innerHTML = '<div style="background:#fef2f2;border:1.5px solid #fecaca;color:#dc2626;padding:12px 14px;border-radius:12px;font-size:13px;font-weight:600;margin-bottom:12px;line-height:1.5;"><i class="fa-solid fa-triangle-exclamation" style="margin-right:6px;"></i> ' + errMsg + '</div>';
                        errorContainer.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                }
            })
            .catch(err => {
                btnSubmit.disabled = false;
                btnSubmit.innerHTML = originalBtnHtml;
                if (errorContainer) {
                    errorContainer.innerHTML = '<div style="background:#fef2f2;border:1.5px solid #fecaca;color:#dc2626;padding:12px 14px;border-radius:12px;font-size:13px;font-weight:600;margin-bottom:12px;"><i class="fa-solid fa-triangle-exclamation" style="margin-right:6px;"></i> An unexpected network error occurred. Please try again.</div>';
                }
            });
        }
    }
});
</script>
