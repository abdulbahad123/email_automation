<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<section class="relative w-screen min-h-screen flex items-stretch overflow-hidden bg-white overflow-x-hidden">
    @include("partials/login-screen", ["name" => __("Create an account & get started.")])

    <div class="flex flex-col justify-center flex-1 px-4 md:px-8 py-12 bg-blueGray-100 z-10" style="background-image: url({{ theme_public_asset('images/pattern-light-big.svg') }}); background-position: center;">
        <div class="absolute inset-0 pointer-events-none" style="background:
            radial-gradient(circle at 25% 30%, rgba(79,70,229,.08) 0%, rgba(79,70,229,0) 22%),
            radial-gradient(circle at 78% 75%, rgba(59,130,246,.08) 0%, rgba(59,130,246,0) 24%);"></div>
        <div class="relative w-full max-w-xl mx-auto">
        <div class="show-on-mobile mb-6 text-center">
            <a class="inline-block" href="{{ url('') }}">
                <img class="h-10" src="{{ url( get_option("website_logo_brand_dark", asset('public/img/logo-brand-dark.png')) ) }}" alt="">
            </a>
        </div>
        <form class="actionForm relative w-full space-y-5 bg-white rounded-3xl border border-white shadow-2xl p-6 md:p-10" id="signup-form" action="{{ module_url('do_signup') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="mb-8 text-center">
                <span class="inline-flex items-center px-4 py-2 mb-4 text-xs font-semibold uppercase tracking-widest rounded-full" style="background-color:#eef2ff;color:#4338ca; letter-spacing:0.22em;">
                    {{ __("Create workspace") }}
                </span>
                <h1 class="mb-3 font-bold font-heading tracking-tight text-gray-900" style="font-size:2.2rem; line-height:1.1;">{{ __("Create your account") }}</h1>
                <p class="text-sm md:text-base leading-relaxed text-gray-600">{{ __("Launch a cleaner publishing workflow with scheduling, collaboration, and reporting in one place.") }}</p>
            </div>

            <div>
                <label for="fullname" class="block text-gray-700 font-semibold mb-2 text-sm">{{ __("Full Name") }}</label>
                <input type="text" id="fullname" name="fullname" class="w-full px-4 py-3 text-gray-700 font-medium bg-white border border-gray-300 rounded-xl focus:ring focus:ring-indigo-300 outline-none text-sm" placeholder="{{ __('Enter your full name') }}" required>
            </div>

            <div>
                <label for="email" class="block text-gray-700 font-semibold mb-2 text-sm">{{ __("Email Address") }}</label>
                <input type="email" id="email" name="email" class="w-full px-4 py-3 text-gray-700 font-medium bg-white border border-gray-300 rounded-xl focus:ring focus:ring-indigo-300 outline-none text-sm" placeholder="{{ __('Enter your email address') }}" required>
            </div>

            <div>
                <label for="username" class="block text-gray-700 font-semibold mb-2 text-sm">{{ __("Username") }}</label>
                <input type="text" id="username" name="username" class="w-full px-4 py-3 text-gray-700 font-medium bg-white border border-gray-300 rounded-xl focus:ring focus:ring-indigo-300 outline-none text-sm" placeholder="{{ __('Choose a username') }}" required>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="password" class="block text-gray-700 font-semibold mb-2 text-sm">{{ __("Password") }}</label>
                    <input type="password" id="password" name="password" class="w-full px-4 py-3 text-gray-700 font-medium bg-white border border-gray-300 rounded-xl focus:ring focus:ring-indigo-300 outline-none text-sm" placeholder="{{ __('Enter your password') }}" required>
                </div>
                <div>
                    <label for="password_confirmation" class="block text-gray-700 font-semibold mb-2 text-sm">{{ __("Confirm Password") }}</label>
                    <input type="password" id="password_confirmation" name="password_confirmation" class="w-full px-4 py-3 text-gray-700 font-medium bg-white border border-gray-300 rounded-xl focus:ring focus:ring-indigo-300 outline-none text-sm" placeholder="{{ __('Re-enter your password') }}" required>
                </div>
            </div>

            <div>
                <label for="timezone" class="block text-gray-700 font-semibold mb-2 text-sm">{{ __("Timezone") }}</label>
                <select id="timezone" name="timezone" class="w-full px-4 py-3 text-gray-700 font-medium bg-white border border-gray-300 rounded-xl focus:ring focus:ring-indigo-300 outline-none text-sm" required>
                    <option value="">{{ __("Select your timezone") }}</option>
                    @foreach(timezone_identifiers_list() as $tz)
                        <option value="{{ $tz }}" {{ (old('timezone') == $tz || $tz === 'Asia/Kolkata') ? 'selected' : '' }}>
                            {{ $tz }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â•  -->
            <!-- Plan Selection & Dynamic Billing Cycle (Synchronized with Pricing) -->
            <!-- â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â•  -->
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

                if ($yearlyPlans->isEmpty()) {
                    if ($monthlyPlans->isNotEmpty()) {
                        $yearlyPlans = $monthlyPlans->map(function($mp) {
                            $yp = clone $mp;
                            $yp->id = 'yearly_' . $mp->id;
                            $yp->id_secure = 'yearly_' . ($mp->id_secure ?: $mp->id);
                            $yp->type = 2;
                            $yp->price = ($mp->price == 350) ? 2999 : round($mp->price * 10);
                            $yp->featured = 1;
                            return $yp;
                        });
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
                    }
                }

                if ($monthlyPlans->isEmpty()) {
                    if ($yearlyPlans->isNotEmpty()) {
                        $monthlyPlans = $yearlyPlans->map(function($yp) {
                            $mp = clone $yp;
                            $mp->id = 'monthly_' . $yp->id;
                            $mp->id_secure = 'monthly_' . ($yp->id_secure ?: $yp->id);
                            $mp->type = 1;
                            $mp->price = ($yp->price == 2999) ? 350 : round($yp->price / 10);
                            return $mp;
                        });
                    } else {
                        $monthlyPlans = collect([
                            (object)[
                                'id' => 1,
                                'id_secure' => 'default_monthly',
                                'name' => 'WhatsApp Bulk Plan',
                                'price' => 350,
                                'type' => 1,
                                'free_plan' => 0,
                                'featured' => 1
                            ]
                        ]);
                    }
                }

                $activeCycle = $cycleParam ?? 'monthly';
                $targetCollection = ($activeCycle === 'yearly') ? $yearlyPlans : $monthlyPlans;

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

            <div class="bg-indigo-50/40 border border-indigo-100 rounded-2xl p-5 mb-5">
                <div class="flex justify-between items-center mb-4 flex-wrap gap-2">
                    <label class="text-sm font-bold text-indigo-950 flex items-center gap-2">
                        <i class="fa-solid fa-crown text-indigo-600"></i>
                        <span>{{ __("Select Membership Plan") }}</span>
                    </label>
                    <span id="selected-plan-badge" class="bg-indigo-100/70 text-indigo-900 text-xs font-bold px-3 py-1 rounded-full border border-indigo-200">
                        {{ $defaultPlanName }} â€¢ â‚¹{{ $defaultPlanPrice }} / {{ $activeCycle === 'monthly' ? 'month' : 'year' }}
                    </span>
                </div>

                <!-- Recurring Billing Cycle Switcher (Monthly vs Yearly) -->
                <div class="flex justify-center mb-4">
                    <div class="inline-flex bg-white border border-indigo-200 rounded-full p-1 gap-1 shadow-sm">
                        <button type="button" id="btn-cycle-monthly" onclick="window.switchCycle('monthly')" class="rounded-full px-4 py-1.5 text-xs font-bold transition-all duration-200" style="background: {{ $activeCycle === 'monthly' ? '#4f46e5' : 'transparent' }}; color: {{ $activeCycle === 'monthly' ? '#ffffff' : '#4b5563' }};">
                            {{ __("Monthly") }}
                        </button>
                        <button type="button" id="btn-cycle-yearly" onclick="window.switchCycle('yearly')" class="rounded-full px-4 py-1.5 text-xs font-bold transition-all duration-200 flex items-center gap-1.5" style="background: {{ $activeCycle === 'yearly' ? '#4f46e5' : 'transparent' }}; color: {{ $activeCycle === 'yearly' ? '#ffffff' : '#4b5563' }};">
                            <span>{{ __("Yearly") }}</span>
                            <span id="badge-yearly-save" class="text-[10px] font-extrabold px-2 py-0.5 rounded-full uppercase" style="background: {{ $activeCycle === 'yearly' ? '#ffffff' : '#e0e7ff' }}; color: {{ $activeCycle === 'yearly' ? '#4f46e5' : '#4338ca' }};">Save 20%</span>
                        </button>
                    </div>
                </div>

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
                <div id="grid-plans-monthly" class="grid grid-cols-1 md:grid-cols-2 gap-3" style="display: {{ $activeCycle === 'monthly' ? 'grid' : 'none' }};">
                    @forelse($monthlyPlans as $p)
                        @php
                            $pPrice = (float)$p->price;
                            $isFree = !empty($p->free_plan) || $pPrice == 0;
                            $planIdentifier = $p->id_secure ?: ($p->id ?: strtolower($p->name));
                            $isFeatured = !empty($p->featured);
                        @endphp
                        <div class="plan-option-card plan-monthly relative bg-white border-2 border-gray-200 rounded-xl p-4 cursor-pointer text-center transition-all duration-200" 
                             id="card-plan-monthly-{{ $planIdentifier }}"
                             data-plan="{{ $planIdentifier }}" 
                             data-id="{{ $p->id }}"
                             data-price="{{ $pPrice }}" 
                             data-name="{{ $p->name }}" 
                             data-cycle="monthly"
                             data-free="{{ $isFree ? '1' : '0' }}" 
                             onclick="window.selectPlanCard(this)">
                            @if($isFeatured)
                                <span class="absolute -top-2.5 left-1/2 transform -translate-x-1/2 bg-indigo-600 text-white text-[9px] font-bold px-2 py-0.5 rounded-full uppercase tracking-wider">Best Value</span>
                            @endif
                            <div class="text-xs font-bold uppercase text-indigo-700 mb-1">{{ $p->name }}</div>
                            <div class="text-2xl font-black text-gray-900 leading-none">â‚¹{{ $pPrice }}</div>
                            <div class="text-[11px] text-gray-400 mt-1">{{ $isFree ? 'Free / Month' : 'Billed Monthly' }}</div>
                        </div>
                    @empty
                        <div class="text-center text-gray-400 py-3 col-span-2">{{ __('No monthly plans currently available.') }}</div>
                    @endforelse
                </div>

                <!-- Yearly Plans Grid -->
                <div id="grid-plans-yearly" class="grid grid-cols-1 md:grid-cols-2 gap-3" style="display: {{ $activeCycle === 'yearly' ? 'grid' : 'none' }};">
                    @forelse($yearlyPlans as $p)
                        @php
                            $pPrice = (float)$p->price;
                            $isFree = !empty($p->free_plan) || $pPrice == 0;
                            $planIdentifier = $p->id_secure ?: ($p->id ?: strtolower($p->name));
                            $isFeatured = !empty($p->featured);
                        @endphp
                        <div class="plan-option-card plan-yearly relative bg-white border-2 border-gray-200 rounded-xl p-4 cursor-pointer text-center transition-all duration-200" 
                             id="card-plan-yearly-{{ $planIdentifier }}"
                             data-plan="{{ $planIdentifier }}" 
                             data-id="{{ $p->id }}"
                             data-price="{{ $pPrice }}" 
                             data-name="{{ $p->name }}" 
                             data-cycle="yearly"
                             data-free="{{ $isFree ? '1' : '0' }}" 
                             onclick="window.selectPlanCard(this)">
                            @if($isFeatured)
                                <span class="absolute -top-2.5 left-1/2 transform -translate-x-1/2 bg-indigo-600 text-white text-[9px] font-bold px-2 py-0.5 rounded-full uppercase tracking-wider">Best Value</span>
                            @endif
                            <div class="text-xs font-bold uppercase text-indigo-700 mb-1">{{ $p->name }}</div>
                            <div class="text-2xl font-black text-gray-900 leading-none">â‚¹{{ $pPrice }}</div>
                            <div class="text-[11px] text-gray-400 mt-1">{{ $isFree ? 'Free / Year' : 'Billed Yearly' }}</div>
                        </div>
                    @empty
                        <div class="text-center text-gray-400 py-3 col-span-2">{{ __('No yearly plans currently available.') }}</div>
                    @endforelse
                </div>
            </div>

            <!-- â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â•  -->
            <!-- Payment Gateway Section (UPI QR + Razorpay) -->
            <!-- â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â• â•  -->
            <div id="payment-gateway-section" class="bg-gray-50 border border-gray-200 rounded-2xl p-5 mb-5">
                <div class="flex items-center justify-between mb-4">
                    <span class="text-xs font-extrabold text-gray-700 uppercase tracking-wider flex items-center gap-1.5">
                        <i class="fa-solid fa-credit-card text-indigo-600"></i> {{ __("Payment Method") }}
                    </span>
                    <span id="payment-amount-display" class="text-base font-black text-indigo-600">â‚¹{{ number_format($defaultPlanPrice, 2) }}</span>
                </div>

                <!-- Tabs -->
                <div class="grid grid-cols-2 gap-2 mb-4">
                    <button type="button" id="tab-pay-upi" class="btn-pay-tab active py-2.5 px-3 rounded-xl border-2 font-bold text-xs flex items-center justify-center gap-2 transition" onclick="window.switchPayTab('upi')" style="border-color:#4f46e5; background:#4f46e5; color:#ffffff;">
                        <i class="fa-solid fa-qrcode"></i> <span>UPI / QR Code</span>
                    </button>
                    <button type="button" id="tab-pay-razorpay" class="btn-pay-tab py-2.5 px-3 rounded-xl border-2 border-gray-200 bg-white text-gray-700 font-bold text-xs flex items-center justify-center gap-2 transition" onclick="window.switchPayTab('razorpay')">
                        <i class="fa-solid fa-credit-card"></i> <span>Razorpay Online</span>
                    </button>
                </div>

                <!-- UPI Container -->
                <div id="box-method-upi" class="text-center bg-white border border-gray-200 rounded-xl p-4">
                    <span class="inline-block bg-emerald-50 text-emerald-700 text-[10px] font-extrabold px-2.5 py-1 rounded-full uppercase tracking-wider mb-2">
                        Scan & Pay via Any UPI App
                    </span>
                    <h6 class="text-sm font-bold text-gray-900 mb-2">Whatsappbulk Subscription</h6>

                    @php
                        $dynamicInitPrice = (float)($defaultPlanPrice > 0 ? $defaultPlanPrice : 350);
                        $dynamicInitPriceFormatted = number_format($dynamicInitPrice, 2, '.', '');
                        $dynamicInitName = $defaultPlanName ?: 'Subscription';
                        $resolvedUpi = !empty($upiId) ? $upiId : env('PAYMENT_UPI_ID', '');
                        $dynamicInitUpiUrl = 'upi://pay?pa=' . $resolvedUpi . '&pn=' . urlencode(env('APP_NAME', 'WhatsApp Bulk')) . '&am=' . $dynamicInitPriceFormatted . '&cu=INR&tn=' . urlencode($dynamicInitName . ' Subscription');
                    @endphp

                    <div class="my-2 inline-block p-2 bg-white border border-gray-200 rounded-xl shadow-sm">
                        <img id="dynamic-upi-qr" src="https://api.qrserver.com/v1/create-qr-code/?size=220x220&data={{ urlencode($dynamicInitUpiUrl) }}" alt="UPI QR Code" class="w-44 h-44 object-contain mx-auto block">
                        <div class="mt-2 text-center">
                            <span id="upi-amount-badge" class="inline-block bg-indigo-50 text-indigo-700 text-xs font-bold px-3 py-1 rounded-full">
                                Amount to Pay: â‚¹{{ $dynamicInitPrice }}
                            </span>
                        </div>
                        <div class="mt-2 text-center">
                            <button type="button" onclick="window.downloadUpiQr()" class="inline-flex items-center gap-1.5 px-3 py-1 text-xs font-bold text-indigo-600 bg-indigo-50 border border-indigo-200 rounded-full hover:bg-indigo-100 transition">
                                <i class="fa-solid fa-download"></i> {{ __("Download QR Code") }}
                            </button>
                        </div>
                    </div>

                    <div class="flex items-center justify-center gap-2 mb-3">
                        <span class="text-xs font-semibold text-gray-500">UPI ID:</span>
                        <code class="font-mono text-xs font-bold text-gray-900 bg-gray-100 px-2 py-1 rounded border border-gray-200" id="text-upi-vpa">{{ $resolvedUpi ?: __('Configure in Admin') }}</code>
                        <button type="button" id="btn-copy-upi" onclick="window.copyUpiId()" class="px-2.5 py-1 text-xs font-bold text-indigo-600 bg-white border border-indigo-500 rounded-full hover:bg-indigo-50 transition">
                            <i class="fa-regular fa-copy mr-1"></i> {{ __("Copy") }}
                        </button>
                    </div>

                    <!-- Payment Proof Upload -->
                    <div class="text-left mt-3">
                        <label class="text-xs font-bold text-gray-700 mb-1 flex items-center justify-between">
                            <span><i class="fa-solid fa-receipt text-indigo-600 mr-1"></i> {{ __("Payment Proof / Screenshot") }}</span>
                            <span class="text-[11px] font-semibold text-indigo-600">{{ __("Image or PDF") }}</span>
                        </label>
                        <div id="proof-dropzone" class="relative border-2 border-dashed border-indigo-200 rounded-xl p-3 bg-indigo-50/40 text-center cursor-pointer hover:border-indigo-400 transition" onclick="document.getElementById('payment_proof').click()">
                            <input type="file" id="payment_proof" name="payment_proof" accept="image/png,image/jpeg,image/jpg,image/webp,application/pdf" style="display: none;" onchange="window.handleProofSelect(this)">
                            <div id="proof-default-state">
                                <i class="fa-solid fa-cloud-arrow-up text-indigo-500 text-lg mb-1 block"></i>
                                <div class="text-xs font-bold text-indigo-900">{{ __("Click to upload payment screenshot or document") }}</div>
                                <div class="text-[10px] text-gray-500 mt-0.5">{{ __("JPG, PNG, WEBP or PDF (up to 5MB)") }}</div>
                            </div>
                            <div id="proof-preview-state" style="display: none;" class="items-center justify-center gap-2">
                                <i class="fa-solid fa-file-circle-check text-emerald-600"></i>
                                <span id="proof-preview-filename" class="text-xs font-bold text-emerald-800 truncate max-w-[200px]"></span>
                                <button type="button" class="text-red-500 hover:text-red-700 text-xs font-bold ml-1" onclick="window.clearProofSelect(event)">âœ•</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Razorpay Container -->
                <div id="box-method-razorpay" style="display: none;" class="text-center bg-white border border-indigo-100 rounded-xl p-5">
                    <span class="inline-block bg-indigo-50 text-indigo-700 text-[10px] font-extrabold px-2.5 py-1 rounded-full uppercase tracking-wider mb-2">
                        Online Instant Checkout
                    </span>
                    <h6 class="text-sm font-bold text-gray-900 mb-1">Pay via Razorpay Secure Gateway</h6>
                    <p class="text-xs text-gray-500 mb-3">
                        {{ __("Supports Credit/Debit Cards, NetBanking, UPI, and Digital Wallets.") }}
                    </p>
                    <div class="p-3 bg-indigo-50/60 rounded-xl border border-dashed border-indigo-200 text-xs text-indigo-700 font-medium">
                        <i class="fa-solid fa-shield-halved mr-1"></i> {{ __("Instant activation via secure Razorpay checkout.") }}
                    </div>
                </div>
            </div>

            <div class="mb-3">
                {!! Captcha::render(); !!}
            </div>

            <div class="flex items-center">
                <input class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500" id="accep_terms" name="accep_terms" type="checkbox" value="1" required>
                <label class="ml-2 text-sm text-gray-700 font-medium" for="accep_terms">
                    <span>{{ __("I agree to the") }}</span>
                    <a class="text-indigo-600 hover:text-indigo-700 underline" href="{{ url('terms-of-service') }}">{{ __("Terms & Conditions") }}</a>
                </label>
            </div>

            <div class="msg-error mb-2 text-sm text-red-500"></div>

            <button type="submit" id="btn-submit-signup" class="mb-6 py-4 px-9 w-full text-white text-base md:text-lg font-bold border border-indigo-700 rounded-2xl shadow-xl focus:ring focus:ring-indigo-300 bg-indigo-600 hover:bg-indigo-700 transition ease-in-out duration-200">
                <span style="color:#ffffff;">{{ __("Complete Sign Up") }}</span>
            </button>

            <p class="text-center pt-2 text-sm text-gray-600">
                {{ __("Already have an account?") }}
                <a href="{{ url('auth/login') }}" class="text-indigo-600 hover:text-indigo-700 font-semibold">{{ __("Sign in") }}</a>
            </p>
        </form>
        </div>
    </div>
</section>

<script type="text/javascript">
window.currentCycle = '{{ $activeCycle ?? "monthly" }}';
window.currentPrice = {{ (float)($defaultPlanPrice > 0 ? $defaultPlanPrice : 350) }};
window.currentPlan = '{{ $defaultPlanId ?? "starter" }}';
window.currentMethod = 'upi';
window.upiVpa = "{{ $resolvedUpi ?? '' }}";

window.updateSubmitButtonText = function() {
    const btnSubmit = document.getElementById('btn-submit-signup');
    if (!btnSubmit) return;
    if (window.currentPrice === 0) {
        btnSubmit.innerHTML = '<span style="color:#ffffff;">{{ __("Complete Free Sign Up") }}</span>';
    } else if (window.currentMethod === 'upi') {
        btnSubmit.innerHTML = '<span style="color:#ffffff;">{{ __("Complete Sign Up with UPI") }} (â‚¹' + window.currentPrice + ')</span>';
    } else {
        btnSubmit.innerHTML = '<span style="color:#ffffff;">{{ __("Proceed to Razorpay Checkout") }} (â‚¹' + window.currentPrice + ')</span>';
    }
};

window.switchCycle = function(cycle) {
    window.currentCycle = cycle || 'monthly';
    const inputCycle = document.getElementById('input_billing_cycle');
    if (inputCycle) inputCycle.value = window.currentCycle;

    const btnMonthly = document.getElementById('btn-cycle-monthly');
    const btnYearly = document.getElementById('btn-cycle-yearly');
    const badgeSave = document.getElementById('badge-yearly-save');
    const gridMonthly = document.getElementById('grid-plans-monthly');
    const gridYearly = document.getElementById('grid-plans-yearly');

    if (window.currentCycle === 'monthly') {
        if (btnMonthly) {
            btnMonthly.style.background = '#4f46e5';
            btnMonthly.style.color = '#ffffff';
        }
        if (btnYearly) {
            btnYearly.style.background = 'transparent';
            btnYearly.style.color = '#4b5563';
        }
        if (badgeSave) {
            badgeSave.style.background = '#e0e7ff';
            badgeSave.style.color = '#4338ca';
        }
        if (gridMonthly) gridMonthly.style.display = 'grid';
        if (gridYearly) gridYearly.style.display = 'none';

        const activeYearlyCard = document.querySelector('#grid-plans-yearly .plan-option-card.active');
        const planKey = activeYearlyCard ? (activeYearlyCard.getAttribute('data-name') || activeYearlyCard.getAttribute('data-plan')) : window.currentPlan;
        window.selectPlan(planKey, 'monthly');
    } else {
        if (btnYearly) {
            btnYearly.style.background = '#4f46e5';
            btnYearly.style.color = '#ffffff';
        }
        if (btnMonthly) {
            btnMonthly.style.background = 'transparent';
            btnMonthly.style.color = '#4b5563';
        }
        if (badgeSave) {
            badgeSave.style.background = '#ffffff';
            badgeSave.style.color = '#4f46e5';
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
    cycle = cycle || window.currentCycle || 'monthly';
    planKey = (planKey || 'starter').toString().toLowerCase().trim();

    const currentGridId = (cycle === 'monthly') ? 'grid-plans-monthly' : 'grid-plans-yearly';
    const grid = document.getElementById(currentGridId);
    const planCards = grid ? grid.querySelectorAll('.plan-option-card') : document.querySelectorAll('.plan-option-card');

    let matchedCard = null;
    planCards.forEach(card => {
        const cardPlan = (card.getAttribute('data-plan') || '').toLowerCase();
        const cardName = (card.getAttribute('data-name') || '').toLowerCase();
        if (cardPlan === planKey || cardName.includes(planKey) || planKey.includes('bulk')) {
            matchedCard = card;
        }
    });

    if (!matchedCard && planCards.length > 0) {
        matchedCard = planCards[0];
    }
    if (!matchedCard) return;

    document.querySelectorAll('.plan-option-card').forEach(c => {
        c.classList.remove('active');
        c.style.borderColor = '#e5e7eb';
        c.style.background = '#ffffff';
        c.style.boxShadow = 'none';
    });

    const plan = matchedCard.getAttribute('data-plan') || 'starter';
    const price = parseFloat(matchedCard.getAttribute('data-price')) || 0;
    const name = matchedCard.getAttribute('data-name') || 'WhatsApp Bulk Plan';

    window.currentPrice = price;
    window.currentPlan = plan;

    matchedCard.classList.add('active');
    matchedCard.style.borderColor = '#4f46e5';
    matchedCard.style.background = '#eef2ff';
    matchedCard.style.boxShadow = '0 4px 14px rgba(79, 70, 229, 0.15)';

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

    const cycleLabel = (cycle === 'monthly') ? '/ month' : '/ year';
    if (planBadge) {
        planBadge.textContent = name + ' â€¢ â‚¹' + price + ' ' + cycleLabel;
    }

    if (paymentGatewaySection) {
        if (price === 0) {
            paymentGatewaySection.style.display = 'none';
            if (inputPaymentMethod) inputPaymentMethod.value = 'free';
        } else {
            paymentGatewaySection.style.display = 'block';
            if (inputPaymentMethod) inputPaymentMethod.value = window.currentMethod;
            if (paymentAmountDisplay) paymentAmountDisplay.textContent = 'â‚¹' + price.toFixed(2);
            if (upiAmountBadge) upiAmountBadge.textContent = 'Amount to Pay: â‚¹' + price;

            if (dynamicUpiQr) {
                const upiUrl = 'upi://pay?pa=' + encodeURIComponent(window.upiVpa) + '&pn=Whatsappbulk&am=' + price.toFixed(2) + '&cu=INR&tn=' + encodeURIComponent(name + ' (' + cycle + ') Subscription');
                dynamicUpiQr.src = 'https://api.qrserver.com/v1/create-qr-code/?size=220x220&data=' + encodeURIComponent(upiUrl);
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
            tabUpi.style.background = '#4f46e5';
            tabUpi.style.color = '#ffffff';
            tabUpi.style.borderColor = '#4f46e5';
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
            tabRazorpay.style.background = '#4f46e5';
            tabRazorpay.style.color = '#ffffff';
            tabRazorpay.style.borderColor = '#4f46e5';
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
                btn.innerHTML = '<i class="fa-solid fa-check mr-1"></i> Copied!';
                setTimeout(() => {
                    btn.innerHTML = '<i class="fa-regular fa-copy mr-1"></i> Copy';
                }, 2000);
            }
        });
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
        if (defaultState) defaultState.style.display = 'none';
        if (previewState) previewState.style.display = 'flex';
        if (filenameEl) filenameEl.textContent = file.name + ' (' + (file.size / 1024).toFixed(1) + ' KB)';
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
    if (defaultState) defaultState.style.display = 'block';
    if (previewState) previewState.style.display = 'none';
};

document.addEventListener('DOMContentLoaded', function () {
    const defaultPlanKey = '{{ $defaultPlanId ?? "starter" }}';
    const initCycle = '{{ $activeCycle ?? "monthly" }}';
    window.selectPlan(defaultPlanKey, initCycle);
    window.updateSubmitButtonText();

    const novaSignupForm = document.getElementById('signup-form');
    if (novaSignupForm) {
        novaSignupForm.addEventListener('submit', function (e) {
            const rzpInput = document.getElementById('hidden_razorpay_payment_id');
            if (window.currentPrice > 0 && window.currentMethod === 'razorpay' && !(rzpInput && rzpInput.value)) {
                e.preventDefault();
                e.stopImmediatePropagation();
                if (typeof Razorpay === 'undefined') {
                    alert('Razorpay Checkout failed to load. Please refresh or choose UPI.');
                    return;
                }
                const csrfEl = novaSignupForm.querySelector('input[name="_token"]');
                const csrf = csrfEl ? csrfEl.value : '';
                const formData = new FormData(novaSignupForm);
                fetch('{{ url("auth/razorpay_order") }}', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    body: formData
                })
                .then(res => res.json())
                .then(orderData => {
                    if (!orderData || orderData.status !== 1 || !orderData.order_id) {
                        const errBox = novaSignupForm.querySelector('.msg-error');
                        const msg = (orderData && orderData.message) || 'Unable to initiate Razorpay payment.';
                        if (errBox) errBox.innerHTML = msg;
                        else alert(msg);
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
                        handler: function (resp) {
                            if (rzpInput) rzpInput.value = resp.razorpay_payment_id || '';
                            const oid = document.getElementById('hidden_razorpay_order_id');
                            const sig = document.getElementById('hidden_razorpay_signature');
                            if (oid) oid.value = resp.razorpay_order_id || '';
                            if (sig) sig.value = resp.razorpay_signature || '';
                            novaSignupForm.dispatchEvent(new Event('submit', { cancelable: true, bubbles: true }));
                        }
                    });
                    rzp.open();
                })
                .catch(() => alert('Network error initiating Razorpay payment.'));
            }
        }, true);
    }
});
</script>
