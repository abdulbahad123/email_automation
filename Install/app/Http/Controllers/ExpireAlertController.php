<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Models\User;

class ExpireAlertController extends Controller
{
    public function index(Request $request)
    {
        // Ensure authenticated admin session in standalone mode
        if (!Auth::check() || (int)Auth::user()->role !== 2) {
            return redirect('/auth/login')->with('error', __('Admin authentication required.'));
        }

        $alertDays = (int)(function_exists('get_option') ? get_option('expire_alert_days', 7) : 7);
        $dailyDays = (int)(function_exists('get_option') ? get_option('expire_alert_daily_days', 3) : 3);
        $alertStatus = (int)(function_exists('get_option') ? get_option('expire_alert_status', 1) : 1);
        $customMessage = function_exists('get_option') ? get_option('expire_alert_message', '') : '';

        $now = time();
        $alertWindow = $now + ($alertDays * 86400);

        // Fetch users
        $allUsers = DB::table('users')
            ->where('role', '!=', 2)
            ->orderBy('id', 'desc')
            ->get();

        $expiredUsers = [];
        $expiringSoonUsers = [];
        $activeUsers = [];

        foreach ($allUsers as $u) {
            $exp = $u->expiration_date;
            if (empty($exp) || $exp == -1) {
                $u->status_label = 'Unlimited / Lifetime';
                $u->status_badge = 'bg-secondary text-white';
                $u->is_expired = false;
                $u->is_expiring = false;
                $activeUsers[] = $u;
                continue;
            }

            $expTs = is_numeric($exp) ? (int)$exp : strtotime($exp);
            $u->exp_formatted = date('M d, Y h:i A', $expTs);

            if ($expTs <= $now) {
                $u->status_label = 'Expired';
                $u->status_badge = 'bg-danger text-white';
                $u->is_expired = true;
                $u->is_expiring = false;
                $expiredUsers[] = $u;
            } elseif ($expTs <= $alertWindow) {
                $diffSec = $expTs - $now;
                if ($diffSec < 3600) {
                    $u->status_label = 'Expires in ' . max(1, (int)ceil($diffSec / 60)) . ' min(s)';
                } elseif ($diffSec < 86400) {
                    $u->status_label = 'Expires in ' . (int)ceil($diffSec / 3600) . ' hour(s)';
                } else {
                    $u->status_label = 'Expires in ' . (int)ceil($diffSec / 86400) . ' day(s)';
                }
                $u->status_badge = 'bg-warning text-dark';
                $u->is_expired = false;
                $u->is_expiring = true;
                $expiringSoonUsers[] = $u;
            } else {
                $u->status_label = 'Active';
                $u->status_badge = 'bg-success text-white';
                $u->is_expired = false;
                $u->is_expiring = false;
                $activeUsers[] = $u;
            }
        }

        return view('expire_alert.index', compact(
            'alertDays',
            'dailyDays',
            'alertStatus',
            'customMessage',
            'expiredUsers',
            'expiringSoonUsers',
            'activeUsers'
        ));
    }

    public function update(Request $request)
    {
        $alertDays = max(1, min(365, (int)$request->input('expire_alert_days', 7)));
        $dailyDays = max(1, min(365, (int)$request->input('expire_alert_daily_days', 3)));
        $status = $request->has('expire_alert_status') ? 1 : 0;
        $message = $request->input('expire_alert_message', '');

        if (function_exists('update_option')) {
            update_option('expire_alert_days', $alertDays);
            update_option('expire_alert_daily_days', $dailyDays);
            update_option('expire_alert_status', $status);
            update_option('expire_alert_message', $message);
        } else {
            DB::table('options')->updateOrInsert(['name' => 'expire_alert_days'], ['value' => $alertDays]);
            DB::table('options')->updateOrInsert(['name' => 'expire_alert_daily_days'], ['value' => $dailyDays]);
            DB::table('options')->updateOrInsert(['name' => 'expire_alert_status'], ['value' => $status]);
            DB::table('options')->updateOrInsert(['name' => 'expire_alert_message'], ['value' => $message]);
        }

        return redirect()->back()->with('success', 'Expire Alert settings updated successfully!');
    }

    public function extendPlan(Request $request)
    {
        $userId = $request->input('user_id');
        $days = (int)$request->input('days', 30);

        $user = DB::table('users')->where('id', $userId)->first();
        if (!$user) {
            return redirect()->back()->with('error', 'User not found');
        }

        $now = time();
        $currExp = is_numeric($user->expiration_date) ? (int)$user->expiration_date : strtotime($user->expiration_date);
        $newExp = ($currExp > $now ? $currExp : $now) + ($days * 86400);

        DB::table('users')->where('id', $userId)->update([
            'expiration_date' => $newExp,
            'status'          => 1,
        ]);

        return redirect()->back()->with('success', "Plan for {$user->fullname} extended by {$days} days! (New Expiry: " . date('M d, Y', $newExp) . ")");
    }

    public function sendWhatsApp(Request $request)
    {
        $userId = $request->input('user_id');
        $user = DB::table('users')->where('id', $userId)->first();
        if (!$user) {
            return redirect()->back()->with('error', 'User not found');
        }

        $phone = $user->phone;
        if (empty($phone)) {
            return redirect()->back()->with('error', 'User does not have a registered phone number.');
        }

        $renewalUrl = url('/app/profile/plan');

        $expTs = is_numeric($user->expiration_date) ? (int)$user->expiration_date : strtotime($user->expiration_date);
        $diffSec = $expTs - time();
        $daysText = $diffSec <= 0 ? 'today (EXPIRED)' : (round($diffSec / 86400) . ' days');
        $expFormatted = date('M d, Y', $expTs);

        $userName = $user->fullname ?: $user->username ?: 'Valued Customer';
        $productName = 'WhatsApp Bulk Automation';

        try {
            if (class_exists('\App\Services\WhatsAppNotificationService')) {
                \App\Services\WhatsAppNotificationService::sendExpiryReminder(
                    $phone,
                    $userName,
                    'WhatsApp Bulk Plan',
                    $daysText,
                    $expFormatted,
                    $renewalUrl,
                    $productName
                );
            }
            return redirect()->back()->with('success', "WhatsApp Expiry Reminder dispatched successfully to {$phone} with renewal link!");
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', 'Failed to send WhatsApp message: ' . $e->getMessage());
        }
    }
}