<?php

namespace App\Http\Controllers\Admin\SiteInformation;

use App\Filters\Admin\SiteInformationFilters;
use App\Models\SiteInformation;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\SiteInformation\SiteInformationRequest;
use App\Repositories\SiteInformation\SiteInformationRepository;

class SiteInformationController extends Controller
{
    private SiteInformationRepository $siteInformation;

    public function __construct(SiteInformationRepository $siteInformationRepo)
    {
        $this->siteInformation = $siteInformationRepo;
        $this->siteInformation->setModel(SiteInformation::make());
    }

    public function index(SiteInformationFilters $filter)
    {
        try {
            $filter->extendRequest([
                'sortBy' => 1,
            ]);
            $data = $this->siteInformation->paginate(
                request('per_page', 10),
                filter: $filter,
            );
            $data = api_successWithData('site information data', $data);
            return response()->json($data, Response::HTTP_OK);
        } catch (\Exception $e) {
            $data = api_error($e->getMessage());
            return response()->json($data, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function create(SiteInformationRequest $request): JsonResponse
    {
        try {
            $params = $request->validated();
            $this->siteInformation->create($params);
            $data = api_success('added successfully.');
            return response()->json($data, Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return response()->json($e->getMessage());
        }
    }

    public function update($id, SiteInformationRequest $request): JsonResponse
    {
        try {
            $params = $request->validated();
            $this->siteInformation->update($id, $params);
            $data = api_success('added successfully.');
            return response()->json($data, Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return response()->json($e->getMessage());
        }
    }
    public function show($id): JsonResponse
    {
        try {

            $data = $this->siteInformation->findById($id);
            $data = api_successWithData('details', $data);
            return response()->json($data, Response::HTTP_OK);
        } catch (\Exception $e) {
            $data = api_error($e->getMessage());
            return response()->json($data, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function destroy($id)
    {
        try {

            $this->siteInformation->delete($id);
            $data = api_success('Content deleted');
            return response()->json($data, Response::HTTP_OK);
        } catch (\Exception $e) {
            $data = api_error($e->getMessage());
            return response()->json($data, Response::HTTP_BAD_REQUEST);
        }
    }
}
