<?php

namespace App\Http\Controllers\Admin\Bundle;

use App\Filters\Admin\BundleFilters;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Http\Controllers\Controller;
use App\Http\Requests\Bundle\BundleRequest;
use App\Http\Requests\Bundle\UpdateBundleRequest;
use App\Models\Service;
use App\Models\ServiceBundle;
use App\Repositories\Service\ServiceRepository;

class BundleController extends Controller
{
    private ServiceRepository $bundle;

    public function __construct(ServiceRepository $bundleRepo, ServiceBundle $bundle)
    {
        $this->bundle = $bundleRepo;
        $this->bundle->setModel($bundle);
    }

    public function index(Request $request, BundleFilters $filters)
    {
        try {

            $filters->extendRequest([
                'order' => 1,
                'type' => 'lab_bundle'
            ]);

            $data = $this->bundle
                ->paginate(
                    request('per_page', 10),
                    filter: $filters,
                    relations: ['icon']
                );
            $data = api_successWithData('bundle data', $data);
            return response()->json($data, Response::HTTP_OK);
        } catch (\Exception $e) {
            $data = api_error($e->getMessage());
            return response()->json($data, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function create(BundleRequest $request)
    {
        try {
            $data = $this->bundle->createBundle($request->validated());
            $data = api_successWithData('bundle created successfully', $data);
            return response()->json($data, Response::HTTP_CREATED);
        } catch (\Exception $e) {
            $data = api_error($e->getMessage());
            return response()->json($data, Response::HTTP_CONFLICT);
        }
    }

    public function update($id, UpdateBundleRequest $request)
    {
        try {
            $data = $this->bundle->updateBundle($id, $request->validated());
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
            $bundle = $this->bundle->withCount([
                'appointments as total_bookings' => function ($query) {
                    $query->where('bookable_type', 'App\Models\ServiceBundle');
                }
            ])->findById($id, relations: ['file', 'reviews', 'icon']);

            return response()->json(api_successWithData('bundles details', $bundle), Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json(api_error($e->getMessage()), Response::HTTP_CONFLICT);
        }
    }

    public function status($id)
    {
        try {
            $this->bundle->status($id);
            $bundle = $this->bundle->findById($id);
            $data = api_successWithData('status has been updated', $bundle);
            return response()->json($data, Response::HTTP_OK);
        } catch (\Exception $e) {
            $data = api_error($e->getMessage());
            return response()->json($data, Response::HTTP_CONFLICT);
        }
    }

    public function services()
    {
        try {
            $services = Service::where('type', 'lab')
                ->where('status', 1)
                ->select('id', 'name')
                ->get();

            return response()->json(api_successWithData('lab services', $services), Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json(api_error($e->getMessage()), Response::HTTP_CONFLICT);
        }
    }
}
