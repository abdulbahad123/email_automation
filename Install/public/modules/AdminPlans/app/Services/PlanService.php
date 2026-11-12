<?php

namespace Modules\AdminPlans\Services;

use Modules\AdminPlans\Models\Plans;
use Modules\AdminUsers\Models\Teams;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\AdminPaymentSubscriptions\Models\PaymentSubscription;

class PlanService
{
    /** @var array */
    protected array $planItems = [];

    public function getTypes(): array
    {
        return [
            1 => __("Monthly"),
            2 => __("Yearly"),
            3 => __("Lifetime"),
        ];
    }

    public function registerPlanItem($item, int $priority = 100, callable $visible = null): void
    {
        $this->planItems[] = [
            'item'     => $item,
            'priority' => $priority,
            'visible'  => $visible,
        ];
    }

    public function getPlanItems(): array
    {
        $items = $this->planItems;
        usort($items, fn($a, $b) => $a['priority'] <=> $b['priority']);
        return $items;
    }

    public function addPermissions($module_name, $plan_permission)
    {
        $module = \Module::find($module_name);
        $menu = $module->get('menu');
        if ($menu) {
            $plan_permission = array_merge([
                'uri'         => $menu['uri'],
                'icon'        => $menu['icon'],
                'color'       => $menu['color'],
                'name'        => $menu['name'],
                'view'        => "permissions",
                'id'          => $module->getName(),
                'key'         => $module->getLowerName(),
                'module_name' => $menu['name'],
            ], $plan_permission);

            $plan_permissions = app()->bound('plan_permissions') ? app('plan_permissions') : [];
            $plan_permissions[] = $plan_permission;
            usort($plan_permissions, fn($a, $b) => $a['sort'] <=> $b['sort']);
            app()->instance('plan_permissions', $plan_permissions);
        }
    }

    public function syncPlanPermissions(array $currentPermissions): array
    {
        $newPermissions = [];
        foreach (\Module::allEnabled() as $module) {
            $moduleLower = $module->getLowerName();
            $modulePermissions = $module->get('permission');
            $menu = $module->get('menu');
            if (!isset($menu['role']) || $menu['role'] !== 'client') continue;
            if (!empty($menu['parent'])) continue;

            $alreadyExists = collect($currentPermissions)->contains(fn($perm) =>
                is_array($perm) && ($perm['module_name'] ?? null) === ($menu['name'] ?? null)
            );
            if ($alreadyExists) continue;

            $base = [
                'uri'         => $menu['uri'] ?? '',
                'icon'        => $menu['icon'] ?? '',
                'color'       => $menu['color'] ?? '',
                'name'        => $menu['name'] ?? '',
                'id'          => $module->getName(),
                'key'         => $moduleLower,
                'module_name' => $menu['name'] ?? '',
            ];

            if ($modulePermissions === true) {
                $newPermissions[] = $base + ['key' => $moduleLower];
            } elseif (is_array($modulePermissions)) {
                foreach ($modulePermissions as $perm) {
                    if (!empty($perm['key'])) {
                        $newPermissions[] = $base + [
                            'key'   => $perm['key'],
                            'label' => $perm['label'] ?? strtoupper($perm['key']),
                        ];
                    }
                }
            }
        }
        return array_merge($currentPermissions, $newPermissions);
    }

    public static function updatePlanForTeam($planId, $userId, $paymentInfo = [])
    {
        $plan = Plans::find($planId);
        if (!$plan) return;
        $user = User::find($userId);
        if (!$user) return;
        $team = Teams::where("owner", $user->id)->first();
        if (!$team) return;

        $team->permissions = $plan->permissions;
        $team->save();

        $now = now()->timestamp;
        $curExpiration = $user->expiration_date;
        $remainDays = ($curExpiration > $now && $curExpiration != -1) ? ceil(($curExpiration - $now) / 86400) : 0;
        $oldPlan = Plans::find($user->plan_id);

        if ($plan->free_plan || $plan->type == 3) {
            $expiration_date = -1;
        } elseif ($user->plan_id == $planId && $plan->type != 3) {
            $mainDays = $plan->type == 1 ? 30 : 365;
            $expiration_date = ($curExpiration > $now ? $curExpiration : $now) + 86400 * $mainDays;
        } elseif ($oldPlan && $oldPlan->type == 2 && $plan->type == 1) {
            $oldUnit = $oldPlan->price / 365;
            $remainCredit = $remainDays * $oldUnit;
            $monthlyPrice = $plan->price;
            $months = $monthlyPrice > 0 ? floor($remainCredit / $monthlyPrice) : 0;
            $daysLeft = $monthlyPrice > 0 ? floor(($remainCredit % $monthlyPrice) / $oldUnit) : 0;
            $totalDays = $months * 30 + $daysLeft;
            if ($totalDays < $remainDays) $totalDays = $remainDays;
            $expiration_date = $now + 86400 * $totalDays;
        } elseif ($oldPlan && $oldPlan->type == 1 && $plan->type == 2) {
            $expiration_date = $now + 86400 * 365;
        } else {
            $mainDays = $plan->type == 1 ? 30 : 365;
            $expiration_date = ($curExpiration > $now ? $curExpiration : $now) + 86400 * $mainDays;
        }

        $user->plan_id = $planId;
        $user->expiration_date = $expiration_date;
        $user->save();

        self::resetQuota($team->id);

        $message = $paymentInfo
            ? __("Your payment was successful! \n\nThank you for upgrading your plan.\n\nPayment details:\n- Plan: :plan\n- Amount: :amount :currency\n- Transaction ID: :transaction\n\nYour subscription has been upgraded. Enjoy all the new features included in your :plan plan!\n\nIf you need any support, feel free to contact our team anytime.", [
                'plan' => $plan->name,
                'amount' => $paymentInfo['amount'],
                'currency' => $paymentInfo['currency'],
                'transaction' => $paymentInfo['transaction_id'],
            ])
            : __("Payment successful! Your plan has been upgraded.");

        \Notifier::sendAuto($userId, $message);

        // Send WhatsApp congratulations notification
        try {
            $uPhone = !empty($user->phone) ? $user->phone : null;
            $uName = !empty($user->fullname) ? $user->fullname : $user->username;
            $planTitle = $plan->name ?? 'WhatsApp Bulk Plan';
            $formattedExp = $expiration_date == -1 ? 'Unlimited' : date('M d, Y', $expiration_date);
            \App\Services\WhatsAppOtpService::sendPlanRenewedNotification(
                $uPhone,
                $uName,
                $planTitle,
                $formattedExp,
                'WhatsApp Automation Tool'
            );
        } catch (\Throwable $we) {
            \Log::warning("WhatsApp Automation Tool plan renewal notify error: " . $we->getMessage());
        }
    }

    public static function assignPlanOnRegister($userId, $planIdSecure = null)
    {
        $user = User::find($userId);
        if (!$user) return ['status' => 'user_not_found', 'redirect' => route('register')];

        if ($planIdSecure) {
            $plan = Plans::where('id_secure', $planIdSecure)->first();
            if (!$plan) return self::assignDefaultPlan($user);
            if ($plan->free_plan) {
                self::updateUserPlan($user, $plan, 'free');
                return ['status' => 'free', 'redirect' => url_app("dashboard")];
            }
            if ($plan->trial_day) {
                $trialDays = (int) ($plan->trial_days ?? 7);
                self::updateUserPlan($user, $plan, 'trial', $trialDays);
                return [
                    'status' => 'trial',
                    'trial_ends_at' => $user->trial_ends_at,
                    'redirect' => url_app("dashboard")
                ];
            }
            if ($plan->is_paid) {
                return ['status' => 'require_payment', 'redirect' => route('payment.index', $plan->id_secure)];
            }
        }
        return self::assignDefaultPlan($user);
    }

    public static function assignDefaultPlan(User $user)
    {
        $plan = Plans::where('free_plan', 1)->orderByDesc('id')->first();
        if ($plan) {
            self::updateUserPlan($user, $plan, 'free');
            return ['status' => 'free', 'redirect' => url_app("dashboard")];
        }
        return ['status' => 'choose_plan', 'redirect' => url("pricing")];
    }

    public static function updateUserPlan(User $user, Plans $plan, $type = 'free', $trialDays = null)
    {
        DB::transaction(function () use ($user, $plan, $type, $trialDays) {
            $user->plan_id = $plan->id;
            $team = Teams::where("owner", $user->id)->first();
            if ($team) {
                $team->permissions = $plan->permissions;
                $team->save();

                if ($type == 'free') {
                    $user->expiration_date = -1;
                } elseif ($type === 'trial' && $trialDays) {
                    $user->expiration_date = Carbon::now()->addDays($trialDays)->timestamp;
                } elseif ($plan->type == 1) {
                    $user->expiration_date = Carbon::now()->addMonth()->timestamp;
                } elseif ($plan->type == 2) {
                    $user->expiration_date = Carbon::now()->addYear()->timestamp;
                } elseif ($plan->type == 3) {
                    $user->expiration_date = -1;
                } else {
                    $user->expiration_date = null;
                }
                $user->save();
                self::resetQuota($team->id);
            }
        });
    }

    public static function updateUserPlanByID($userId, $planId, $type = 'free', $trialDays = null)
    {
        $user = User::find($userId);
        $plan = Plans::find($planId);

        if (!$user || !$plan) return false;

        return self::updateUserPlan($user, $plan, $type, $trialDays);
    }

    public static function hasSubscription()
    {
        if (PaymentSubscription::userHasActive(auth()->id())) {
            return true;
        }

        return false;
    }

    public static function activateFreePlan($planIdSecure)
    {
        $user = auth()->user();
        $plan = Plans::where('id_secure', $planIdSecure)->where('free_plan', 1)->first();
        
        if (PaymentSubscription::userHasActive($user->id)) {
            return response()->json([
                'status' => 0,
                'message' => __("You already have an active subscription. Please cancel it before registering a new one.")
            ], 404);
        }
        
        if (!$plan) {
            return response()->json([
                'status' => 0,
                'message' => __("Invalid or unavailable free plan.")
            ], 404);
        }

        $user->plan_id = $plan->id;
        $user->expiration_date = -1;
        $user->save();

        $team = Teams::where('owner', $user->id)->first();
        if ($team) {
            $team->permissions = $plan->permissions;
            $team->save();
        }

        return response()->json([
            'status' => 1,
            'message' => __("Your free plan has been activated successfully!"),
            'plan' => [
                'id' => $plan->id,
                'name' => $plan->name,
                'type' => $plan->type,
            ],
        ]);
    }

    public static function resetQuota($teamId)
    {
        $startOfDay = Carbon::now()->startOfDay()->timestamp;
        $nextReset = $startOfDay + 30*24*60*60;
        \UserInfo::setDataTeam("quota_reset_at", $startOfDay, $teamId);
        \UserInfo::setDataTeam("next_quota_reset_at", $nextReset, $teamId);
    }

    public static function doResetQuota($teamId)
    {
        $now = Carbon::now();
        $quotaResetAt = \UserInfo::getDataTeam("quota_reset_at", $teamId);
        $nextQuotaResetAt = \UserInfo::getDataTeam("next_quota_reset_at", $teamId);

        if (
            empty($quotaResetAt) ||
            empty($nextQuotaResetAt) ||
            $now->timestamp > $nextQuotaResetAt
        ) {
            self::resetQuota($teamId);
        }
    }

    public static function syncDefaultBulkPlans(): void
    {
        try {
            if (Plans::count() > 0) {
                return;
            }
            // 1. Sync or create Monthly Plan (WhatsApp Bulk Plan - ₹350)
            $monthly = Plans::where('status', 1)
                ->where(function($q) {
                    $q->where('name', 'LIKE', '%Waziper%')
                      ->orWhere('name', 'LIKE', '%WhatsApp Bulk%')
                      ->orWhere('id', 1);
                })
                ->where('type', 1)
                ->first();

            if ($monthly) {
                $needsSave = false;
                if ($monthly->name !== 'WhatsApp Bulk Plan') {
                    $monthly->name = 'WhatsApp Bulk Plan';
                    $needsSave = true;
                }
                if ((float)$monthly->price !== 350.0 && (float)$monthly->price <= 100.0) {
                    $monthly->price = 350;
                    $needsSave = true;
                }
                if ($monthly->free_plan != 0) {
                    $monthly->free_plan = 0;
                    $needsSave = true;
                }
                if ($monthly->featured != 1) {
                    $monthly->featured = 1;
                    $needsSave = true;
                }
                if ($needsSave) {
                    $monthly->changed = time();
                    $monthly->save();
                }
            } else {
                $hasAnyMonthly = Plans::where('status', 1)->where('type', 1)->first();
                if (!$hasAnyMonthly) {
                    $starterPerms = self::getDefaultStarterPermissions();
                    Plans::create([
                        'id_secure'   => function_exists('rand_string') ? rand_string(10) : md5(uniqid(rand(), true)),
                        'name'        => 'WhatsApp Bulk Plan',
                        'desc'        => 'Essential WhatsApp automation & bulk campaigns suite',
                        'type'        => 1,
                        'price'       => 350,
                        'trial_day'   => 0,
                        'free_plan'   => 0,
                        'featured'    => 1,
                        'position'    => 1,
                        'permissions' => $starterPerms,
                        'status'      => 1,
                        'created'     => time(),
                        'changed'     => time()
                    ]);
                }
            }

            // 2. Sync or create Yearly Plan (WhatsApp Bulk Plan - ₹2,999)
            $yearly = Plans::where('status', 1)
                ->where('type', 2)
                ->where(function($q) {
                    $q->where('name', 'LIKE', '%WhatsApp Bulk%')
                      ->orWhere('name', 'LIKE', '%Starter%')
                      ->orWhere('price', 2999);
                })
                ->first();

            if ($yearly) {
                $needsSave = false;
                if ($yearly->name !== 'WhatsApp Bulk Plan') {
                    $yearly->name = 'WhatsApp Bulk Plan';
                    $needsSave = true;
                }
                if ((float)$yearly->price !== 2999.0 && (float)$yearly->price <= 100.0) {
                    $yearly->price = 2999;
                    $needsSave = true;
                }
                if ($yearly->free_plan != 0) {
                    $yearly->free_plan = 0;
                    $needsSave = true;
                }
                if ($yearly->featured != 1) {
                    $yearly->featured = 1;
                    $needsSave = true;
                }
                if ($needsSave) {
                    $yearly->changed = time();
                    $yearly->save();
                }
            } else {
                $baseMonthly = Plans::where('status', 1)->where('type', 1)->first();
                $permissions = $baseMonthly ? $baseMonthly->permissions : self::getDefaultStarterPermissions();
                Plans::create([
                    'id_secure'   => function_exists('rand_string') ? rand_string(10) : md5(uniqid(rand(), true)),
                    'name'        => 'WhatsApp Bulk Plan',
                    'desc'        => 'Essential WhatsApp automation & bulk campaigns suite (Billed Yearly)',
                    'type'        => 2,
                    'price'       => 2999,
                    'trial_day'   => 0,
                    'free_plan'   => 0,
                    'featured'    => 1,
                    'position'    => 1,
                    'permissions' => $permissions,
                    'status'      => 1,
                    'created'     => time(),
                    'changed'     => time()
                ]);
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error("PlanService::syncDefaultBulkPlans error: " . $e->getMessage());
        }
    }

    public static function getDefaultStarterPermissions(): array
    {
        return [
            ['key' => 'credits', 'label' => 'Credits', 'value' => '10000'],
            ['key' => 'ai_word_credits', 'label' => 'Ai Word Credits', 'value' => '500'],
            ['key' => 'appchannels', 'label' => 'Channels', 'value' => '1'],
            ['key' => 'max_channels', 'label' => 'Max channels', 'value' => '1'],
            ['key' => 'channel_calculate_by', 'label' => 'Channel Calculate By', 'value' => '1'],
            ['key' => 'appchannels.appchannelwhatsappunofficial', 'label' => 'WhatsApp Unofficial', 'value' => '1'],
            ['key' => 'appwhatsappprofileinfo', 'label' => 'Profile Info', 'value' => '1'],
            ['key' => 'appwhatsappreport', 'label' => 'Reports', 'value' => '1'],
            ['key' => 'appwhatsappchat', 'label' => 'Live Chat', 'value' => '1'],
            ['key' => 'appwhatsappbulk', 'label' => 'Bulk campaigns', 'value' => '1'],
            ['key' => 'appwhatsappaismartreply', 'label' => 'AI Smart Reply', 'value' => '0'],
            ['key' => 'appwhatsappautoreply', 'label' => 'Auto Reply', 'value' => '1'],
            ['key' => 'appwhatsappchatbot', 'label' => 'Chatbot', 'value' => '1'],
            ['key' => 'appwhatsappcontact', 'label' => 'Contacts', 'value' => '1'],
            ['key' => 'appwhatsappparticipantsexport', 'label' => 'Export participants', 'value' => '1'],
            ['key' => 'appwhatsappapi', 'label' => 'REST API', 'value' => '0'],
            ['key' => 'whatsapp_chatbot_item_limit', 'label' => 'Chatbot item limit per account', 'value' => '2'],
            ['key' => 'whatsapp_bulk_max_contact_group', 'label' => 'Maximum contact groups', 'value' => '3'],
            ['key' => 'whatsapp_bulk_max_phone_numbers', 'label' => 'Maximum phone numbers per contact group', 'value' => '1000'],
            ['key' => 'whatsapp_message_per_month', 'label' => 'Monthly WhatsApp message limit', 'value' => '10000'],
            ['key' => 'appaicontents', 'label' => 'AI Contents', 'value' => '0'],
            ['key' => 'appsupport', 'label' => 'Support', 'value' => '1'],
            ['key' => 'appfiles', 'label' => 'File Manager', 'value' => '1'],
            ['key' => 'appgroups', 'label' => 'Contact Groups', 'value' => '1'],
            ['key' => 'appcaptions', 'label' => 'Captions', 'value' => '1'],
            ['key' => 'appfiles.max_storage', 'label' => 'Max. storage size (MB)', 'value' => '256'],
            ['key' => 'appfiles.max_size', 'label' => 'Max. file size (MB)', 'value' => '10'],
            ['key' => 'appfiles.image_editor', 'label' => 'Image Editor', 'value' => '1']
        ];
    }

    public static function seedDefaultPlans(): void
    {
        try {
            if (Plans::count() > 0) {
                return;
            }

            $starterPerms = [
                ['key' => 'credits', 'label' => 'Credits', 'value' => '10000'],
                ['key' => 'ai_word_credits', 'label' => 'Ai Word Credits', 'value' => '500'],
                ['key' => 'appchannels', 'label' => 'Channels', 'value' => '1'],
                ['key' => 'max_channels', 'label' => 'Max channels', 'value' => '1'],
                ['key' => 'channel_calculate_by', 'label' => 'Channel Calculate By', 'value' => '1'],
                ['key' => 'appchannels.appchannelwhatsappunofficial', 'label' => 'WhatsApp Unofficial', 'value' => '1'],
                ['key' => 'appwhatsappprofileinfo', 'label' => 'Profile Info', 'value' => '1'],
                ['key' => 'appwhatsappreport', 'label' => 'Reports', 'value' => '1'],
                ['key' => 'appwhatsappchat', 'label' => 'Live Chat', 'value' => '1'],
                ['key' => 'appwhatsappbulk', 'label' => 'Bulk campaigns', 'value' => '1'],
                ['key' => 'appwhatsappaismartreply', 'label' => 'AI Smart Reply', 'value' => '0'],
                ['key' => 'appwhatsappautoreply', 'label' => 'Auto Reply', 'value' => '1'],
                ['key' => 'appwhatsappchatbot', 'label' => 'Chatbot', 'value' => '1'],
                ['key' => 'appwhatsappcontact', 'label' => 'Contacts', 'value' => '1'],
                ['key' => 'appwhatsappparticipantsexport', 'label' => 'Export participants', 'value' => '1'],
                ['key' => 'appwhatsappapi', 'label' => 'REST API', 'value' => '0'],
                ['key' => 'whatsapp_chatbot_item_limit', 'label' => 'Chatbot item limit per account', 'value' => '2'],
                ['key' => 'whatsapp_bulk_max_contact_group', 'label' => 'Maximum contact groups', 'value' => '3'],
                ['key' => 'whatsapp_bulk_max_phone_numbers', 'label' => 'Maximum phone numbers per contact group', 'value' => '1000'],
                ['key' => 'whatsapp_message_per_month', 'label' => 'Monthly WhatsApp message limit', 'value' => '10000'],
                ['key' => 'appaicontents', 'label' => 'AI Contents', 'value' => '0'],
                ['key' => 'appsupport', 'label' => 'Support', 'value' => '1'],
                ['key' => 'appfiles', 'label' => 'File Manager', 'value' => '1'],
                ['key' => 'appgroups', 'label' => 'Contact Groups', 'value' => '1'],
                ['key' => 'appcaptions', 'label' => 'Captions', 'value' => '1'],
                ['key' => 'appfiles.max_storage', 'label' => 'Max. storage size (MB)', 'value' => '256'],
                ['key' => 'appfiles.max_size', 'label' => 'Max. file size (MB)', 'value' => '10'],
                ['key' => 'appfiles.image_editor', 'label' => 'Image Editor', 'value' => '1']
            ];

            $standardPerms = [
                ['key' => 'credits', 'label' => 'Credits', 'value' => '100000'],
                ['key' => 'ai_word_credits', 'label' => 'Ai Word Credits', 'value' => '2000'],
                ['key' => 'appchannels', 'label' => 'Channels', 'value' => '5'],
                ['key' => 'max_channels', 'label' => 'Max channels', 'value' => '5'],
                ['key' => 'channel_calculate_by', 'label' => 'Channel Calculate By', 'value' => '1'],
                ['key' => 'appchannels.appchannelwhatsappunofficial', 'label' => 'WhatsApp Unofficial', 'value' => '1'],
                ['key' => 'appwhatsappprofileinfo', 'label' => 'Profile Info', 'value' => '1'],
                ['key' => 'appwhatsappreport', 'label' => 'Reports', 'value' => '1'],
                ['key' => 'appwhatsappchat', 'label' => 'Live Chat', 'value' => '1'],
                ['key' => 'appwhatsappbulk', 'label' => 'Bulk campaigns', 'value' => '1'],
                ['key' => 'appwhatsappaismartreply', 'label' => 'AI Smart Reply', 'value' => '1'],
                ['key' => 'appwhatsappautoreply', 'label' => 'Auto Reply', 'value' => '1'],
                ['key' => 'appwhatsappchatbot', 'label' => 'Chatbot', 'value' => '1'],
                ['key' => 'appwhatsappcontact', 'label' => 'Contacts', 'value' => '1'],
                ['key' => 'appwhatsappparticipantsexport', 'label' => 'Export participants', 'value' => '1'],
                ['key' => 'appwhatsappapi', 'label' => 'REST API', 'value' => '1'],
                ['key' => 'whatsapp_chatbot_item_limit', 'label' => 'Chatbot item limit per account', 'value' => '10'],
                ['key' => 'whatsapp_bulk_max_contact_group', 'label' => 'Maximum contact groups', 'value' => '15'],
                ['key' => 'whatsapp_bulk_max_phone_numbers', 'label' => 'Maximum phone numbers per contact group', 'value' => '25000'],
                ['key' => 'whatsapp_message_per_month', 'label' => 'Monthly WhatsApp message limit', 'value' => '100000'],
                ['key' => 'appaicontents', 'label' => 'AI Contents', 'value' => '1'],
                ['key' => 'appsupport', 'label' => 'Support', 'value' => '1'],
                ['key' => 'appfiles', 'label' => 'File Manager', 'value' => '1'],
                ['key' => 'appgroups', 'label' => 'Contact Groups', 'value' => '1'],
                ['key' => 'appcaptions', 'label' => 'Captions', 'value' => '1'],
                ['key' => 'appfiles.max_storage', 'label' => 'Max. storage size (MB)', 'value' => '1024'],
                ['key' => 'appfiles.max_size', 'label' => 'Max. file size (MB)', 'value' => '25'],
                ['key' => 'appfiles.image_editor', 'label' => 'Image Editor', 'value' => '1']
            ];

            $premiumPerms = [
                ['key' => 'credits', 'label' => 'Credits', 'value' => '500000'],
                ['key' => 'ai_word_credits', 'label' => 'Ai Word Credits', 'value' => '10000'],
                ['key' => 'appchannels', 'label' => 'Channels', 'value' => '10'],
                ['key' => 'max_channels', 'label' => 'Max channels', 'value' => '-1'],
                ['key' => 'channel_calculate_by', 'label' => 'Channel Calculate By', 'value' => '1'],
                ['key' => 'appchannels.appchannelwhatsappunofficial', 'label' => 'WhatsApp Unofficial', 'value' => '1'],
                ['key' => 'appwhatsappprofileinfo', 'label' => 'Profile Info', 'value' => '1'],
                ['key' => 'appwhatsappreport', 'label' => 'Reports', 'value' => '1'],
                ['key' => 'appwhatsappchat', 'label' => 'Live Chat', 'value' => '1'],
                ['key' => 'appwhatsappbulk', 'label' => 'Bulk campaigns', 'value' => '1'],
                ['key' => 'appwhatsappaismartreply', 'label' => 'AI Smart Reply', 'value' => '1'],
                ['key' => 'appwhatsappautoreply', 'label' => 'Auto Reply', 'value' => '1'],
                ['key' => 'appwhatsappchatbot', 'label' => 'Chatbot', 'value' => '1'],
                ['key' => 'appwhatsappcontact', 'label' => 'Contacts', 'value' => '1'],
                ['key' => 'appwhatsappparticipantsexport', 'label' => 'Export participants', 'value' => '1'],
                ['key' => 'appwhatsappapi', 'label' => 'REST API', 'value' => '1'],
                ['key' => 'whatsapp_chatbot_item_limit', 'label' => 'Chatbot item limit per account', 'value' => '50'],
                ['key' => 'whatsapp_bulk_max_contact_group', 'label' => 'Maximum contact groups', 'value' => '50'],
                ['key' => 'whatsapp_bulk_max_phone_numbers', 'label' => 'Maximum phone numbers per contact group', 'value' => '100000'],
                ['key' => 'whatsapp_message_per_month', 'label' => 'Monthly WhatsApp message limit', 'value' => '500000'],
                ['key' => 'appaicontents', 'label' => 'AI Contents', 'value' => '1'],
                ['key' => 'appsupport', 'label' => 'Support', 'value' => '1'],
                ['key' => 'appfiles', 'label' => 'File Manager', 'value' => '1'],
                ['key' => 'appgroups', 'label' => 'Contact Groups', 'value' => '1'],
                ['key' => 'appcaptions', 'label' => 'Captions', 'value' => '1'],
                ['key' => 'appfiles.max_storage', 'label' => 'Max. storage size (MB)', 'value' => '5120'],
                ['key' => 'appfiles.max_size', 'label' => 'Max. file size (MB)', 'value' => '50'],
                ['key' => 'appfiles.image_editor', 'label' => 'Image Editor', 'value' => '1']
            ];

            $plansToCreate = [
                // Monthly Plans (type = 1)
                [
                    'name'        => 'WhatsApp Bulk Plan',
                    'desc'        => 'Essential WhatsApp automation & bulk campaigns suite',
                    'type'        => 1,
                    'price'       => 350,
                    'trial_day'   => 0,
                    'free_plan'   => 0,
                    'featured'    => 1,
                    'position'    => 1,
                    'permissions' => $starterPerms,
                    'status'      => 1
                ],
                [
                    'name'        => 'Standard',
                    'desc'        => 'Essential bulk messaging & automation for growing businesses',
                    'type'        => 1,
                    'price'       => 39,
                    'trial_day'   => 0,
                    'free_plan'   => 0,
                    'featured'    => 0,
                    'position'    => 2,
                    'permissions' => $standardPerms,
                    'status'      => 1
                ],
                [
                    'name'        => 'Premium',
                    'desc'        => 'Complete WhatsApp marketing suite with AI Smart Reply & maximum limits',
                    'type'        => 1,
                    'price'       => 89,
                    'trial_day'   => 0,
                    'free_plan'   => 0,
                    'featured'    => 1,
                    'position'    => 3,
                    'permissions' => $premiumPerms,
                    'status'      => 1
                ],
                // Yearly Plans (type = 2)
                [
                    'name'        => 'WhatsApp Bulk Plan',
                    'desc'        => 'Essential WhatsApp automation & bulk campaigns suite (Billed Yearly)',
                    'type'        => 2,
                    'price'       => 2999,
                    'trial_day'   => 0,
                    'free_plan'   => 0,
                    'featured'    => 1,
                    'position'    => 1,
                    'permissions' => $starterPerms,
                    'status'      => 1
                ],
                [
                    'name'        => 'Standard',
                    'desc'        => 'Essential bulk messaging & automation for growing businesses (Billed Yearly)',
                    'type'        => 2,
                    'price'       => 39,
                    'trial_day'   => 0,
                    'free_plan'   => 0,
                    'featured'    => 0,
                    'position'    => 2,
                    'permissions' => $standardPerms,
                    'status'      => 1
                ],
                [
                    'name'        => 'Premium',
                    'desc'        => 'Complete WhatsApp marketing suite with AI Smart Reply & maximum limits (Billed Yearly)',
                    'type'        => 2,
                    'price'       => 89,
                    'trial_day'   => 0,
                    'free_plan'   => 0,
                    'featured'    => 1,
                    'position'    => 3,
                    'permissions' => $premiumPerms,
                    'status'      => 1
                ],
            ];

            foreach ($plansToCreate as $pData) {
                $pData['id_secure'] = rand_string(10);
                $pData['created']   = time();
                $pData['changed']   = time();
                Plans::create($pData);
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error("PlanService::seedDefaultPlans failed: " . $e->getMessage());
        }
    }
}
