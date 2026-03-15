<?php

namespace App\Http\Controllers\User\Report;

use App\Models\Report;
use Illuminate\Http\Response;
use App\Http\Controllers\Controller;
use App\Http\Requests\Report\CreateReportRequest;
use App\Repositories\Report\ReportRepository;

class ReportController extends Controller
{
    protected $report;

    public function __construct(ReportRepository $reportRepo, Report $report)
    {
        $this->report =  $reportRepo;
        $this->report->setModel($report);
    }

    public function create(CreateReportRequest $request)
    {
        try {
            $data = $this->report->create($request->validated());
            $data = api_successWithData('Your report has been submitted successfully.', $data);
            return response()->json($data, Response::HTTP_OK);
        } catch (\Exception $e) {
            $data = api_error($e->getMessage());
            return response()->json($data, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
