<?php

namespace Modules\AdminDashboard\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AdminDashboardController extends Controller
{
    public function index()
    {
        return view('admindashboard::index');
    }

    public function statistics(Request $request) 
    {
        try {
            [$startDate, $endDate] = \Core::parseDateRange($request);
            $data = view('admindashboard::statistics', [
                'startDate' => $startDate,
                'endDate' => $endDate,
            ])->render();

            return response()->json([
                "status" => 1,
                "data" => $data
            ]);
        } catch (\Throwable $e) {
            \Log::error("AdminDashboard statistics error: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());
            return response()->json([
                "status" => 1,
                "data" => '<div class="alert alert-light border border-dashed rounded-3 p-4 text-center my-4"><i class="fa-light fa-gauge-high fs-28 text-muted mb-2"></i><div class="fw-6 fs-15 text-dark">' . __('Admin Overview Initialized') . '</div><div class="fs-12 text-muted mt-1">' . __('System metrics are ready.') . '</div></div>'
            ]);
        }
    }
}
