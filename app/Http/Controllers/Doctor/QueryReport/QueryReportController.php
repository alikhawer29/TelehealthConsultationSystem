<?php

namespace App\Http\Controllers\Doctor\QueryReport;

use App\Models\Report;
use App\Models\Quotation;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Schema;
use App\Filters\Doctor\QueryReportFilters;
use App\Models\Appointment;
use App\Repositories\Appointment\AppointmentRepository;
use App\Repositories\QueryReport\QueryReportRepository;


class QueryReportController extends Controller
{
    private QueryReportRepository $report;
    private AppointmentRepository $appointment;


    public function __construct(
        QueryReportRepository $reportRepo,
        Report $report,
        AppointmentRepository $appointmentRepo,
        Appointment $appointment,
    ) {
        $this->report = $reportRepo;
        $this->report->setModel($report);

        $this->appointment = $appointmentRepo;
        $this->appointment->setModel($appointment);
    }

    public function index(Request $request, QueryReportFilters $filters)
    {
        try {

            $filters->extendRequest([
                'sortBy' => 1,
                'reportable_id' => request()->user()->id
            ]);
            $data = $this->report->paginate(
                request('per_page', 10),
                filter: $filters,
                relations: ['reportable', 'user'],
            );
            $data = api_successWithData('data', $data);
            return response()->json($data, Response::HTTP_OK);
        } catch (\Exception $e) {
            $data = api_error($e->getMessage());
            return response()->json($data, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function show($id): JsonResponse
    {
        try {

            $details = $this->report->findById(
                $id,
                relations: ['reportable', 'user'],
            );
            $data = api_successWithData('report details', $details);
            return response()->json($data, Response::HTTP_OK);
        } catch (\Exception $e) {
            $data = api_error($e->getMessage());
            return response()->json($data, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function resolved($id, Request $request)
    {
        try {
            $this->report->update($id, ['admin_note' => $request->admin_comments, 'status' => 'resolved']);
            $data = api_success('Report Resolved Successfully');
            return response()->json($data, Response::HTTP_OK);
        } catch (\Exception $e) {
            $data = api_error($e->getMessage());
            return response()->json($data, Response::HTTP_CONFLICT);
        }
    }
}
