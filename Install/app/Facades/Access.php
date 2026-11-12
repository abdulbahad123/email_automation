<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Gate;
use Modules\AdminUsers\Models\Teams;
use Auth;
use User;

class Access extends Facade
{
    protected static function getFacadeAccessor()
    { 
        return 'access';
    }

    protected static function deny($message = null)
    {
        if(module('role') != "admin"){
            abort(403, $message ?? __('You do not have permission to access this feature.'));
        }
    }

    protected static function check($permission, $withExpire = true, $redirectToDashboard = false, $message = null)
    {
        if ($withExpire && self::isExpired()) {
            $msg = __('Your account or plan has expired. Please upgrade to continue.');

            if (request()->expectsJson()) {
                response()->json([
                    'status' => 0,
                    'message' => $msg,
                    'code' => 403
                ], 403)->send();
                exit;
            } elseif ($redirectToDashboard) {
                session()->flash('error', $msg);
                session()->save();
                redirect()->route('app.dashboard')->send();
                exit;
            } else {
                self::deny($msg);
            }
        }

        if (!self::canAccess($permission)) {
            $msg = $message ?? __('You do not have permission to access this feature.');

            if (request()->expectsJson()) {
                response()->json([
                    'status' => 0,
                    'message' => $msg,
                    'code' => 403
                ], 403)->send();
                exit;
            } elseif ($redirectToDashboard) {
                session()->flash('error', $msg);
                session()->save();
                redirect()->route('app.dashboard')->send();
                exit;
            } else {
                self::deny($msg);
            }
        }
    }

    protected static function canAccess($permission, $withExpire = true)
    {
        if ($withExpire && self::isExpired()) {
            return false;
        }

        return \Gate::allows($permission);
    }

    protected static function getPermissions()
    {
        if (!app()->bound('permissions')) {
            return $default;
        }

        return app('permissions');
    }

    protected static function permission($key, $default = null)
    {
        $permissions = app()->bound('permissions') ? app('permissions') : [];

        $value = null;
        if (is_array($permissions) && array_key_exists($key, $permissions)) {
            $value = $permissions[$key];
        } else {
            $value = data_get($permissions, $key, null);
        }

        $isLimitKey = in_array($key, ['credits', 'whatsapp_message_per_month', 'whatsapp_chatbot_item_limit', 'whatsapp_bulk_max_contact_group', 'whatsapp_bulk_max_phone_numbers']);
        $user = auth()->user();

        // If limit or permission is missing or 0, check user's plan
        if ($value === null || ($isLimitKey && (empty($value) || $value === '0' || $value === 0))) {
            if ($user && $user->plan && !empty($user->plan->permissions)) {
                $planPerms = $user->plan->permissions;
                if (is_string($planPerms)) {
                    $planPerms = json_decode($planPerms, true);
                }
                if (is_array($planPerms) && isset($planPerms[0]['key']) && isset($planPerms[0]['value'])) {
                    $planPerms = collect($planPerms)->pluck('value', 'key')->toArray();
                }
                if (is_array($planPerms) && isset($planPerms[$key])) {
                    $value = $planPerms[$key];
                }
            }
        }

        // Cross-fallback between 'credits' and 'whatsapp_message_per_month'
        if ($isLimitKey && (empty($value) || $value === '0' || $value === 0)) {
            if ($key === 'credits' && isset($permissions['whatsapp_message_per_month']) && !empty($permissions['whatsapp_message_per_month'])) {
                $value = $permissions['whatsapp_message_per_month'];
            } elseif ($key === 'whatsapp_message_per_month' && isset($permissions['credits']) && !empty($permissions['credits'])) {
                $value = $permissions['credits'];
            }
        }

        // Active plan defaults
        if (($value === null || (is_numeric($value) && (int)$value <= 0)) && $user && (!empty($user->plan_id) || (!empty($user->expiration_date) && ($user->expiration_date == -1 || $user->expiration_date > time())))) {
            if (in_array($key, ['credits', 'whatsapp_message_per_month'])) {
                $value = 10000;
            } elseif ($key === 'whatsapp_chatbot_item_limit' || $key === 'whatsapp_bulk_max_contact_group') {
                $value = 5;
            } elseif ($key === 'whatsapp_bulk_max_phone_numbers') {
                $value = 10000;
            } elseif (str_starts_with($key, 'appwhatsapp') || str_starts_with($key, 'appchannel')) {
                $value = 1;
            }
        }

        if ($value === null) {
            $value = $default;
        }

        if (is_string($value) && is_numeric($value)) {
            return strpos($value, '.') !== false ? (float) $value : (int) $value;
        }

        return $value;
    }

    protected static function isExpired($user = null)
    {
        $user = $user ?: auth()->user();
        if (!$user) return true;
        return $user->expiration_date > 0 && $user->expiration_date < time();
    }
}


