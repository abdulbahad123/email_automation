<?php

namespace Modules\AdminContact\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\ContactEnquiry;
use Carbon\Carbon;

class AdminContactController extends Controller
{
    public function index(Request $request)
    {
        ContactEnquiry::checkTable();

        $query = DB::table('contact_enquiries');

        // Filter: Time Range (all, today, yesterday, custom)
        $filter = $request->input('filter', 'all');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $now = Carbon::now();
        $todayStart = Carbon::today()->startOfDay()->timestamp;
        $todayEnd = Carbon::today()->endOfDay()->timestamp;
        $yesterdayStart = Carbon::yesterday()->startOfDay()->timestamp;
        $yesterdayEnd = Carbon::yesterday()->endOfDay()->timestamp;

        if ($filter === 'today') {
            $query->where(function($q) use ($todayStart, $todayEnd) {
                $q->whereBetween('created', [$todayStart, $todayEnd])
                  ->orWhereBetween('created_at', [Carbon::today()->startOfDay(), Carbon::today()->endOfDay()]);
            });
        } elseif ($filter === 'yesterday') {
            $query->where(function($q) use ($yesterdayStart, $yesterdayEnd) {
                $q->whereBetween('created', [$yesterdayStart, $yesterdayEnd])
                  ->orWhereBetween('created_at', [Carbon::yesterday()->startOfDay(), Carbon::yesterday()->endOfDay()]);
            });
        } elseif ($filter === 'custom' && $startDate) {
            $start = Carbon::parse($startDate)->startOfDay()->timestamp;
            $end = $endDate ? Carbon::parse($endDate)->endOfDay()->timestamp : Carbon::parse($startDate)->endOfDay()->timestamp;
            $query->where(function($q) use ($start, $end, $startDate, $endDate) {
                $q->whereBetween('created', [$start, $end])
                  ->orWhereBetween('created_at', [
                      Carbon::parse($startDate)->startOfDay(),
                      $endDate ? Carbon::parse($endDate)->endOfDay() : Carbon::parse($startDate)->endOfDay()
                  ]);
            });
        }

        // Filter: Status (unread = 0, read = 1)
        if ($request->has('status') && $request->status !== '' && $request->status !== 'all') {
            $query->where('status', (int)$request->status);
        }

        // Search Keyword
        if ($request->filled('keyword')) {
            $kw = '%' . trim($request->keyword) . '%';
            $query->where(function($q) use ($kw) {
                $q->where('name', 'like', $kw)
                  ->orWhere('email', 'like', $kw)
                  ->orWhere('phone', 'like', $kw)
                  ->orWhere('subject', 'like', $kw)
                  ->orWhere('message', 'like', $kw);
            });
        }

        $enquiries = $query->orderBy('id', 'desc')->paginate(15)->appends($request->all());

        // Overview Statistics
        $totalCount = DB::table('contact_enquiries')->count();
        $todayCount = DB::table('contact_enquiries')
            ->where(function($q) use ($todayStart, $todayEnd) {
                $q->whereBetween('created', [$todayStart, $todayEnd])
                  ->orWhereBetween('created_at', [Carbon::today()->startOfDay(), Carbon::today()->endOfDay()]);
            })->count();

        $yesterdayCount = DB::table('contact_enquiries')
            ->where(function($q) use ($yesterdayStart, $yesterdayEnd) {
                $q->whereBetween('created', [$yesterdayStart, $yesterdayEnd])
                  ->orWhereBetween('created_at', [Carbon::yesterday()->startOfDay(), Carbon::yesterday()->endOfDay()]);
            })->count();

        $unreadCount = DB::table('contact_enquiries')->where('status', 0)->count();

        $stats = [
            'total' => $totalCount,
            'today' => $todayCount,
            'yesterday' => $yesterdayCount,
            'unread' => $unreadCount,
        ];

        return view('admincontact::index', compact('enquiries', 'stats', 'filter', 'startDate', 'endDate'));
    }

    public function markStatus(Request $request, $id)
    {
        $status = (int)$request->input('status', 1);
        DB::table('contact_enquiries')
            ->where('id', $id)
            ->orWhere('id_secure', $id)
            ->update([
                'status' => $status,
                'changed' => time(),
                'updated_at' => now(),
            ]);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => $status === 1 ? __('Marked as read') : __('Marked as unread')
            ]);
        }

        return back()->with('success', __('Status updated successfully.'));
    }

    public function destroy(Request $request, $id)
    {
        DB::table('contact_enquiries')
            ->where('id', $id)
            ->orWhere('id_secure', $id)
            ->delete();

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => __('Enquiry deleted successfully.')
            ]);
        }

        return back()->with('success', __('Enquiry deleted successfully.'));
    }

    public function saveSettings(Request $request)
    {
        $posts = $request->except(['_token']);
        $isStandalone = filter_var(env('STANDALONE_MODE', true), FILTER_VALIDATE_BOOLEAN);
        $sisterDbs = $isStandalone ? [] : [
            'nooryak_whatsapp',
            'nooryak_whatsappbulk',
            'nooryak_whatautomation',
            'nooryak_replix_automation',
            'replix_automation',
        ];
        $currentDb = config('database.connections.mysql.database');

        foreach ($posts as $name => $value) {
            if (is_array($value)) {
                $value = json_encode($value, JSON_UNESCAPED_UNICODE);
            }
            if ($value === null) {
                $value = "";
            }
            DB::table('options')->updateOrInsert(
                ['name' => $name],
                ['value' => (string)$value]
            );

            // Sync recaptcha keys to global keys as well
            if ($name === 'contact_recaptcha_site_key') {
                DB::table('options')->updateOrInsert(['name' => 'auth_google_recaptcha_site_key'], ['value' => (string)$value]);
            }
            if ($name === 'contact_recaptcha_secret_key') {
                DB::table('options')->updateOrInsert(['name' => 'auth_google_recaptcha_secret_key'], ['value' => (string)$value]);
            }
            if ($name === 'contact_recaptcha_status') {
                DB::table('options')->updateOrInsert(['name' => 'auth_google_recaptcha_status'], ['value' => (string)$value]);
            }

            foreach ($sisterDbs as $sDb) {
                if ($sDb === $currentDb) continue;
                try {
                    DB::statement("INSERT INTO `{$sDb}`.options (name, value) VALUES (?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value)", [$name, (string)$value]);
                } catch (\Throwable $e) {}
            }
        }

        try {
            app()->forgetInstance('options');
        } catch (\Throwable $e) {}

        try {
            \Illuminate\Support\Facades\Cache::flush();
        } catch (\Throwable $e) {}

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => __('Contact & reCAPTCHA settings saved successfully!')
            ]);
        }

        return back()->with('success', __('Contact & reCAPTCHA settings saved successfully!'));
    }
}
