<?php
namespace Modules\AdminUsers\Facades;

use Carbon\Carbon;
use Illuminate\Support\Facades\Facade;
use App\Models\User;
use Modules\AdminUsers\Models\Teams;

class UserInfo extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'UserInfo';
    }

    public static function getTeamPermission($key, $default = null, $teamId = null)
    {
        $teamId = $teamId ?? request()->team_id ?? request()->input('team_id');
        $user = auth()->user();

        if (!$teamId && $user) {
            $team = \Modules\AdminUsers\Models\Teams::where('owner', $user->id)->first();
            if (!$team) {
                $member = \Modules\AdminUsers\Models\TeamMembers::where('user_id', $user->id)->first();
                if ($member) {
                    $team = \Modules\AdminUsers\Models\Teams::find($member->team_id);
                }
            }
        } else {
            $team = $teamId ? \Modules\AdminUsers\Models\Teams::find($teamId) : null;
        }

        $val = null;
        if ($team) {
            $val = self::extractPermissionValue($team->permissions, $key, null);
        }

        // If not found in team, or if numeric limit is 0/empty, check user's plan
        $isLimitKey = in_array($key, ['credits', 'whatsapp_message_per_month', 'whatsapp_chatbot_item_limit', 'whatsapp_bulk_max_contact_group', 'whatsapp_bulk_max_phone_numbers']);
        if ($val === null || ($isLimitKey && (empty($val) || $val === '0' || $val === 0))) {
            if ($user && $user->plan) {
                $planVal = self::extractPermissionValue($user->plan->permissions, $key, null);
                if ($planVal !== null && (!empty($planVal) || !$isLimitKey)) {
                    $val = $planVal;
                }
            }
        }

        // Cross-fallback between 'credits' and 'whatsapp_message_per_month'
        if ($isLimitKey && (empty($val) || $val === '0' || $val === 0)) {
            if ($key === 'credits') {
                $altVal = $team ? self::extractPermissionValue($team->permissions, 'whatsapp_message_per_month', null) : null;
                if (empty($altVal) && $user && $user->plan) {
                    $altVal = self::extractPermissionValue($user->plan->permissions, 'whatsapp_message_per_month', null);
                }
                if (!empty($altVal) && (int)$altVal > 0) {
                    $val = $altVal;
                }
            } elseif ($key === 'whatsapp_message_per_month') {
                $altVal = $team ? self::extractPermissionValue($team->permissions, 'credits', null) : null;
                if (empty($altVal) && $user && $user->plan) {
                    $altVal = self::extractPermissionValue($user->plan->permissions, 'credits', null);
                }
                if (!empty($altVal) && (int)$altVal > 0) {
                    $val = $altVal;
                }
            }
        }

        // If user is on an active plan, ensure safe positive limits
        if (($val === null || (is_numeric($val) && (int)$val <= 0)) && $user && (!empty($user->plan_id) || (!empty($user->expiration_date) && ($user->expiration_date == -1 || $user->expiration_date > time())))) {
            if (in_array($key, ['credits', 'whatsapp_message_per_month'])) {
                return 10000;
            }
            if ($key === 'whatsapp_chatbot_item_limit' || $key === 'whatsapp_bulk_max_contact_group') {
                return 5;
            }
            if ($key === 'whatsapp_bulk_max_phone_numbers') {
                return 10000;
            }
            if (str_starts_with($key, 'appwhatsapp') || str_starts_with($key, 'appchannel')) {
                return 1;
            }
        }

        return $val !== null ? $val : $default;
    }

    protected static function extractPermissionValue($permissions, $key, $default = null)
    {
        if (is_string($permissions)) {
            $permissions = json_decode($permissions, true) ?: [];
        }
        if (!is_array($permissions)) {
            return $default;
        }
        if (array_key_exists($key, $permissions)) {
            return $permissions[$key];
        }
        foreach ($permissions as $item) {
            if (is_array($item) && isset($item['key']) && $item['key'] == $key) {
                return $item['value'];
            }
        }
        return $default;
    }

    public static function setDataTeam($key, $value, $teamId = null)
    {
        $teamId = $teamId ?? request()->team_id;
        $team = Teams::find($teamId);
        if (!$team) return false;
        $data = is_array($team->data) ? $team->data : (json_decode($team->data, true) ?: []);
        $data[$key] = $value;
        $team->data = $data;
        $team->save();
        return true;
    }

    public static function getDataTeam($key = null, $default = null, $teamId = null)
    {
        $teamId = $teamId ?? request()->team_id;
        $team = Teams::find($teamId);
        if (!$team) return $default;
        $data = is_array($team->data) ? $team->data : (json_decode($team->data, true) ?: []);
        if ($key === null) return $data;
        return $data[$key] ?? $default;
    }

    public static function setDataUser($key, $value, $userId = null)
    {
        $userId = $userId ?? auth()->id();
        if (!$userId) return false;
        $user = User::find($userId);
        if (!$user) return false;
        $data = is_array($user->data) ? $user->data : (json_decode($user->data, true) ?: []);
        $data[$key] = $value;
        $user->data = $data;
        $user->save();
        return true;
    }

    public static function getDataUser($key = null, $default = null, $userId = null)
    {
        $userId = $userId ?? auth()->id();
        if (!$userId) return false;
        $user = User::find($userId);
        if (!$user) return false;
        $data = is_array($user->data) ? $user->data : (json_decode($user->data, true) ?: []);
        if ($key === null) return $data;
        return $data[$key] ?? $default;
    }

    public static function getJoinedTeams($userId = null)
    {
        $userId = $userId ?? auth()->id();
        if (!$userId) return collect();

        $user = User::find($userId);
        if (!$user) return collect();

        $teams = $user->teams()
            ->wherePivot('status', 1)
            ->orderByDesc('teams.id')
            ->get();

        $teams->load('ownerUser');

        return $teams->map(function($team) {
            return [
                'name'        => $team->name ?? optional($team->ownerUser)->fullname,
                'id'          => $team->id,
                'id_secure'   => $team->id_secure,
                'owner_id'    => $team->owner,
            ];
        });
    }
}
