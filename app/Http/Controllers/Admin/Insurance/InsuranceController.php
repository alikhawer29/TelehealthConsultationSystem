<?php

namespace App\Http\Controllers\Admin\Insurance;

use App\Models\Insurance;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Filters\Admin\InsuranceFilters;
use App\Repositories\Insurance\InsuranceRepository;

class InsuranceController extends Controller
{
    private InsuranceRepository $insurance;

    public function __construct(Insurance $insurance)
    {
        $insuranceRepo = new InsuranceRepository();
        $this->insurance = $insuranceRepo;
        $this->insurance->setModel($insurance);
    }

    public function index(InsuranceFilters $filters)
    {
        try {
            $filters->extendRequest([
                'sortBy' => 1,
            ]);
            $data = $this->insurance->paginate(
                request('per_page', 10),
                filter: $filters,
                relations: ['user']
            );
            $data = api_successWithData('insurance data', $data);
            return response()->json($data, Response::HTTP_OK);
        } catch (\Exception $e) {
            $data = api_error($e->getMessage());
            return response()->json($data, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function show($id): JsonResponse
    {
        try {

            $details = $this->insurance->findById(
                $id,
                relations: ['user', 'file']
            );
            $data = api_successWithData('insurance data', $details);
            return response()->json($data, Response::HTTP_OK);
        } catch (\Exception $e) {
            $data = api_error($e->getMessage());
            return response()->json($data, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function status($id, $status): JsonResponse
    {
        try {
            $this->insurance->status($id, $status);
            $data = api_success('status has been updated');
            return response()->json($data, Response::HTTP_OK);
        } catch (\Exception $e) {
            $data = api_error($e->getMessage());
            return response()->json($data, Response::HTTP_CONFLICT);
        }
    }
}
