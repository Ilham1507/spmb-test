<?php

namespace App\Http\Controllers\Shared;

use App\Http\Controllers\Controller;
use App\Services\ExecutiveReportService;
use App\Services\ExecutiveReportWorkbook;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExecutiveReportController extends Controller
{
    public function index(Request $request, ExecutiveReportService $reports)
    {
        $data = $reports->data($request);
        $page = max(1, (int) $request->query('page', 1));
        $data['detailRows'] = new LengthAwarePaginator($data['rows']->forPage($page, 25)->values(), $data['rows']->count(), 25, $page, ['path' => $request->url(), 'query' => $request->query()]);
        return view('shared.executive-report.index', $data);
    }

    public function pdf(Request $request, ExecutiveReportService $reports)
    {
        return Pdf::loadView('shared.executive-report.print', $reports->data($request))->setPaper('a4', 'portrait')->download('laporan-eksekutif-spmb-'.now()->format('Ymd-His').'.pdf');
    }

    public function exportExcel(Request $request, ExecutiveReportService $reports, ExecutiveReportWorkbook $workbooks): StreamedResponse
    {
        $workbook = $workbooks->build($reports->data($request));
        return response()->streamDownload(function () use ($workbook) {
            IOFactory::createWriter($workbook, 'Xlsx')->save('php://output');
            $workbook->disconnectWorksheets();
        }, 'laporan-eksekutif-spmb-'.now()->format('Ymd-His').'.xlsx', ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }
}
