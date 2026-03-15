<?php

namespace App\Http\Controllers\Admin\Speciality;

use App\Filters\Admin\SpecialityFilters;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Http\Controllers\Controller;
use App\Http\Requests\Bundle\BundleRequest;
use App\Http\Requests\Bundle\UpdateBundleRequest;
use App\Http\Requests\Speciality\SpecialityRequest;
use App\Http\Requests\Speciality\UpdateSpecialityRequest;
use App\Models\Specialty;
use App\Repositories\Speciality\SpecialityRepository;

class SpecialityController extends Controller
{
    private SpecialityRepository $speciality;

    public function __construct(SpecialityRepository $specialityRepo, Specialty $speciality)
    {
        $this->speciality = $specialityRepo;
        $this->speciality->setModel($speciality);
    }

    public function index(Request $request, SpecialityFilters $filters)
    {
        try {
            $filters->extendRequest([
                'sort' => 1,
            ]);

            $data = $this->speciality
                ->paginate(
                    request('per_page', 10),
                    filter: $filters,
                );
            $data = api_successWithData('speciality data', $data);
            return response()->json($data, Response::HTTP_OK);
        } catch (\Exception $e) {
            $data = api_error($e->getMessage());
            return response()->json($data, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function create(SpecialityRequest $request)
    {
        try {
            $data = $this->speciality->create($request->validated());
            $data = api_successWithData('bundle created successfully', $data);
            return response()->json($data, Response::HTTP_CREATED);
        } catch (\Exception $e) {
            $data = api_error($e->getMessage());
            return response()->json($data, Response::HTTP_CONFLICT);
        }
    }

    public function update($id, UpdateSpecialityRequest $request)
    {
        try {
            $data = $this->speciality->update($id, $request->validated());
            $data = api_successWithData('slot updated', $data);
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
            $slot = $this->speciality->findById($id, relations: ['file']);

            return response()->json(api_successWithData('Slots details', $slot), Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json(api_error($e->getMessage()), Response::HTTP_CONFLICT);
        }
    }

    public function status($id)
    {
        try {
            $this->speciality->status($id);
            $speciality = $this->speciality->findById($id);
            $data = api_successWithData('status has been updated', $speciality);
            return response()->json($data, Response::HTTP_OK);
        } catch (\Exception $e) {
            $data = api_error($e->getMessage());
            return response()->json($data, Response::HTTP_CONFLICT);
        }
    }
}
