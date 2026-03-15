<?php

namespace App\Http\Controllers\User\Package;

use App\Models\Plans;
use App\Models\Package;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Filters\User\PackageFilters;
use App\Http\Requests\Payment\CreateCustomPaymentRequest;
use App\Http\Requests\Payment\CreatePaymentRequest;
use App\Http\Requests\Plans\CreatePlanRequest;
use App\Http\Requests\Plans\UpdatePlanRequest;
use App\Repositories\Packages\PackagesRepository;

class PackageController extends Controller
{
    private PackagesRepository $package;

    public function __construct(PackagesRepository $package)
    {
        $this->package = $package;
        $this->package->setModel(Package::make());
    }

    public function index(Request $request, PackageFilters $filters)
    {
        try {
            $data = $this->package
                ->findAll(
                    filter: $filters,
                );
            $data = api_successWithData('packages data', $data);
            return response()->json($data, Response::HTTP_OK);
        } catch (\Exception $e) {
            $data = api_error($e->getMessage());
            return response()->json($data, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function create($id, CreatePaymentRequest $request)
    {
        try {
            $this->package->payment($id, $request->validated());
            $data = api_success('package purchase successfully');
            return response()->json($data, Response::HTTP_OK);
        } catch (\Throwable $th) {
            throw $th;
            $data = api_error('something went wrong');
            return response()->json($data, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function custom(CreateCustomPaymentRequest $request)
    {
        try {
            $this->package->custom($request->validated());
            $data = api_success('package requested successfully');
            return response()->json($data, Response::HTTP_OK);
        } catch (\Throwable $th) {
            throw $th;
            $data = api_error('something went wrong');
            return response()->json($data, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    public function show($id)
    {
        try {
            $data = $this->package->findById($id);
            $data = api_successWithData('package details', $data);
            return response()->json($data, Response::HTTP_OK);
        } catch (\Exception $e) {
            $data = api_error($e->getMessage());
            return response()->json($data, Response::HTTP_CONFLICT);
        }
    }
}
