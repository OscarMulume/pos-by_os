<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ReportService;
use Illuminate\Http\Request;

class ReportController extends Controller
{

    public function index()
    {
        return view('admin.reports.index');
    }

    public function export(Request $request, ReportService $reportService)
    {
        $path = $reportService->exportCsv(
            restaurantId: $request->integer('restaurant_id') ?: null,
            startDate: $request->input('start_date') ?: null,
            endDate: $request->input('end_date') ?: null,
        );

        return response()->download($path)->deleteFileAfterSend(true);
    }
}
