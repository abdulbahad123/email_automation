<?php

namespace Modules\AppDashboard\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AppDashboardController extends Controller
{
    public function index()
    {
        return view('appdashboard::index');
    }

    public function statistics(Request $request) 
    {
        try {
            [$startDate, $endDate] = \Core::parseDateRange($request);
            $data = view('appdashboard::statistics', [
                'startDate' => $startDate,
                'endDate' => $endDate,
            ])->render();

            return response()->json([
                "status" => 1,
                "data" => $data
            ]);
        } catch (\Throwable $e) {
            \Log::error("AppDashboard statistics error: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());
            return response()->json([
                "status" => 1,
                "data" => '<div class="alert alert-light border border-dashed rounded-3 p-4 text-center my-4"><i class="fa-light fa-chart-line fs-28 text-muted mb-2"></i><div class="fw-6 fs-15 text-dark">' . __('WhatsApp Dashboard Initialized') . '</div><div class="fs-12 text-muted mt-1">' . __('Connect your first WhatsApp account from the Channels menu to start campaigns.') . '</div></div>'
            ]);
        }
    }
}
