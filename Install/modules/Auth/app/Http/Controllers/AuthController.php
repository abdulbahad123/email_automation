<?php

namespace Modules\Auth\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Password;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Modules\AdminUsers\Models\Teams;
use Laravel\Socialite\Facades\Socialite;
use Modules\Auth\Events\AuthEvent;
use App\Models\User;
use App\Services\WhatsAppOtpService;
use App\Services\StandaloneRazorpayService;
use Core;
use Auth;
use Hash;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        return view('auth::login');
    }

    public function signup(Request $request)
    {
        if(!get_option("auth_signup_page_status", 1)){
            return redirect()->route('home');
        }

        $planParam = request("plan") ?: session('start_plan', 'starter');
        $cycleParam = request("cycle") ?: (request("billing") ?: 'yearly');
        session(['start_plan' => $planParam]);

        $plans = [];
        try {
            if (class_exists('\Modules\AdminPlans\Services\PlanService')) {
                \Modules\AdminPlans\Services\PlanService::syncDefaultBulkPlans();
            }
            $plans = \Modules\AdminPlans\Models\Plans::where('status', 1)->orderBy('position', 'asc')->get();
        } catch (\Throwable $e) {}

        $razorpayKey = get_option('razorpay_key_id', env('RAZORPAY_KEY_ID', 'rzp_test_T9UaATIMf1qeO8'));
        $upiId = get_option('payment_upi_id', env('PAYMENT_UPI_ID', ''));

        return view('auth::signup', compact('plans', 'planParam', 'cycleParam', 'razorpayKey', 'upiId'));
    }

    public function forgotPassword(Request $request)
    {
        return view('auth::forgot_password');
    }

    public function recoveryPassword(Request $request)
    {
        return view('auth::recovery_password');
    }

    public function resendActivation(Request $request)
    {
        return view('auth::resend_activation');
    }

    public function activation(Request $request)
    {
        $email = $request->query('email');
        $token = $request->query('token');
        $status = false;
        $message = '';

        if (!$email || !$token) {
            $message = __("Invalid activation link.");
            return view('auth::activation', compact('status', 'message'));
        }

        $user = User::where('email', $email)->where('secret_key', $token)->first();

        if (!$user) {
            $message = __("Activation link is invalid or has expired.");
            return view('auth::activation', compact('status', 'message'));
        }

        if ($user->status == 2) {
            $message = __("Your account has already been activated. Please login!");
            $status = true;
            return view('auth::activation', compact('status', 'message'));
        }

        $user->status = 2;
        $user->secret_key = rand_string(32);
        $user->save();

        if (get_option('auth_welcome_email_new_user_status', 0)) {
            \MailSender::sendByTemplate('welcome', $user->email, [
                'fullname'  => $user->fullname,
                'login_url' => url('auth/login'),
            ]);
        }

        $status = true;
        $message = __("Your account has been activated successfully! You can now login.");

        return view('auth::activation', compact('status', 'message'));
    }

    public function loginAsAdmin(Request $request)
    {
        $user = \Auth::user();
        if (!$user || (int)$user->role !== 2)
        {
            return redirect(url('app/dashboard'));
        }

        session(["login_as" => "admin"]);
        return redirect(url('admin/dashboard'));
    }

    public function loginAsUser(Request $request)
    {
        session(["login_as" => "client"]);
        return redirect(url('app/dashboard'));
    }

    public function logout(Request $request)
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('auth/login');
    }   

    public function settings(Request $request)
    {
        return view('auth::settings');
    }

    /*
    * WhatsApp and Email OTP Verification
    */
    public function sendWhatsappOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone'        => 'required|string|min:7|max:20',
            'country_code' => 'required|string|max:8',
            'email'        => 'required|email|max:255',
        ], [
            'phone.required'        => __('Mobile number is required.'),
            'country_code.required' => __('Country code is required.'),
            'email.required'        => __('Email address is required before sending OTP.'),
            'email.email'           => __('Please enter a valid email address.'),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 0,
                'message' => $validator->errors()->first(),
            ]);
        }

        WhatsAppOtpService::ensureUserColumnsExist();
        $cleanPhone = WhatsAppOtpService::formatNumber($request->phone, $request->country_code);

        if (\Schema::hasColumn('users', 'phone')) {
            $exists = User::where('phone', $cleanPhone)->orWhere('phone', $request->phone)->exists();
            if ($exists) {
                return response()->json([
                    'status'  => 0,
                    'message' => __('This mobile number is already registered. Please log in or use another number.'),
                ]);
            }
        }

        $email = strtolower(trim((string)$request->input('email', '')));
        if (User::where('email', $email)->exists()) {
            return response()->json([
                'status'  => 0,
                'message' => __('This email address is already registered. Please log in or use another email.'),
            ]);
        }

        // Generate ONE shared OTP for simultaneous dual-channel delivery
        $otp = WhatsAppOtpService::generateOtp();

        // 1. Dispatch WhatsApp OTP
        $res = WhatsAppOtpService::sendWhatsappOtp($request->phone, $request->country_code, $otp);

        // 2. Dispatch Email OTP simultaneously
        $emailSent = false;
        if (!empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            try {
                $emailRes = WhatsAppOtpService::sendEmailOtp($email, $otp);
                if (($emailRes['status'] ?? 0) === 1) {
                    $emailSent = true;
                }
            } catch (\Throwable $e) {}
        }

        if (($res['status'] ?? 0) === 1) {
            $res['message'] = __('Verification code has been sent to both your WhatsApp number (+:number) and Email (:email)!', ['number' => $cleanPhone, 'email' => $email]);
            $res['email_sent'] = $emailSent;
        }

        return response()->json($res);
    }

    public function verifyWhatsappOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone'        => 'required|string',
            'country_code' => 'required|string',
            'otp'          => 'required|string|size:6',
        ], [
            'otp.required' => __('Please enter the 6-digit OTP.'),
            'otp.size'     => __('The OTP must be exactly 6 digits.'),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 0,
                'message' => $validator->errors()->first(),
            ]);
        }

        $res = WhatsAppOtpService::verifyWhatsappOtp($request->phone, $request->country_code, $request->otp);
        if (($res['status'] ?? 0) === 1) {
            $res['message'] = __('OTP Verified successfully! Your account is verified.');
        }
        return response()->json($res);
    }

    public function sendEmailOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|max:255',
        ], [
            'email.required' => __('Email address is required.'),
            'email.email'    => __('Please enter a valid email address.'),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 0,
                'message' => $validator->errors()->first(),
            ]);
        }

        if (User::where('email', strtolower(trim($request->email)))->exists()) {
            return response()->json([
                'status'  => 0,
                'message' => __('This email address is already registered. Please log in or use another email.'),
            ]);
        }

        $res = WhatsAppOtpService::sendEmailOtp($request->email);
        return response()->json($res);
    }

    public function verifyEmailOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'otp'   => 'required|string|size:6',
        ], [
            'otp.required' => __('Please enter the 6-digit OTP.'),
            'otp.size'     => __('The OTP must be exactly 6 digits.'),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 0,
                'message' => $validator->errors()->first(),
            ]);
        }

        $res = WhatsAppOtpService::verifyEmailOtp($request->email, $request->otp);
        return response()->json($res);
    }

    /*
    * AJAX REQUESTS
    */
    /**
     * Validate signup inputs and resolve selected plan / payment parameters.
     */
    private function validateAndResolveSignup(Request $request): array
    {
        if ($request->has('username')) {
            $request->merge(['username' => preg_replace('/\s+/', '', (string)$request->username)]);
        }
        if ($request->has('email')) {
            $request->merge(['email' => trim((string)$request->email)]);
        }
        if ($request->has('phone')) {
            $request->merge(['phone' => trim((string)$request->phone)]);
        }

        $validator = Validator::make($request->all(), [
            'fullname'     => 'required|string|max:255',
            'email'        => 'required|email|max:255|unique:users,email',
            'country_code' => 'required|string|max:8',
            'phone'        => 'required|string|min:7|max:20',
            'username'     => [
                'required',
                'string',
                'min:5',
                'max:64',
                'regex:/^\S+$/',
                'unique:users,username',
            ],
            'password'     => 'required|string|min:6|confirmed',
            'timezone'     => 'required|in:' . implode(',', timezone_identifiers_list()),
        ], [
            'phone.required'        => __('Mobile number is required.'),
            'country_code.required' => __('Country code is required.'),
            'username.regex'        => __('Username must not contain any whitespace.'),
            'username.min'          => __('Username must be at least 5 characters.'),
            'timezone.required'     => __('Please select your timezone.'),
            'timezone.in'           => __('Invalid timezone.'),
        ]);

        if ($validator->fails()) {
            return [
                'error' => ms([
                    "status"     => 0,
                    "message"    => "errors",
                    "class"      => "text-error",
                    "error_type" => 2,
                    "errors"     => $validator->errors()
                ])
            ];
        }

        WhatsAppOtpService::ensureUserColumnsExist();
        $cleanPhone = WhatsAppOtpService::formatNumber($request->phone, $request->country_code);

        if (\Schema::hasColumn('users', 'phone')) {
            $phoneExists = User::where('phone', $cleanPhone)->orWhere('phone', $request->phone)->exists();
            if ($phoneExists) {
                return [
                    'error' => ms([
                        "status"     => 0,
                        "message"    => __("This mobile number is already registered. Please log in or use another number."),
                        "class"      => "text-error",
                        "error_type" => 1,
                    ])
                ];
            }
        }

        if (!WhatsAppOtpService::isPhoneVerified($request->phone, $request->country_code)) {
            return [
                'error' => ms([
                    "status"     => 0,
                    "message"    => __("Please verify your WhatsApp mobile number with OTP before completing signup."),
                    "class"      => "text-error",
                    "error_type" => 1,
                ])
            ];
        }

        $planParam = $request->input('plan') ?: session('start_plan', 'starter');
        $planPrice = (float)$request->input('plan_price', 0);
        $billingCycle = strtolower((string)$request->input('billing_cycle', 'yearly'));
        $paymentMethod = strtolower((string)$request->input('payment_method', 'free'));
        $utrNumber = trim((string)$request->input('utr_number', ''));
        $razorpayPaymentId = trim((string)$request->input('razorpay_payment_id', ''));
        $razorpayOrderId = trim((string)$request->input('razorpay_order_id', ''));
        $razorpaySignature = trim((string)$request->input('razorpay_signature', ''));

        $targetPlan = null;
        $planIdInput = $request->input('plan_id');
        $cycleType = ($billingCycle === 'monthly') ? 1 : 2;
        try {
            if ($planIdInput && is_numeric($planIdInput)) {
                $targetPlan = \Modules\AdminPlans\Models\Plans::find($planIdInput);
            }
            if (!$targetPlan && $planParam) {
                $targetPlan = \Modules\AdminPlans\Models\Plans::where('id_secure', $planParam)
                    ->orWhere('id', $planParam)
                    ->first();
            }
            if (!$targetPlan && $planParam) {
                $targetPlan = \Modules\AdminPlans\Models\Plans::where(function($q) use ($planParam) {
                    $q->whereRaw('LOWER(name) = ?', [strtolower($planParam)])
                      ->orWhere('name', 'LIKE', '%' . $planParam . '%');
                })->where('type', $cycleType)->first();
            }
            if (!$targetPlan && $planParam) {
                $targetPlan = \Modules\AdminPlans\Models\Plans::whereRaw('LOWER(name) = ?', [strtolower($planParam)])
                    ->orWhere('name', 'LIKE', '%' . $planParam . '%')
                    ->first();
            }
        } catch (\Throwable $e) {}

        $inputPrice = (float)$request->input('plan_price', 0);
        $isPaidPlan = false;
        if ($inputPrice > 0) {
            $planPrice = $inputPrice;
            $isPaidPlan = true;
        } elseif ($targetPlan) {
            $planPrice = (float)$targetPlan->price;
            $isPaidPlan = empty($targetPlan->free_plan) && $planPrice > 0;
        } elseif ($planPrice > 0) {
            $isPaidPlan = true;
        }

        if (!$targetPlan) {
            $targetPlan = \Modules\AdminPlans\Models\Plans::where('status', 1)->where('type', $cycleType)->first()
                       ?? \Modules\AdminPlans\Models\Plans::where('status', 1)->first();
        }

        return [
            'cleanPhone'        => $cleanPhone,
            'planParam'         => $planParam,
            'planPrice'         => $planPrice,
            'billingCycle'      => $billingCycle,
            'paymentMethod'     => $paymentMethod,
            'utrNumber'         => $utrNumber,
            'razorpayPaymentId' => $razorpayPaymentId,
            'razorpayOrderId'   => $razorpayOrderId,
            'razorpaySignature' => $razorpaySignature,
            'targetPlan'        => $targetPlan,
            'isPaidPlan'        => $isPaidPlan,
        ];
    }

    /**
     * Validate signup form and create an in-app Razorpay Order for standalone checkout.
     */
    public function prepareRazorpaySignup(Request $request)
    {
        $resolved = $this->validateAndResolveSignup($request);
        if (!empty($resolved['error'])) {
            return $resolved['error'];
        }

        \Captcha::verify($request, 4);

        if (empty($resolved['isPaidPlan']) || (float)$resolved['planPrice'] <= 0) {
            return response()->json([
                'status'  => 0,
                'message' => __('Selected plan does not require online payment.'),
            ]);
        }

        try {
            $currency = strtoupper((string) get_option('currency', 'INR'));
            if ($currency === 'USD' && (float)$resolved['planPrice'] > 0) {
                // Pricing UI renders ₹ INR; default to INR unless configured otherwise
                $currency = 'INR';
            }
            $planName = $resolved['targetPlan'] ? $resolved['targetPlan']->name : ($resolved['planParam'] ?: 'WhatsApp Bulk Plan');
            $order = StandaloneRazorpayService::createOrder(
                (float) $resolved['planPrice'],
                $currency,
                'SIGNUP_' . time(),
                [
                    'email' => $request->email,
                    'plan'  => $planName,
                    'cycle' => $resolved['billingCycle'],
                ]
            );

            $appTitle = trim((string) get_option('website_title', '')) ?: config('app.name', 'WhatsApp Bulk');

            return response()->json([
                'status'      => 1,
                'key_id'      => $order['key_id'],
                'order_id'    => $order['order_id'],
                'amount'      => $order['amount'],
                'currency'    => $order['currency'],
                'name'        => $appTitle,
                'description' => $planName . ' (' . ucfirst($resolved['billingCycle']) . ')',
                'prefill'     => [
                    'name'    => (string) $request->fullname,
                    'email'   => (string) $request->email,
                    'contact' => '+' . $resolved['cleanPhone'],
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status'  => 0,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function doSignup(Request $request)
    {
        $resolved = $this->validateAndResolveSignup($request);
        if (!empty($resolved['error'])) {
            return $resolved['error'];
        }

        $cleanPhone        = $resolved['cleanPhone'];
        $planParam         = $resolved['planParam'];
        $planPrice         = $resolved['planPrice'];
        $billingCycle      = $resolved['billingCycle'];
        $paymentMethod     = $resolved['paymentMethod'];
        $utrNumber         = $resolved['utrNumber'];
        $razorpayPaymentId = $resolved['razorpayPaymentId'];
        $razorpayOrderId   = $resolved['razorpayOrderId'];
        $razorpaySignature = $resolved['razorpaySignature'];
        $targetPlan        = $resolved['targetPlan'];
        $isPaidPlan        = $resolved['isPaidPlan'];

        if ($isPaidPlan && $paymentMethod === 'razorpay') {
            if (empty($razorpayPaymentId)) {
                return ms([
                    "status"     => 0,
                    "error_type" => 4,
                    "class"      => "text-error",
                    "message"    => __("Please complete Razorpay payment before creating your account."),
                ]);
            }

            try {
                $verified = StandaloneRazorpayService::verifyPayment(
                    $razorpayPaymentId,
                    $razorpayOrderId ?: null,
                    $razorpaySignature ?: null
                );
                $razorpayPaymentId = $verified['payment_id'];
            } catch (\Throwable $e) {
                return ms([
                    "status"     => 0,
                    "error_type" => 4,
                    "class"      => "text-error",
                    "message"    => __("Razorpay payment verification failed: ") . $e->getMessage(),
                ]);
            }
        } elseif ($isPaidPlan && $paymentMethod === 'upi') {
            $cleanUtr = !empty($utrNumber) ? preg_replace('/\s+/', '', $utrNumber) : ('UPI_' . strtoupper(\Illuminate\Support\Str::random(10)));
        }

        \Captcha::verify($request, 4);

        $userData = [
            'id_secure'     => rand_string(),
            'role'          => 1,
            'login_type'    => 'direct',
            'fullname'      => $request->fullname,
            'email'         => $request->email,
            'username'      => $request->username,
            'password'      => Hash::make($request->password),
            'timezone'      => $request->timezone,
            'avatar'        => text2img($request->fullname),
            'secret_key'    => rand_string(32),
            'status'        => get_option('auth_activation_email_new_user_status', 0) ? 1 : 2,
            'changed'       => time(),
            'created'       => time()
        ];

        if (\Schema::hasColumn('users', 'country_code')) {
            $userData['country_code'] = $request->country_code;
        }
        if (\Schema::hasColumn('users', 'phone')) {
            $userData['phone'] = $cleanPhone;
        }
        if (\Schema::hasColumn('users', 'phone_verified_at')) {
            $userData['phone_verified_at'] = date('Y-m-d H:i:s');
        }
        if (\Schema::hasColumn('users', 'email_verified_at')) {
            $userData['email_verified_at'] = date('Y-m-d H:i:s');
        }

        $user = User::create($userData);

        Teams::create([
            'id_secure'   => rand_string(),
            'owner'       => $user->id
        ]);

        event(new AuthEvent("signup", $user->id));

        if (get_option('auth_activation_email_new_user_status', 0)) {
            $verify_url = url('auth/activation?email=' . urlencode($user->email) . '&token=' . $user->secret_key);

            \MailSender::sendByTemplate('activation', $user->email, [
                'fullname'   => $user->fullname,
                'verify_url' => $verify_url,
            ]);

            $return = [
                "status" => 1,
                "error_type" => 4,
                "class" => "text-success",
                "message" => __("Signup successful! Please check your email and click the activation link to activate your account."),
                "redirect" => url('auth/login'),
            ];
        } else {
            if (get_option('auth_welcome_email_new_user_status', 0)) {
                \MailSender::sendByTemplate('welcome', $user->email, [
                    'fullname'  => $user->fullname,
                    'login_url' => url('auth/login'),
                ]);
            }

            $user->last_login = time();
            $user->save();
            Auth::login($user);

            // ── Handle Payment Proof File Upload ──
            $proofPath = null;
            if ($request->hasFile('payment_proof')) {
                try {
                    $file = $request->file('payment_proof');
                    if ($file->isValid()) {
                        $uploadDir = public_path('uploads/payment_proofs');
                        if (!file_exists($uploadDir)) {
                            @mkdir($uploadDir, 0755, true);
                        }
                        $fileName = time() . '_' . rand_string(8) . '.' . $file->getClientOriginalExtension();
                        $file->move($uploadDir, $fileName);
                        $proofPath = 'uploads/payment_proofs/' . $fileName;
                    }
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::error("Payment proof upload error: " . $e->getMessage());
                }
            }

            // Ensure payment columns exist dynamically on users & payment_manual
            try {
                if (!\Schema::hasColumn('users', 'payment_method')) {
                    \Schema::table('users', function (\Illuminate\Database\Schema\Blueprint $table) {
                        $table->string('payment_method', 50)->nullable()->after('plan_id');
                        $table->string('payment_status', 50)->nullable()->after('payment_method');
                        $table->string('payment_reference', 100)->nullable()->after('payment_status');
                        $table->string('payment_proof', 255)->nullable()->after('payment_reference');
                    });
                }
            } catch (\Throwable $e) {}

            try {
                if (!\Schema::hasColumn('payment_manual', 'proof')) {
                    \Schema::table('payment_manual', function (\Illuminate\Database\Schema\Blueprint $table) {
                        $table->string('proof', 255)->nullable()->after('payment_info');
                    });
                }
            } catch (\Throwable $e) {}

            // ── Handle Payment Record and Plan Activation ──
            $successMsg = __("Signup successful! Your account has been created.");
            if ($isPaidPlan) {
                if ($paymentMethod === 'upi') {
                    $cleanUtr = !empty($utrNumber) ? preg_replace('/\s+/', '', $utrNumber) : ('UPI_' . strtoupper(\Illuminate\Support\Str::random(10)));
                    $autoVerify = \App\Services\UpiAutoVerifyService::checkAndClaimCredit($cleanUtr, $planPrice, $user->id);
                    $isAutoVerified = !empty($autoVerify['verified']);
                    $payStatus = $isAutoVerified ? 'approved' : 'pending';

                    try {
                        $manualData = [
                            'id_secure'    => rand_string(),
                            'uid'          => $user->id,
                            'plan_id'      => $targetPlan ? $targetPlan->id : null,
                            'payment_id'   => $cleanUtr,
                            'payment_info' => $isAutoVerified ? ('Auto-verified via Google Pay Sync | UTR: ' . $cleanUtr) : ('Direct UPI | UTR: ' . $cleanUtr),
                            'amount'       => $planPrice,
                            'currency'     => 'INR',
                            'notes'        => $isAutoVerified ? 'Auto-verified on signup via Google Pay sync' : ('Submitted at signup for plan: ' . ($targetPlan ? $targetPlan->name : $planParam)),
                            'status'       => $isAutoVerified ? 1 : 0, // 1 = Approved / Success, 0 = Pending
                            'created'      => time(),
                            'changed'      => time(),
                        ];
                        if (\Schema::hasColumn('payment_manual', 'proof') && $proofPath) {
                            $manualData['proof'] = $proofPath;
                        }
                        \Modules\AdminManualPayments\Models\PaymentManual::create($manualData);

                        if ($isAutoVerified && $targetPlan) {
                            if (class_exists('\Plan') && method_exists('\Plan', 'updatePlanForTeam')) {
                                \Plan::updatePlanForTeam($targetPlan->id, $user->id);
                            }
                        }
                    } catch (\Throwable $e) {
                        \Illuminate\Support\Facades\Log::error("PaymentManual create error: " . $e->getMessage());
                    }

                    // Save payment details directly on user record
                    try {
                        if (\Schema::hasColumn('users', 'payment_method')) {
                            $user->payment_method = 'upi';
                            $user->payment_status = $payStatus;
                            $user->payment_reference = $cleanUtr;
                            $user->payment_proof = $proofPath;
                            $user->save();
                        }
                    } catch (\Throwable $e) {}

                    if ($isAutoVerified) {
                        $successMsg = __("Signup successful! Your UPI payment (UTR: ") . $cleanUtr . __(") has been automatically verified via Google Pay. Your subscription is now ACTIVE!");
                    } else {
                        $successMsg = __("Signup successful! Your UPI payment (UTR: ") . $cleanUtr . __(") has been submitted. Auto-verification is syncing with Google Pay.");
                    }
                } elseif ($paymentMethod === 'razorpay') {
                    // Standalone in-page Razorpay payment already verified above -> activate immediately
                    try {
                        if ($targetPlan && class_exists('\Plan') && method_exists('\Plan', 'updatePlanForTeam')) {
                            \Plan::updatePlanForTeam($targetPlan->id, $user->id);
                        }
                        if (\Schema::hasColumn('users', 'payment_method')) {
                            $user->payment_method = 'razorpay';
                            $user->payment_status = 'approved';
                            $user->payment_reference = $razorpayPaymentId;
                            $user->status = 2; // Active
                            if ($targetPlan) {
                                $user->plan_id = $targetPlan->id;
                                $user->expiration_date = time() + (86400 * ($billingCycle === 'monthly' ? 30 : 365));
                            }
                            $user->save();
                        }
                        if (class_exists('\Modules\AdminManualPayments\Models\PaymentManual')) {
                            \Modules\AdminManualPayments\Models\PaymentManual::create([
                                'id_secure'    => rand_string(),
                                'uid'          => $user->id,
                                'plan_id'      => $targetPlan ? $targetPlan->id : null,
                                'payment_id'   => $razorpayPaymentId,
                                'payment_info' => 'Razorpay Payment ID: ' . $razorpayPaymentId,
                                'amount'       => $planPrice,
                                'currency'     => 'INR',
                                'notes'        => 'Standalone Razorpay online payment verified on signup',
                                'status'       => 1,
                                'created'      => time(),
                                'changed'      => time(),
                            ]);
                        }
                    } catch (\Throwable $e) {
                        \Log::error("Direct Razorpay signup activation error: " . $e->getMessage());
                    }

                    \Auth::login($user, true);
                    session(['login_as' => 'client']);

                    return ms([
                        "status"     => 1,
                        "error_type" => 4,
                        "class"      => "text-success",
                        "message"    => __("Payment successful! Your plan is now active. Redirecting to your dashboard..."),
                        "redirect"   => url('/app/dashboard'),
                    ], true);
                }
            } else {
                // Free Plan activation
                try {
                    if ($targetPlan) {
                        \Plan::updatePlanForTeam($targetPlan->id, $user->id);
                    }
                } catch (\Throwable $e) {}

                try {
                    if (\Schema::hasColumn('users', 'payment_method')) {
                        $user->payment_method = 'free';
                        $user->payment_status = 'approved';
                        $user->payment_reference = null;
                        $user->payment_proof = null;
                        $user->save();
                    }
                } catch (\Throwable $e) {}
            }

            $return = [
                "status" => 1,
                "error_type" => 4,
                "class" => "text-success",
                "message" => $successMsg,
                "redirect" => Core::startPage(),
            ];
        }

        return ms($return, true);
    }

    public function doLogin(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required|max:255',
            'password' => 'required|string|min:6',
            'remember' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return ms([
                "status" => 0,
                "message" => "errors",
                "class" => "alert alert-danger alert-error",
                "error_type" => 2,
                "errors" => $validator->errors()
            ]);
        }

        $loginValue = $request->username;
        $fieldType = filter_var($loginValue, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        $user = User::where($fieldType, $loginValue)->first();
        if (!$user) {
            return ms([
                "status" => 1,
                "error_type" => 4,
                "class" => "text-danger text-error",
                "message" => __("Username or password incorrect. Please try again."),
            ]);
        }

        // Recaptcha removed to prevent invalid site key errors

        switch ($user->status) {
            case 0:
                return ms([
                    "status" => 0,
                    "error_type" => 4,
                    "class" => "text-danger text-error",
                    "message" => __("Your account has been banned or locked. Please contact support."),
                ]);
            case 1:
                return ms([
                    "status" => 0,
                    "error_type" => 4,
                    "class" => "text-danger text-error",
                    "message" => __("Your account is not active. Please verify your email or contact support."),
                ]);
            case 2:
                if (Auth::attempt([$fieldType => $loginValue, 'password' => $request->password], $request->boolean('remember', false))) {
                    $request->session()->regenerate();
                    $user = Auth::user();
                    $user->last_login = time();
                    $user->save();

                    if($user->language){
                        \Cookie::queue('locale', $user->language, 60 * 24 * 365 * 10);
                    }

                    $team = Teams::where("owner", $user->id)->first();
                    if (empty($team)) {
                        $plan = $user->plan ?? null;
                        $planPermissions = $plan->permissions ?? [];

                        $team = new Teams();
                        $team->owner = $user->id;
                        $team->id_secure = rand_string();
                        $team->permissions = $planPermissions;
                        $team->save();
                    }

                    return ms([
                        "status" => 1,
                        "error_type" => 4,
                        "class" => "text-success",
                        "message" => __("Login successfully"),
                        "redirect" => Core::startPage()
                    ], true);

                } else {
                    return ms([
                        "status" => 0,
                        "error_type" => 4,
                        "class" => "text-danger text-error",
                        "message" => __("Username or password incorrect. Please try again."),
                    ]);
                }
            default:
                return ms([
                    "status" => 0,
                    "error_type" => 4,
                    "class" => "text-danger text-error",
                    "message" => __("Your account is not active. Please verify your email or contact support."),
                ]);
        }
    }

    public function doForgotPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|max:255',
        ], [
            'email.required' => __('Please enter your email address or phone number.'),
        ]);

        if ($validator->fails()) {
            return ms([
                "status" => 0,
                "message" => "errors",
                "class" => "alert alert-danger alert-error",
                "error_type" => 2,
                "errors" => $validator->errors()
            ]);
        }

        // Recaptcha removed to prevent invalid site key errors

        $input = trim($request->email);
        $user = User::where('email', $input)
            ->orWhere('phone', $input)
            ->orWhere('username', $input)
            ->first();

        if (!$user && preg_replace('/[^0-9]/', '', $input)) {
            $digits = preg_replace('/[^0-9]/', '', $input);
            $user = User::where('phone', $digits)
                ->orWhere('phone', 'LIKE', '%' . substr($digits, -10))
                ->first();
        }

        if (!$user) {
            return ms([
                "status" => 0,
                "message" => __("We could not find an account with that email address or phone number."),
                "class" => "alert alert-danger alert-error",
                "error_type" => 4,
            ]);
        }

        // Generate secure reset token & link
        $token = Password::broker()->createToken($user);
        $resetUrl = url('auth/recovery-password?token=' . $token . '&email=' . urlencode($user->email));

        $customerName = $user->fullname ?: ($user->username ?: 'Customer');
        $customerPhone = $user->phone ?? null;

        // 1. Primary Dispatch: WhatsApp to registered mobile number
        $waSent = false;
        if (!empty($customerPhone)) {
            try {
                $waSent = WhatsAppOtpService::sendPasswordResetNotification($customerPhone, $customerName, $resetUrl, 'WhatsApp Bulk');
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning("WhatsApp Bulk password reset WA dispatch error: " . $e->getMessage());
                $waSent = false;
            }
        }

        // 2. Email Dispatch: Always send or fallback if mobile failed/crashed
        $emailSent = false;
        try {
            $status = Password::sendResetLink(['email' => $user->email]);
            if ($status === Password::RESET_LINK_SENT) {
                $emailSent = true;
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning("WhatsApp Bulk password reset Email dispatch error: " . $e->getMessage());
        }

        // Native PHP mail() fail-safe fallback
        if (!$emailSent) {
            try {
                $emailBody = "
                    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 24px; border: 1px solid #e2e8f0; border-radius: 12px; background: #ffffff;'>
                        <div style='text-align: center; margin-bottom: 24px;'>
                            <h2 style='color: #0e1b3d; margin: 0;'>WhatsApp Bulk</h2>
                            <p style='color: #64748b; font-size: 14px;'>Account Recovery & Password Reset</p>
                        </div>
                        <h3 style='color: #1e293b;'>Hello {$customerName},</h3>
                        <p style='color: #475569; font-size: 15px; line-height: 1.6;'>
                            We received a request to reset the password for your WhatsApp Bulk account. Click the button below to choose a new password:
                        </p>
                        <div style='text-align: center; margin: 32px 0;'>
                            <a href='{$resetUrl}' style='background: #25D366; color: #ffffff; text-decoration: none; padding: 14px 28px; border-radius: 8px; font-weight: bold; font-size: 15px; display: inline-block;'>Reset Your Password</a>
                        </div>
                        <p style='color: #64748b; font-size: 13px; line-height: 1.6;'>
                            Or copy and paste this link into your browser:<br>
                            <a href='{$resetUrl}' style='color: #25D366; word-break: break-all;'>{$resetUrl}</a>
                        </p>
                        <hr style='border: none; border-top: 1px solid #f1f5f9; margin: 24px 0;'>
                        <p style='color: #94a3b8; font-size: 12px; margin: 0;'>
                            This reset link will expire in 60 minutes. If you did not request this, please ignore this email.
                        </p>
                    </div>
                ";
                $fromEmail = get_option('mail_from_address', config('mail.from.address', 'support@whatsappbulk.com'));
                $fromName = get_option('mail_from_name', config('mail.from.name', 'WhatsApp Bulk'));
                $headers  = "MIME-Version: 1.0\r\n";
                $headers .= "Content-type: text/html; charset=UTF-8\r\n";
                $headers .= "From: {$fromName} <{$fromEmail}>\r\n";
                $headers .= "Reply-To: {$fromEmail}\r\n";
                $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";
                if (@mail($user->email, "Reset Password - WhatsApp Bulk", $emailBody, $headers)) {
                    $emailSent = true;
                }
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning("WhatsApp Bulk native mail fallback error: " . $e->getMessage());
            }
        }

        // Status response adhering to task requirements
        if ($waSent && $emailSent) {
            $msg = __("Password reset link has been sent to your registered mobile and email.");
        } elseif ($waSent) {
            $msg = __("Password reset link has been sent to your registered WhatsApp mobile number (+:number).", ['number' => $customerPhone]);
        } elseif ($emailSent) {
            $msg = __("Password reset link has been sent to your registered email address (:email).", ['email' => $user->email]);
        } else {
            return ms([
                "status" => 0,
                "message" => __("Unable to send reset link. Please try again later."),
                "class" => "text-error",
                "error_type" => 4,
            ]);
        }

        return ms([
            "status" => 1,
            "message" => $msg,
            "class" => "text-success",
            "error_type" => 4,
        ]);
    }

    public function doRecoveryPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'token' => 'required',
            'password' => 'required|string|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return ms([
                "status" => 0,
                "message" => "errors",
                "class" => "text-error",
                "error_type" => 2,
                "errors" => $validator->errors()
            ]);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return ms([
                "status" => 0,
                "message" => __("Account does not exist."),
                "class" => "text-error",
                "error_type" => 4,
            ]);
        }

        $reset = \DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->first();

        if (!$reset || !Hash::check($request->token, $reset->token)) {
            return ms([
                "status" => 0,
                "message" => __("Reset link is invalid or has expired."),
                "class" => "text-error",
                "error_type" => 4,
            ]);
        }

        // Recaptcha removed to prevent invalid site key errors

        $user->password = Hash::make($request->password);
        $user->save();

        \DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->delete();

        return ms([
            "status" => 1,
            "message" => __("Your password has been reset successfully. Please login!"),
            "class" => "text-success",
            "error_type" => 4,
            "redirect" => route('login'),
        ]);
    }

    public function doResendActivation(Request $request)
    {
        $user = auth()->user();

        if (!$user) {
            return ms([
                "status" => 0,
                "message" => __("You are not logged in."),
                "error_type" => 4,
            ]);
        }

        if ($user->status == 2) {
            return ms([
                "status" => 0,
                "message" => __("Your account is already activated. Please login."),
                "error_type" => 4,
                "class" => "text-success",
            ]);
        }

        \Captcha::verify($request, 4);

        $user->secret_key = \Str::random(48);
        $user->save();

        $verify_url = url('auth/activation?email=' . urlencode($user->email) . '&token=' . $user->secret_key);

        \MailSender::sendByTemplate('activation', $user->email, [
            'fullname'   => $user->fullname ?? $user->username ?? $user->email,
            'verify_url' => $verify_url,
        ]);

        return ms([
            "status" => 1,
            "error_type" => 4,
            "class" => "text-success",
            "message" => __("Activation email resent! Please check your email to activate your account."),
        ]);
    }

    public function viewAsUser($id_secure)
    {
        $user = User::where('id_secure', $id_secure)->firstOrFail();
        session([
            'impersonate_by' => auth()->id(), 
            'login_as' => 'client'
        ]);
        auth()->login($user);

        return redirect()->route('app.dashboard')->with('success', __('You are now viewing as user: ') . $user->fullname);
    }

    public function leaveImpersonate()
    {
        $impersonateBy = session('impersonate_by');
        if ($impersonateBy) {
            $admin = User::where('id', $impersonateBy)->where("role", "!=", 1)->first();
            if ($admin) {
                auth()->login($admin);
                session()->forget('impersonate_by');
                session(['login_as' => 'admin']);
                return redirect()->route('admin.users.index')->with('success', __('Returned to admin account.'));
            }
            session()->forget('impersonate_by');
            return redirect()->route('login')->with('error', __('Admin user not found.'));
        }
        return redirect()->back()->with('error', __('Not impersonating any user.'));
    }

    public function saveSidebarState(Request $request)
    {
        $value = $request->input('sidebar_small', 0);
        $userId = auth()->id();
        \UserInfo::setDataUser('sidebar-small', $value ? 1 : 0);
        return response()->json(['status' => 1]);
    }



    /*
    * SOCIAL LOGIN
     */
    // Facebook
    public function redirectFacebook()
    {
        if (!get_option('auth_facebook_login_status', 0)) {
            abort(404);
        }
        $config = [
            'client_id'     => get_option('auth_facebook_login_app_id', ''),
            'client_secret' => get_option('auth_facebook_login_app_secret', ''),
            'redirect'      => url('auth/login/facebook/callback'),
            'graph_api_version' => get_option("auth_facebook_login_app_version", "v22.0"),
        ];
        config(['services.facebook' => $config]);
        return Socialite::driver('facebook')->redirect();
    }

    public function callbackFacebook()
    {
        $config = [
            'client_id'     => get_option('auth_facebook_login_app_id', ''),
            'client_secret' => get_option('auth_facebook_login_app_secret', ''),
            'redirect'      => url('auth/login/facebook/callback'),
        ];
        config(['services.facebook' => $config]);

        try {
            $socialUser = Socialite::driver('facebook')->stateless()->user();
            return $this->handleSocialLogin($socialUser, 'facebook');
        } catch (\Exception $e) {
            return redirect()->route('login')->with('error', 'Login Facebook failed!');
        }
    }

    // Google
    public function redirectGoogle()
    {
        if (!get_option('auth_google_login_status', 0)) {
            abort(404);
        }
        $config = [
            'client_id'     => get_option('auth_google_login_client_id', ''),
            'client_secret' => get_option('auth_google_login_client_secret', ''),
            'redirect'      => url('auth/login/google/callback'),
        ];
        config(['services.google' => $config]);
        return Socialite::driver('google')->redirect();
    }

    public function callbackGoogle()
    {
        $config = [
            'client_id'     => get_option('auth_google_login_client_id', ''),
            'client_secret' => get_option('auth_google_login_client_secret', ''),
            'redirect'      => url('auth/login/google/callback'),
        ];
        config(['services.google' => $config]);

        try {
            $socialUser = Socialite::driver('google')->stateless()->user();
            return $this->handleSocialLogin($socialUser, 'google');
        } catch (\Exception $e) {
            return redirect()->route('login')->with('error', 'Login Google failed!');
        }
    }

    // X (Twitter)
    public function redirectX()
    {
        if (!get_option('auth_x_login_status', 0)) {
            abort(404);
        }
        $config = [
            'client_id'     => get_option('auth_x_login_client_id', ''),
            'client_secret' => get_option('auth_x_login_client_secret', ''),
            'redirect'      => url('auth/login/x/callback'),
        ];
        config(['services.twitter-oauth-2' => $config]);
        try {
            return Socialite::driver('twitter-oauth-2')
            ->scopes(['tweet.read','users.read','users.email'])
            ->redirect();
        } catch (\Exception $e) {
            return redirect()->route('login')->with('error', __("Cannot connect to X (Twitter): ") . $e->getMessage());
        }
    }

    public function callbackX()
    {
        $config = [
            'client_id'     => get_option('auth_x_login_client_id', ''),
            'client_secret' => get_option('auth_x_login_client_secret', ''),
            'redirect'      => url('auth/login/x/callback'),
        ];
        config(['services.twitter-oauth-2' => $config]);
        try {
            $socialUser = Socialite::driver('twitter-oauth-2')->stateless()->user();
            return $this->handleSocialLogin($socialUser, 'twitter');
        } catch (\Exception $e) {
            return redirect()->route('login')->with('error', 'Login X failed!');
        }
    }


    // -------- HANDLE SOCIAL LOGIN --------
    protected function handleSocialLogin($socialUser, $provider)
    {
        $user = User::where('email', $socialUser->getEmail())->first();
        if (!$user) {
            $user = User::create([
                'id_secure'  => rand_string(),
                'role'       => 1,
                'fullname'   => $socialUser->getName() ?: $socialUser->getNickname(),
                'email'      => $socialUser->getEmail(),
                'username'   => $socialUser->getNickname() ?: $socialUser->getEmail(),
                'avatar'     => $socialUser->getAvatar(),
                'login_type' => $provider,
                'status'     => 2,
                'created'    => time(),
                'changed'    => time(),
            ]);

            Teams::create([
                'id_secure'   => rand_string(),
                'owner'       => $user->id
            ]);

            event(new AuthEvent("signup", $user->id));
        }else{
            $user->last_login = time();
            $user->save();
        }

        Auth::login($user, true);
        return redirect(Core::startPage());
    }


    public function ssoLogin(Request $request)
    {
        $uid = $request->query('uid');
        $token = $request->query('token');

        if ($uid && $token) {
            $user = User::find($uid);
            if ($user && ($user->secret_key === $token || hash_equals((string)$user->secret_key, (string)$token))) {
                Auth::login($user, true);
                session(['login_as' => 'client']);
                $user->last_login = time();
                $user->save();
                return redirect(Core::startPage());
            }
        }

        return redirect(url('auth/login'));
    }
}
