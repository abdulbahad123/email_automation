<?php

namespace Modules\AdminUsers\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use Modules\AdminUsers\Classes\AdminUsersExport;
use Modules\AdminUsers\Models\Teams;
use Modules\AdminUsers\Models\TeamMembers;
use Modules\AdminPlans\Models\Plans;
use App\Models\User;

class AdminUsersController extends Controller
{
    public $Datatable;

    public function __construct()
    {
        $this->Datatable = [
            "element" => "DataTable",
            "order" => ['created', 'desc'],
            "lengthMenu" => [10, 25, 50, 100, 150, 200],
            "search_field" => ["fullname", "username", "email", "phone", "plans.name"],
            "columns" => [
                ['data' => 'id_secure', 'name' => 'id_secure', 'className' => 'w-40'],
                ['data' => 'fullname', 'name' => 'fullname', 'title' => __('User Info')],
                ['data' => 'username', 'name' => 'username', 'title' => __('Username')],
                ['data' => 'phone', 'name' => 'phone', 'title' => __('Mobile / WhatsApp')],
                ['data' => 'plan_name', 'name' => 'plans.name', 'alias' => 'plan_name', 'title' => __('Plan')],
                ['data' => 'payment_details', 'name' => 'users.payment_method', 'alias' => 'payment_details', 'title' => __('Payment Method / Status')],
                ['data' => 'role', 'name' => 'role', 'title' => __('Role')],
                ['data' => 'timezone', 'name' => 'timezone', 'title' => __('Timezone')],
                ['data' => 'last_login', 'name' => 'last_login', "type" => "time_elapsed", 'title' => __('Last Login')],
                ['data' => 'changed', 'name' => 'changed', 'type' => 'datetime', 'title' => __('Update at')],
                ['data' => 'created', 'name' => 'created', 'type' => 'datetime', 'title' => __('Created at')],
                ['data' => 'status', 'name' => 'status', 'title' => __('Status'), 'className' => 'w-80'],
                ['title' => __('Action'), 'className' => 'text-center'],
            ],
            'status_filter' => [
                ['value' => '-1', 'label' => __('All')],
                ['value' => '2', 'name' => 'active', 'icon' => 'fa-light fa-user-check', 'color' => 'success', 'label' => __('Active')],
                ['value' => '1', 'name' => 'inactive', 'icon' => 'fa-light fa-user-xmark', 'color' => 'warning', 'label' => __('Inactive')],
                ['value' => '0', 'name' => 'banned', 'icon' => 'fa-light fa-user-lock', 'color' => 'danger', 'label' => __('Banned')],
            ],
            'actions' => [
                [
                    'url' => module_url("status/active"),
                    'icon' => 'fa-light fa-user-check',
                    'label' => __('Active'),
                    'call_success' => "Main.DataTable_Reload('#DataTable')"
                ],
                [
                    'url' => module_url("status/inactive"),
                    'icon' => 'fa-light fa-user-xmark',
                    'label' => __('Inactive'),
                    'call_success' => "Main.DataTable_Reload('#DataTable')"
                ],
                [
                    'url' => module_url("status/banned"),
                    'icon' => 'fa-light fa-user-lock',
                    'label' => __('Banned'),
                    'call_success' => "Main.DataTable_Reload('#DataTable')"
                ],
                ['divider' => true],
                [
                    'url' => module_url("destroy"),
                    'icon' => 'fa-light fa-trash-can-list',
                    'label' => __('Delete'),
                    'confirm' => __("Are you sure you want to delete this item?"),
                    'call_success' => "Main.DataTable_Reload('#DataTable')"
                ],
            ],
        ];
    }

    public function index()
    {
        $total_user = User::count();
        $total_active_user = User::where("status", 2)->count();
        $total_inactive_user = User::where("status", 1)->count();
        $total_banned_user = User::where("status", 0)->count();

        return view(module('key').'::index', [
            'total_user' => $total_user,
            'total_active_user' => $total_active_user,
            'total_inactive_user' => $total_inactive_user,
            'total_banned_user' => $total_banned_user,
            'Datatable' => $this->Datatable,
        ]);
    }

    public function list(Request $request)
    {
        $whereConditions = [];
        $joins = [
            [
                "table" => "plans",
                "first" => "plans.id",
                "second" => "users.plan_id",
                "type" => "left"
            ]
        ];

        $dataTableService = \DataTable::make(User::class, $this->Datatable, $whereConditions, $joins);
        $data = $dataTableService->getData($request);

        // Enrich rows with payment details (method, status, reference ID, proof image/doc)
        if (!empty($data['data'])) {
            $idSecures = array_filter(array_column($data['data'], 'id_secure'));
            if (!empty($idSecures)) {
                $usersWithPayments = User::whereIn('id_secure', $idSecures)->get()->keyBy('id_secure');
                $userIds = $usersWithPayments->pluck('id')->filter()->toArray();

                $manualPayments = [];
                if (!empty($userIds) && class_exists('\Modules\AdminManualPayments\Models\PaymentManual')) {
                    try {
                        $manualPayments = \Modules\AdminManualPayments\Models\PaymentManual::whereIn('uid', $userIds)
                            ->orderBy('id', 'desc')
                            ->get()
                            ->groupBy('uid');
                    } catch (\Throwable $e) {}
                }

                foreach ($data['data'] as &$row) {
                    $u = $usersWithPayments[$row['id_secure']] ?? null;
                    $userManual = ($u && isset($manualPayments[$u->id])) ? $manualPayments[$u->id]->first() : null;

                    // Payment Method: 'upi', 'razorpay', or 'free'
                    $method = 'free';
                    if ($u && !empty($u->payment_method)) {
                        $method = strtolower($u->payment_method);
                    } elseif ($userManual) {
                        $manualNotes = strtolower((string)$userManual->notes . ' ' . (string)$userManual->payment_info);
                        if (str_contains($manualNotes, 'razorpay') || str_starts_with((string)$userManual->payment_id, 'pay_')) {
                            $method = 'razorpay';
                        } else {
                            $method = 'upi';
                        }
                    } elseif (!empty($row['plan_name']) && !str_contains(strtolower($row['plan_name']), 'free')) {
                        $method = 'upi';
                    }

                    // Payment Status: 'pending', 'approved', 'rejected'
                    $status = 'approved';
                    if ($u && !empty($u->payment_status)) {
                        $status = strtolower($u->payment_status);
                    } elseif ($userManual) {
                        if ($userManual->status == 1) {
                            $status = 'approved';
                        } elseif ($userManual->status == 2) {
                            $status = 'rejected';
                        } else {
                            $status = 'pending';
                        }
                    }

                    // Reference Number / UTR
                    $ref = '';
                    if ($u && !empty($u->payment_reference)) {
                        $ref = $u->payment_reference;
                    } elseif ($userManual) {
                        $ref = $userManual->payment_id;
                    }

                    // Payment Proof Image / Document
                    $proof = '';
                    if ($u && !empty($u->payment_proof)) {
                        $proof = asset($u->payment_proof);
                    } elseif ($userManual && !empty($userManual->proof)) {
                        $proof = asset($userManual->proof);
                    }

                    // Auto-heal "No Plan" issue for Razorpay & Paid users
                    if (empty($row['plan_name']) && $u) {
                        $healPlan = null;
                        if ($u->plan_id && class_exists('\Modules\AdminPlans\Models\Plans')) {
                            $healPlan = \Modules\AdminPlans\Models\Plans::find($u->plan_id);
                        }
                        if (!$healPlan && $userManual && $userManual->plan_id && class_exists('\Modules\AdminPlans\Models\Plans')) {
                            $healPlan = \Modules\AdminPlans\Models\Plans::find($userManual->plan_id);
                        }
                        if (!$healPlan && in_array($method, ['razorpay', 'upi']) && class_exists('\Modules\AdminPlans\Models\Plans')) {
                            $healPlan = \Modules\AdminPlans\Models\Plans::where('free_plan', 0)->first()
                                     ?? \Modules\AdminPlans\Models\Plans::first();
                        }
                        if ($healPlan) {
                            $row['plan_name'] = $healPlan->name;
                            if (!$u->plan_id || $u->plan_id == 0) {
                                try {
                                    if (class_exists('\Plan') && method_exists('\Plan', 'updatePlanForTeam')) {
                                        \Plan::updatePlanForTeam($healPlan->id, $u->id);
                                    }
                                } catch (\Throwable $e) {}
                            }
                        }
                    }

                    $row['payment_details'] = $method;
                    $row['payment_method'] = $method;
                    $row['payment_status'] = $status;
                    $row['payment_reference'] = $ref;
                    $row['payment_proof'] = $proof;
                    $row['manual_id_secure'] = $userManual ? $userManual->id_secure : '';
                }
            }
        }

        return response()->json($data);
    }

    public function approveUpiPayment(Request $request, $id_secure)
    {
        $user = User::where('id_secure', $id_secure)->first();
        if (!$user) {
            return response()->json(['status' => 0, 'message' => __('User not found!')]);
        }

        $planIdToActivate = null;

        // Find pending manual payment and approve it
        if (class_exists('\Modules\AdminManualPayments\Models\PaymentManual')) {
            try {
                $payment = \Modules\AdminManualPayments\Models\PaymentManual::where('uid', $user->id)
                    ->where('status', '!=', 1)
                    ->orderBy('id', 'desc')
                    ->first();

                if ($payment) {
                    $payment->status = 1; // Approved
                    $payment->changed = time();
                    $payment->save();
                    $planIdToActivate = $payment->plan_id;
                }
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error("Approve UPI PaymentManual error: " . $e->getMessage());
            }
        }

        // Determine plan to activate (payment plan -> user plan -> first paid plan)
        if (!$planIdToActivate && $user->plan_id) {
            $planIdToActivate = $user->plan_id;
        }
        if (!$planIdToActivate && class_exists('\Modules\AdminPlans\Models\Plans')) {
            $defaultPlan = \Modules\AdminPlans\Models\Plans::where('free_plan', 0)->first() 
                        ?? \Modules\AdminPlans\Models\Plans::first();
            if ($defaultPlan) {
                $planIdToActivate = $defaultPlan->id;
            }
        }

        if ($planIdToActivate && class_exists('\Plan') && method_exists('\Plan', 'updatePlanForTeam')) {
            try {
                \Plan::updatePlanForTeam($planIdToActivate, $user->id);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error("Approve UPI updatePlanForTeam error: " . $e->getMessage());
            }
        }

        try {
            $user->status = 2; // Active
            if (\Schema::hasColumn('users', 'payment_status')) {
                $user->payment_status = 'approved';
            }
            $user->save();
        } catch (\Throwable $e) {}

        return response()->json([
            'status' => 1,
            'message' => __('UPI payment for :name has been approved! Plan is now ACTIVE.', ['name' => $user->fullname])
        ]);
    }

    public function rejectUpiPayment(Request $request, $id_secure)
    {
        $user = User::where('id_secure', $id_secure)->first();
        if (!$user) {
            return response()->json(['status' => 0, 'message' => __('User not found!')]);
        }

        // Find manual payment and reject it
        if (class_exists('\Modules\AdminManualPayments\Models\PaymentManual')) {
            try {
                $payment = \Modules\AdminManualPayments\Models\PaymentManual::where('uid', $user->id)
                    ->where('status', '!=', 1)
                    ->orderBy('id', 'desc')
                    ->first();

                if ($payment) {
                    $payment->status = 2; // 2 = Rejected
                    $payment->changed = time();
                    $payment->save();
                }
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error("Reject UPI PaymentManual error: " . $e->getMessage());
            }
        }

        try {
            if (\Schema::hasColumn('users', 'payment_status')) {
                $user->payment_status = 'rejected';
                $user->save();
            }
        } catch (\Throwable $e) {}

        return response()->json([
            'status' => 1,
            'message' => __('UPI payment for :name has been rejected.', ['name' => $user->fullname])
        ]);
    }

    public function get_search_users(Request $request)
    {
        $search = $request->input('q', '');
        $page = (int)$request->input('page', 1);
        $limit = 30;
        $offset = ($page - 1) * $limit;

        $query = User::query();

        if (!empty($search)) {
            $query->where(function ($query) use ($search) {
                $query->where('username', 'LIKE', "%{$search}%")
                      ->orWhere('fullname', 'LIKE', "%{$search}%")
                      ->orWhere('email', 'LIKE', "%{$search}%");
            });
        }

        $total_count = $query->count();

        $users = $query->offset($offset)
                       ->limit($limit)
                       ->get();

        $results = [];

        foreach ($users as $user) {
            $results[] = [
                'id' => $user->id_secure,
                'text' => $user->fullname . ' (' . $user->email . ')',
            ];
        }

        return response()->json([
            'items' => $results,
            'total_count' => $total_count,
        ]);
    }

    public function export()
    {
        return Excel::download(new AdminUsersExport, 'users.xlsx');
    }

    public function create()
    {
        $plans = Plans::orderBy("type", "asc")->get();

        return view(module('key').'::create', [
            "plans" => $plans
        ]);
    }

    public function edit(Request $request, $id_secure = "")
    {
        $user = User::where('id_secure', $id_secure)->firstOrFail();
        $plans = Plans::orderBy("type", "asc")->get();

        return view(module('key').'::edit', [
            "result" => $user,
            "plans" => $plans
        ]);
    }

    public function save(Request $request)
    {
        $rawAv = $request->files->get('avatar');
        if ($rawAv && method_exists($rawAv, 'isValid') && !$rawAv->isValid() && $rawAv->getError() !== UPLOAD_ERR_NO_FILE) {
            return ms(['status' => 0, 'message' => 'errors', 'error_type' => 2, 'errors' => ['avatar' => [$rawAv->getErrorMessage()]]]);
        }
        $validator = Validator::make($request->all(), [
            'fullname'              => 'required|min:3',
            'username'              => 'required|min:5|regex:/^\S+$/|unique:users,username',
            'email'                 => 'required|email|unique:users,email',
            'country_code'          => 'nullable|string|max:10',
            'phone'                 => 'nullable|string|max:30',
            'timezone'              => 'required',
            'password'              => 'required|min:6|confirmed',
            'password_confirmation' => 'required|min:6',
            'plan'                  => 'required|numeric|min:1',
            'avatar'                => function_exists('finfo_open') ? 'nullable|image' : ['nullable', 'mimes:jpg,jpeg,png,gif,webp,bmp,svg'],
            'role'                  => 'required|in:1,2',
            'status'                => 'required|in:0,1,2',
            'expiration_date'       => [
                'required',
                function ($attribute, $value, $fail) {
                    $value = trim($value);
                    if ($value == -1 || $value === '-1') {
                        return;
                    }
                    $ts = timestamp_sql($value);
                    if ($ts !== null && $ts !== false && $ts > 0) {
                        return;
                    }
                    $fail("The expiration date must be either a valid date or set to -1 to indicate that it is unlimited.");
                }
            ],
        ], [
            'fullname.required' => __('Full name is required.'),
            'fullname.min' => __('Full name must be at least :min characters.'),
            'username.required' => __('Username is required.'),
            'username.min' => __('Username must be at least :min characters.'),
            'username.regex' => __('Username must not contain spaces.'),
            'username.unique' => __('This username is already taken.'),
            'email.required' => __('Email is required.'),
            'email.email' => __('Please provide a valid email address.'),
            'email.unique' => __('This email is already taken.'),
            'timezone.required' => __('Timezone is required.'),
            'password.required' => __('Password is required.'),
            'password.min' => __('Password must be at least :min characters.'),
            'password.confirmed' => __('Password confirmation does not match.'),
            'password_confirmation.required' => __('Password confirmation is required.'),
            'password_confirmation.min' => __('Password confirmation must be at least :min characters.'),
            'plan.required' => __('Plan is required.'),
            'plan.numeric' => __('Plan must be a number.'),
            'plan.min' => __('Plan is not valid.'),
            'avatar.image' => __('Avatar must be an image.'),
            'avatar.mimes' => __('Avatar must be an image.'),
            'role.required' => __('Role is required.'),
            'role.in' => __('Role is not valid.'),
            'status.required' => __('Status is required.'),
            'status.in' => __('Status is not valid.'),
            'expiration_date.required' => __('Expiration date is required.'),
        ]);

        if ($validator->fails()) {
            return ms([
                "status"     => 0,
                "message"    => "errors",
                "error_type" => 2,
                "errors"     => $validator->errors()
            ]);
        }

        $avatar = '';
        if ($request->hasFile('avatar')) {
            $avatar = \UploadFile::storeSingleFile($request->file('avatar'), 'avatars', true);
        }

        \App\Services\WhatsAppOtpService::ensureUserColumnsExist();
        $phoneInput = $request->input('phone');
        $countryCodeInput = $request->input('country_code', '+91');
        $cleanPhone = $phoneInput ? \App\Services\WhatsAppOtpService::formatNumber($phoneInput, $countryCodeInput) : null;

        $userData = [
            'id_secure'  => rand_string(),
            'role'       => $request->input('role'),
            'login_type' => 'direct',
            'fullname'   => $request->input('fullname'),
            'username'   => $request->input('username'),
            'email'      => $request->input('email'),
            'password'   => bcrypt($request->input('password')),
            'last_login' => time(),
            'timezone'   => $request->input('timezone'),
            'avatar'     => $avatar,
            'secret_key' => rand_string(15),
            'status'     => $request->input('status'),
            'changed'    => time(),
            'created'    => time(),
        ];

        if (\Schema::hasColumn('users', 'country_code')) {
            $userData['country_code'] = $countryCodeInput;
        }
        if (\Schema::hasColumn('users', 'phone')) {
            $userData['phone'] = $cleanPhone ?: $phoneInput;
        }
        if (\Schema::hasColumn('users', 'phone_verified_at')) {
            $userData['phone_verified_at'] = date('Y-m-d H:i:s');
        }
        if (\Schema::hasColumn('users', 'email_verified_at')) {
            $userData['email_verified_at'] = date('Y-m-d H:i:s');
        }

        $user = User::create($userData);

        $plan = Plans::findOrFail($request->input('plan'));

        $expirationInput = $request->input('expiration_date');
        if ($expirationInput == -1 || $expirationInput === '-1') {
            $expirationTimestamp = -1;
        } else {
            $expirationTimestamp = timestamp_sql($expirationInput);
        }

        \Plan::updateUserPlan($user, $plan, 'custom', null);

        if ($user->expiration_date != $expirationTimestamp) {
            $user->expiration_date = $expirationTimestamp;
            $user->save();
        }

        return ms([
            "status"  => 1,
            "message" => "Succeeded",
            "data"    => $user
        ]);
    }

    public function update_info(Request $request)
    {
        $user = User::where('id_secure', $request->id_secure)->firstOrFail();

        $validator = Validator::make($request->all(), [
            'fullname' => 'required|min:3',
            'username' => 'required|min:3|unique:users,username,' . $user->id,
            'email'    => 'required|email|unique:users,email,' . $user->id,
            'country_code' => 'nullable|string|max:10',
            'phone'        => 'nullable|string|max:30',
            'timezone' => 'required',
            'avatar'   => function_exists('finfo_open') ? 'nullable|image' : ['nullable', 'mimes:jpg,jpeg,png,gif,webp,bmp,svg'],
        ], [
            'fullname.required' => __('Full name is required.'),
            'fullname.min'      => __('Full name must be at least :min characters.'),
            'username.required' => __('Username is required.'),
            'username.min'      => __('Username must be at least :min characters.'),
            'username.unique'   => __('This username is already taken.'),
            'email.required'    => __('Email is required.'),
            'email.email'       => __('Please provide a valid email address.'),
            'email.unique'      => __('This email is already taken.'),
            'timezone.required' => __('Timezone is required.'),
            'avatar.image'      => __('Avatar must be an image.'),
        ]);

        if ($validator->fails()) {
            return ms([
                "status" => 0,
                "message" => "errors",
                "error_type" => 2,
                "errors" => $validator->errors()
            ]);
        }

        \App\Services\WhatsAppOtpService::ensureUserColumnsExist();
        $phoneInput = $request->input('phone');
        $countryCodeInput = $request->input('country_code', '+91');
        $cleanPhone = $phoneInput ? \App\Services\WhatsAppOtpService::formatNumber($phoneInput, $countryCodeInput) : null;

        $values = [
            'fullname' => $request->input('fullname'),
            'username' => $request->input('username'),
            'email'    => $request->input('email'),
            'timezone' => $request->input('timezone'),
            'status'   => $request->input('status'),
            'role'     => $request->input('role'),
            'changed'  => time()
        ];

        if (\Schema::hasColumn('users', 'country_code')) {
            $values['country_code'] = $countryCodeInput;
        }
        if (\Schema::hasColumn('users', 'phone')) {
            $values['phone'] = $cleanPhone ?: $phoneInput;
        }
        if (\Schema::hasColumn('users', 'phone_verified_at') && !empty($cleanPhone) && empty($user->phone_verified_at)) {
            $values['phone_verified_at'] = date('Y-m-d H:i:s');
        }
        if (\Schema::hasColumn('users', 'email_verified_at') && empty($user->email_verified_at)) {
            $values['email_verified_at'] = date('Y-m-d H:i:s');
        }

        if ($request->hasFile('avatar')) {
            \UploadFile::deleteFileFromServer($user->avatar);
            $avatar = \UploadFile::storeSingleFile($request->file('avatar'), 'avatars', true);
            $values['avatar'] = $avatar;
        }

        $user->update($values);

        return ms([
            "status" => 1,
            "message" => "Succeeded"
        ]);
    }

    public function change_password(Request $request)
    {
        $user = User::where('id_secure', $request->id_secure)->first();

        $validator = Validator::make($request->all(), [
            'password'              => 'required|min:6|confirmed',
            'password_confirmation' => 'required|min:6',
        ]);

        if ($validator->passes()) {
            $values = [
                'password' => bcrypt($request->input('password')),
                'changed'  => time()
            ];
            User::where("id", $user->id)->update($values);

            return ms(["status" => 1, "message" => "Succeeded"]);
        }

        return ms([
            "status"     => 0,
            "message"    => "errors",
            "error_type" => 2,
            "errors"     => $validator->errors()
        ]);
    }

    public function update_plan(Request $request)
    {
        $user = User::where('id_secure', $request->id_secure)->firstOrFail();

        $validator = Validator::make($request->all(), [
            'plan' => ['required', 'numeric', 'min:1'],
            'expiration_date' => [
                'required',
                function ($attribute, $value, $fail) {
                    $value = trim($value);

                    if ($value == -1 || $value === '-1') {
                        return;
                    }

                    $ts = timestamp_sql($value);

                    if ($ts !== null && $ts !== false && $ts > 0) {
                        return;
                    }

                    $fail(__('The expiration date must be a valid datetime or -1 for unlimited.'));
                }
            ],
        ], [
            'plan.required'            => __('Plan is required.'),
            'plan.numeric'             => __('Plan must be a number.'),
            'plan.min'                 => __('Plan is not valid.'),
            'expiration_date.required' => __('Expiration date is required.'),
        ]);

        if ($validator->fails()) {
            return ms([
                "status"     => 0,
                "message"    => "errors",
                "error_type" => 2,
                "errors"     => $validator->errors()
            ]);
        }

        $expirationInput = $request->input('expiration_date');
        if ($expirationInput == -1 || $expirationInput === '-1') {
            $expirationTimestamp = -1;
        } else {
            $expirationTimestamp = timestamp_sql($expirationInput);
        }

        $plan = Plans::findOrFail($request->input('plan'));

        \Plan::updateUserPlan($user, $plan, 'custom', null);

        $user->expiration_date = $expirationTimestamp;
        $user->save();

        return ms([
            "status"  => 1,
            "message" => __('Plan updated successfully.'),
        ]);
    }

    public function status(Request $request, $status = "active")
    {
        $ids = $request->input('id');
        $id_arr = [];

        if (empty($ids)) {
            return ms([
                "status" => 0,
                "message" => __("Please select at least one item"),
            ]);
        }

        if (is_string($ids)) {
            $ids = [$ids];
        }

        foreach ($ids as $value) {
            if ($value != 0) {
                $id_arr[] = $value;
            }
        }

        switch ($status) {
            case 'active':
                $status = 2;
                break;
            case 'inactive':
                $status = 1;
                break;
            default:
                $status = 0;
                break;
        }

        $targetUsers = User::whereIn('id_secure', $id_arr)->get();
        User::whereIn('id_secure', $id_arr)->update(['status' => $status]);

        foreach ($targetUsers as $tUser) {
            if ($status === 0) { // Banned / Suspended
                if (!empty($tUser->phone)) {
                    $msg = "⚠️ *Account Suspended Notice*\n\nHello *{$tUser->fullname}*,\n\nYour *Whatsappbulk* account (*{$tUser->username}*) has been *suspended* by administration.\n\nYour campaign workspace and dashboard are currently locked. To reactivate your account, please contact administration support.\n\n– Team Whatsappbulk";
                    try {
                        \App\Services\WhatsAppOtpService::sendMessage($tUser->phone, $msg);
                    } catch (\Throwable $e) {}
                }
                if (!empty($tUser->email)) {
                    try {
                        \Illuminate\Support\Facades\Mail::raw("Hello {$tUser->fullname},\n\nYour Whatsappbulk account ({$tUser->username}) has been suspended by administration.\n\nYour campaign workspace and dashboard are currently locked. Please contact support to reactivate your account.\n\nThank you,\nWhatsappbulk Administration", function ($m) use ($tUser) {
                            $m->to($tUser->email, $tUser->fullname)->subject("Account Suspended - Whatsappbulk");
                        });
                    } catch (\Throwable $e) {}
                }
            } elseif ($status === 2) { // Reactivated
                if (!empty($tUser->phone)) {
                    $msg = "✅ *Account Reactivated*\n\nHello *{$tUser->fullname}*,\n\nYour *Whatsappbulk* account (*{$tUser->username}*) has been *reactivated* by administration. Your campaign workspace is active again!\n\n🔗 *Login:*\n" . url('auth/login') . "\n\n– Team Whatsappbulk";
                    try {
                        \App\Services\WhatsAppOtpService::sendMessage($tUser->phone, $msg);
                    } catch (\Throwable $e) {}
                }
                if (!empty($tUser->email)) {
                    try {
                        \Illuminate\Support\Facades\Mail::raw("Hello {$tUser->fullname},\n\nYour Whatsappbulk account has been reactivated. You can now login to your workspace.\n\nLogin URL: " . url('auth/login') . "\n\nThank you,\nWhatsappbulk Administration", function ($m) use ($tUser) {
                            $m->to($tUser->email, $tUser->fullname)->subject("Account Reactivated - Whatsappbulk");
                        });
                    } catch (\Throwable $e) {}
                }
            }
        }

        ms(["status" => 1, "message" => "Succeeded"]);
    }

    public function destroy(Request $request)
    {
        $ids = id_arr($request->input('id'));

        if (empty($ids)) {
            return [
                "status" => 0,
                "message" => __("Please select at least one item"),
            ];
        }

        if (is_string($ids)) {
            $ids = [$ids];
        }

        $users = User::whereIn('id_secure', $ids)->get();

        foreach ($users as $user) {
            $team = Teams::where('owner', $user->id)->first();

            if ($team) {
                $teamId = $team->id;

                $tables = DB::select('SHOW TABLES');
                $tableKey = 'Tables_in_' . DB::getDatabaseName();

                foreach ($tables as $table) {
                    $tableName = $table->$tableKey;

                    if (Schema::hasColumn($tableName, 'team_id')) {
                        DB::table($tableName)->where('team_id', $teamId)->delete();
                    }
                }

                $team->delete();
            }

            \UploadFile::deleteFileFromServer($user->avatar);
            $user->delete();
            $team_id = Teams::where("owner", $user->id)->delete();
            TeamMembers::where("team_id", $team_id)->delete();
        }

        return response()->json([
            'status' => 1,
            'message' => __('Deleted successfully.')
        ]);
    }
}