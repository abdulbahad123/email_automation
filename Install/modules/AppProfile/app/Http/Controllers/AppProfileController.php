<?php

namespace Modules\AppProfile\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Modules\AdminPaymentHistory\Models\PaymentHistory;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\User;

class AppProfileController extends Controller
{
    public function index(Request $request, $page = '')
    {
        $userId = \Auth::id();
        $user = \Auth::user();

        // Auto-heal "No Plan" issue if user has a paid payment or plan assigned
        if ($user && (!$user->plan_id || $user->plan_id == 0)) {
            $healPlan = null;
            if (class_exists('\Modules\AdminManualPayments\Models\PaymentManual')) {
                $manual = \Modules\AdminManualPayments\Models\PaymentManual::where('uid', $user->id)
                    ->where('status', 1)
                    ->orderBy('id', 'desc')
                    ->first();
                if ($manual && $manual->plan_id && class_exists('\Modules\AdminPlans\Models\Plans')) {
                    $healPlan = \Modules\AdminPlans\Models\Plans::find($manual->plan_id);
                }
            }
            if (!$healPlan && in_array(strtolower((string)$user->payment_method), ['razorpay', 'upi']) && class_exists('\Modules\AdminPlans\Models\Plans')) {
                $healPlan = \Modules\AdminPlans\Models\Plans::where('free_plan', 0)->first() 
                         ?? \Modules\AdminPlans\Models\Plans::first();
            }
            if ($healPlan) {
                try {
                    if (class_exists('\Plan') && method_exists('\Plan', 'updatePlanForTeam')) {
                        \Plan::updatePlanForTeam($healPlan->id, $user->id);
                        $user->refresh();
                    }
                } catch (\Throwable $e) {}
            }
        }

        switch ($page) {
            case 'billing':

                $billings = PaymentHistory::with('plan')->where("uid", $userId)->orderBy('created', 'desc')->paginate(20);

                return view('appprofile::billing', compact('billings'));
                break;

            case 'plan':
                return view('appprofile::plan');

                break;
            case 'settings':
                return view('appprofile::settings');
                break;
            
            default:
                return view('appprofile::index');
                break;
        }
        
    }

    public function showInvoice(Request $request, $id_secure)
    {
        $userId = \Auth::id();
        $invoice = PaymentHistory::with('plan')
            ->where('uid', $userId)
            ->where('id_secure', $id_secure)
            ->firstOrFail();

        return view('appprofile::invoice-detail', compact('invoice'));
    }

    public function downloadInvoice($id_secure)
    {
        $invoice = PaymentHistory::with('plan')
            ->where('id_secure', $id_secure)
            ->firstOrFail();

        $pdf = Pdf::loadView('appprofile::invoice-pdf', compact('invoice'));

        $filename = 'Invoice_'.$invoice->id_secure.'.pdf';

        return $pdf->download($filename);

    }

    public function activateFreePlan($plan_id){
        return \Plan::activateFreePlan($plan_id);
    }

    public function updateProfile(Request $request)
    {
        $user = User::findOrFail(auth()->id());

        if (!get_option('auth_user_change_email_status', 0)) {
            $request->merge(['email' => $user->email]);
        }

        if (!get_option('auth_user_change_username_status', 0)) {
            $request->merge(['username' => $user->username]);
        }

        $validator = \Validator::make($request->all(), [
            'fullname' => 'required|min:3',
            'username' => 'required|min:5|regex:/^\S+$/',
            'email'    => 'required|email|unique:users,email,' . $user->id,
            'timezone' => 'required',
            'avatar'   => function_exists('finfo_open') ? 'nullable|image' : ['nullable', 'mimes:jpg,jpeg,png,gif,webp,bmp,svg'],
        ], [
            'fullname.required' => __('Full name is required.'),
            'fullname.min'      => __('Full name must be at least :min characters.'),
            'username.required' => __('Username is required.'),
            'username.min'      => __('Username must be at least :min characters.'),
            'username.regex'    => __('Username must not contain spaces.'),
            'email.required'    => __('Email is required.'),
            'email.email'       => __('Please provide a valid email address.'),
            'email.unique'      => __('This email is already taken.'),
            'timezone.required' => __('Timezone is required.'),
            'avatar.image'      => __('Avatar must be an image.'),
        ]);

        if ($validator->fails()) {
            return response()->json([
                "status" => 0,
                "message" => "errors",
                "error_type" => 4,
                "errors" => $validator->errors()
            ]);
        }

        $values = [
            'fullname' => $request->input('fullname'),
            'username' => $request->input('username'),
            'email'    => $request->input('email'),
            'timezone' => $request->input('timezone'),
            'language' => $request->input('language'),
            'changed'  => time()
        ];

        if ($request->hasFile('avatar')) {
            \UploadFile::deleteFileFromServer($user->avatar);
            $avatar = \UploadFile::storeSingleFile($request->file('avatar'), 'avatars', true);
            $values['avatar'] = $avatar;
        }

        $user->update($values);

        return response()->json([
            "status" => 1,
            "message" => __("Profile updated successfully.")
        ]);
    }

    public function changePassword(Request $request)
    {
        $user = auth()->user();

        $validator = Validator::make($request->all(), [
            'current_password'      => ['required'],
            'password'              => ['required', 'min:6', 'confirmed'],
        ], [
            'current_password.required' => __('Current password is required.'),
            'password.required'         => __('New password is required.'),
            'password.min'              => __('New password must be at least :min characters.', ['min' => 6]),
            'password.confirmed'        => __('Password confirmation does not match.'),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 0,
                'message' => __('Validation errors.'),
                'error_type' => 4,
                'errors' => $validator->errors()
            ]);
        }

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'status' => 0,
                'message' => __('Current password is incorrect.'),
                'error_type' => 1,
                'errors' => [
                    'current_password' => [__('Current password is incorrect.')]
                ]
            ]);
        }

        // Update password
        $user->password = bcrypt($request->password);
        $user->save();

        return response()->json([
            'status' => 1,
            'message' => __('Password changed successfully.')
        ]);
    }

    public function syncPendingPlan(Request $request)
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json(['status' => 0, 'message' => __('Unauthorized')]);
        }

        $planId = $request->input('plan_id');
        $planName = $request->input('plan_name');
        $cycle = (int)$request->input('billing_cycle', 1);
        $payMethod = $request->input('payment_method', 'upi');

        $targetPlan = null;
        if (class_exists('\Modules\AdminPlans\Models\Plans')) {
            if ($planId && is_numeric($planId)) {
                $targetPlan = \Modules\AdminPlans\Models\Plans::find($planId);
            }
            if (!$targetPlan && $planId) {
                $targetPlan = \Modules\AdminPlans\Models\Plans::where('id_secure', $planId)->first();
            }
            if (!$targetPlan && $planName) {
                $cleanName = trim(str_ireplace(['whatsappbulk', 'plan'], '', $planName));
                $targetPlan = \Modules\AdminPlans\Models\Plans::where('name', 'LIKE', '%' . $cleanName . '%')
                    ->where('type', $cycle)
                    ->first()
                    ?? \Modules\AdminPlans\Models\Plans::where('name', 'LIKE', '%' . $cleanName . '%')->first();
            }
            if (!$targetPlan) {
                $targetPlan = \Modules\AdminPlans\Models\Plans::where('status', 1)->first();
            }
        }

        if ($targetPlan) {
            $user->plan_id = $targetPlan->id;
            if (!empty($payMethod) && in_array(strtolower($payMethod), ['upi', 'razorpay', 'free'])) {
                $user->payment_method = strtolower($payMethod);
            }
            $days = ($cycle == 2) ? 365 : 30;
            $user->expiration_date = time() + ($days * 86400);
            $user->save();

            if (class_exists('\Plan') && method_exists('\Plan', 'updatePlanForTeam')) {
                try {
                    \Plan::updatePlanForTeam($targetPlan->id, $user->id);
                } catch (\Throwable $e) {}
            }

            return response()->json([
                'status' => 1,
                'message' => __('Plan synchronized successfully'),
                'plan_name' => $targetPlan->name
            ]);
        }

        return response()->json(['status' => 0, 'message' => __('No matching plan found')]);
    }

    public function processRenewal(Request $request)
    {
        $user = auth()->user();
        if (!$user) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['status' => 0, 'message' => __('Unauthorized. Please log in.')], 401);
            }
            return redirect()->route('login');
        }

        $planId = $request->input('plan_id') ?: $request->input('package_id');
        $term = strtolower((string)$request->input('term', $request->input('cycle', 'monthly')));
        $isYearly = in_array($term, ['yearly', '2', 2], true);
        $cycleInt = $isYearly ? 2 : 1;
        $durationDays = $isYearly ? 365 : 30;

        // Fetch target plan dynamically from replix_automation.plans
        $targetPlan = null;
        if (class_exists('\Modules\AdminPlans\Models\Plans')) {
            if ($planId && is_numeric($planId)) {
                $targetPlan = \Modules\AdminPlans\Models\Plans::find($planId);
            }
            if (!$targetPlan && $planId) {
                $targetPlan = \Modules\AdminPlans\Models\Plans::where('id_secure', $planId)->first();
            }
            if ($targetPlan && $targetPlan->type != $cycleInt) {
                $cleanName = trim(str_ireplace(['monthly', 'yearly', 'annual', 'plan'], '', $targetPlan->name));
                $altPlan = \Modules\AdminPlans\Models\Plans::where('name', 'LIKE', '%' . $cleanName . '%')
                    ->where('type', $cycleInt)
                    ->where('status', 1)
                    ->first();
                if ($altPlan) {
                    $targetPlan = $altPlan;
                }
            }
            if (!$targetPlan) {
                $targetPlan = \Modules\AdminPlans\Models\Plans::where('type', $cycleInt)->where('status', 1)->first()
                           ?? \Modules\AdminPlans\Models\Plans::where('status', 1)->first();
            }
        }

        if (!$targetPlan) {
            return response()->json(['status' => 0, 'message' => __('Selected plan not found.')], 404);
        }

        $inputPrice = (float)$request->input('price', 0);
        $planPrice = (float)($targetPlan->price ?? 39);
        if ($inputPrice > 0) {
            $price = $inputPrice;
        } elseif ($isYearly && $planPrice <= 100) {
            $price = $planPrice;
        } else {
            $price = $planPrice;
        }

        $paymentMethod = strtolower((string)$request->input('payment_method', 'razorpay'));

        // ==========================================
        // ==========================================
        // 1. RAZORPAY GATEWAY -> IN-APP ON-PAGE CHECKOUT
        // ==========================================
        if ($paymentMethod === 'razorpay') {
            $razorpayPaymentId = trim((string) $request->input('razorpay_payment_id', ''));
            $razorpayOrderId = trim((string) $request->input('razorpay_order_id', ''));
            $razorpaySignature = trim((string) $request->input('razorpay_signature', ''));

            if (empty($razorpayPaymentId)) {
                try {
                    $order = \App\Services\StandaloneRazorpayService::createOrder(
                        (float) $price,
                        'INR',
                        'RENEW_' . $user->id . '_' . time(),
                        [
                            'user_id' => $user->id,
                            'plan_id' => $targetPlan->id,
                            'term'    => $isYearly ? 'yearly' : 'monthly',
                        ]
                    );
                    $appTitle = function_exists('get_option') ? (trim((string) get_option('website_title', '')) ?: config('app.name', 'WhatsApp Bulk')) : config('app.name', 'WhatsApp Bulk');

                    return response()->json([
                        'status'         => 1,
                        'razorpay_popup' => true,
                        'key_id'         => $order['key_id'],
                        'order_id'       => $order['order_id'],
                        'amount'         => $order['amount'],
                        'currency'       => $order['currency'],
                        'name'           => $appTitle,
                        'description'    => $targetPlan->name . ' (' . ($isYearly ? 'Yearly' : 'Monthly') . ')',
                        'prefill'        => [
                            'name'    => (string) ($user->fullname ?: ($user->username ?: 'Customer')),
                            'email'   => (string) ($user->email ?? ''),
                            'contact' => (string) ($user->phone ?? ''),
                        ],
                    ]);
                } catch (\Throwable $e) {
                    return response()->json([
                        'status'  => 0,
                        'message' => __('Unable to initiate Razorpay checkout: ') . $e->getMessage(),
                    ], 422);
                }
            }

            try {
                $verified = \App\Services\StandaloneRazorpayService::verifyPayment(
                    $razorpayPaymentId,
                    $razorpayOrderId ?: null,
                    $razorpaySignature ?: null
                );
                $razorpayPaymentId = $verified['payment_id'];
            } catch (\Throwable $e) {
                return response()->json([
                    'status'  => 0,
                    'message' => __('Razorpay payment verification failed: ') . $e->getMessage(),
                ], 422);
            }

            $now = time();
            $baseTime = ($user->expiration_date && $user->expiration_date > $now) ? (int) $user->expiration_date : $now;
            $newExpiration = $baseTime + ($durationDays * 86400);

            $user->plan_id = $targetPlan->id;
            $user->expiration_date = $newExpiration;
            $user->payment_method = 'razorpay';
            $user->payment_status = 'approved';
            $user->payment_reference = $razorpayPaymentId;
            $user->status = 2;
            $user->save();

            try {
                \Modules\AppTeams\Models\Teams::where('owner', $user->id)->update([
                    'permissions' => $targetPlan->permissions
                ]);
            } catch (\Throwable $e) {}

            if (class_exists('\Plan') && method_exists('\Plan', 'updatePlanForTeam')) {
                try {
                    \Plan::updatePlanForTeam($targetPlan->id, $user->id);
                } catch (\Throwable $e) {}
            }

            if (class_exists('\Modules\AdminManualPayments\Models\PaymentManual')) {
                try {
                    $secureId = function_exists('rand_string') ? rand_string() : md5(uniqid((string) rand(), true));
                    \Modules\AdminManualPayments\Models\PaymentManual::create([
                        'id_secure'    => $secureId,
                        'uid'          => $user->id,
                        'plan_id'      => $targetPlan->id,
                        'payment_id'   => $razorpayPaymentId,
                        'payment_info' => 'Razorpay Renewal | Plan: ' . $targetPlan->name . ' (' . ($isYearly ? 'Yearly' : 'Monthly') . ') | Payment ID: ' . $razorpayPaymentId,
                        'amount'       => $price,
                        'currency'     => 'INR',
                        'notes'        => 'Renewed online via Razorpay Checkout',
                        'status'       => 1,
                        'created'      => time(),
                        'changed'      => time(),
                    ]);
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::error("Razorpay Renewal PaymentManual error: " . $e->getMessage());
                }
            }

            try {
                \Illuminate\Support\Facades\Auth::setUser($user->fresh());
            } catch (\Throwable $e) {}

            $formattedExp = date('d M, Y', $newExpiration);
            $successMsg = __('Payment verified! Your plan has been renewed until ') . $formattedExp;
            $redirectUrl = url('/app/profile/plan?renewed=1');

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'status'          => 1,
                    'is_pending'      => false,
                    'message'         => $successMsg,
                    'plan_name'       => $targetPlan->name,
                    'expiration_date' => $formattedExp,
                    'redirect'        => $redirectUrl,
                ]);
            }

            return redirect()->to('/app/profile/plan?renewed=1')->with('success', $successMsg);
        }

        // ==========================================
        // 2. UPI PAYMENT -> LOCAL ON-PAGE ONLY
        // ==========================================
        $utr = trim((string)$request->input('utr_number', ''));
        if (empty($utr)) {
            $utr = 'UPI_' . strtoupper(\Illuminate\Support\Str::random(10));
        }

        // Upload payment receipt proof if provided
        $proofPath = null;
        if ($request->hasFile('receipt')) {
            try {
                $file = $request->file('receipt');
                $filename = 'renewal_upi_' . time() . '_' . \Illuminate\Support\Str::random(6) . '.' . $file->getClientOriginalExtension();
                $destDir = public_path('uploads/renewal_proofs');
                if (!file_exists($destDir)) {
                    mkdir($destDir, 0755, true);
                }
                $file->move($destDir, $filename);
                $proofPath = 'uploads/renewal_proofs/' . $filename;
            } catch (\Throwable $e) {}
        }

        // Check auto-verify if service exists
        $isAutoVerified = false;
        if (class_exists('\App\Services\UpiAutoVerifyService')) {
            try {
                $autoRes = \App\Services\UpiAutoVerifyService::checkAndClaimCredit($utr, $price, $user->id);
                $isAutoVerified = !empty($autoRes['verified']);
            } catch (\Throwable $e) {}
        }

        if ($isAutoVerified) {
            // Calculate extended expiration date
            $now = time();
            $baseTime = ($user->expiration_date && $user->expiration_date > $now) ? (int)$user->expiration_date : $now;
            $newExpiration = $baseTime + ($durationDays * 86400);

            // Update user subscription immediately (status = 2 is ACTIVE in Stackposts)
            $user->plan_id = $targetPlan->id;
            $user->expiration_date = $newExpiration;
            $user->payment_method = 'upi';
            $user->payment_status = 'approved';
            $user->payment_reference = $utr;
            if ($proofPath) {
                $user->payment_proof = $proofPath;
            }
            $user->status = 2; // Active
            $user->save();

            // Ensure user team permissions match target plan
            try {
                \Modules\AppTeams\Models\Teams::where('owner', $user->id)->update([
                    'permissions' => $targetPlan->permissions
                ]);
            } catch (\Throwable $e) {}

            if (class_exists('\Plan') && method_exists('\Plan', 'updatePlanForTeam')) {
                try {
                    \Plan::updatePlanForTeam($targetPlan->id, $user->id);
                } catch (\Throwable $e) {}
            }

            // Record in payment_manual with approved status (1)
            if (class_exists('\Modules\AdminManualPayments\Models\PaymentManual')) {
                try {
                    $secureId = function_exists('rand_string') ? rand_string() : md5(uniqid(rand(), true));
                    $manualData = [
                        'id_secure'    => $secureId,
                        'uid'          => $user->id,
                        'plan_id'      => $targetPlan->id,
                        'payment_id'   => $utr,
                        'payment_info' => 'On-page UPI Renewal | Plan: ' . $targetPlan->name . ' (' . ($isYearly ? 'Yearly' : 'Monthly') . ') | UTR: ' . $utr . ($proofPath ? ' | Proof: ' . $proofPath : ''),
                        'amount'       => $price,
                        'currency'     => 'INR',
                        'notes'        => 'Renewed via on-page UPI modal (Auto-verified via Google Pay sync)',
                        'status'       => 1,
                        'created'      => time(),
                        'changed'      => time(),
                    ];
                    if (\Schema::hasColumn('payment_manual', 'proof') && $proofPath) {
                        $manualData['proof'] = $proofPath;
                    }
                    \Modules\AdminManualPayments\Models\PaymentManual::create($manualData);
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::error("Renewal PaymentManual create error: " . $e->getMessage());
                }
            }

            // Refresh user in auth session
            try {
                \Illuminate\Support\Facades\Auth::setUser($user->fresh());
            } catch (\Throwable $e) {}

            $formattedExp = date('d M, Y', $newExpiration);
            $successMsg = __('Your plan has been renewed successfully! Active until ') . $formattedExp;
            $redirectUrl = url('/app/profile/plan?renewed=1');

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'status'          => 1,
                    'is_pending'      => false,
                    'message'         => $successMsg,
                    'plan_name'       => $targetPlan->name,
                    'expiration_date' => $formattedExp,
                    'redirect'        => $redirectUrl
                ]);
            }

            return redirect()->to('/app/profile/plan?renewed=1')->with('success', $successMsg);
        } else {
            // Unverified manual UPI submission -> Pending Admin Verification
            $user->payment_method = 'upi';
            $user->payment_status = 'pending';
            $user->payment_reference = $utr;
            if ($proofPath) {
                $user->payment_proof = $proofPath;
            }
            $user->status = 2; // Keep workspace active
            $user->save();

            // Record in payment_manual with pending status (0)
            if (class_exists('\Modules\AdminManualPayments\Models\PaymentManual')) {
                try {
                    $secureId = function_exists('rand_string') ? rand_string() : md5(uniqid(rand(), true));
                    $manualData = [
                        'id_secure'    => $secureId,
                        'uid'          => $user->id,
                        'plan_id'      => $targetPlan->id,
                        'payment_id'   => $utr,
                        'payment_info' => 'On-page UPI Renewal | Plan: ' . $targetPlan->name . ' (' . ($isYearly ? 'Yearly' : 'Monthly') . ') | UTR: ' . $utr . ($proofPath ? ' | Proof: ' . $proofPath : ''),
                        'amount'       => $price,
                        'currency'     => 'INR',
                        'notes'        => 'Renewal payment submitted via UPI - Pending admin verification',
                        'status'       => 0, // 0 = Pending Admin Verification
                        'created'      => time(),
                        'changed'      => time(),
                    ];
                    if (\Schema::hasColumn('payment_manual', 'proof') && $proofPath) {
                        $manualData['proof'] = $proofPath;
                    }
                    \Modules\AdminManualPayments\Models\PaymentManual::create($manualData);
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::error("Renewal PaymentManual create error: " . $e->getMessage());
                }
            }

            // Refresh user in auth session
            try {
                \Illuminate\Support\Facades\Auth::setUser($user->fresh());
            } catch (\Throwable $e) {}

            $successMsg = __('Your UPI payment (UTR: :utr) has been submitted for admin verification. Your workspace features remain fully active.', ['utr' => $utr]);
            $redirectUrl = url('/app/dashboard');

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'status'          => 1,
                    'is_pending'      => true,
                    'message'         => $successMsg,
                    'plan_name'       => $targetPlan->name,
                    'redirect'        => $redirectUrl
                ]);
            }

            return redirect()->to('/app/dashboard')->with('success', $successMsg);
        }
    }
}


