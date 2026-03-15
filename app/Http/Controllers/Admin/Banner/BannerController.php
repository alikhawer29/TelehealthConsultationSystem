<?php

namespace App\Http\Controllers\Admin\Banner;

use App\Filters\Admin\BannerFilters;
use App\Models\Banner;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Http\Controllers\Controller;
use App\Http\Requests\Banner\BannerRequest;
use App\Http\Requests\Banner\UpdateBannerRequest;
use App\Repositories\Banner\BannerRepository;

class BannerController extends Controller
{
    private BannerRepository $banner;

    public function __construct(BannerRepository $bannerRepo, Banner $banner)
    {
        $this->banner = $bannerRepo;
        $this->banner->setModel($banner);
    }

    public function index(Request $request, BannerFilters $filters)
    {
        try {
            $filters->extendRequest([
                'sort' => 1,
            ]);

            $data = $this->banner
                ->paginate(
                    request('per_page', 10),
                    filter: $filters,
                );
            $data = api_successWithData('banner data', $data);
            return response()->json($data, Response::HTTP_OK);
        } catch (\Exception $e) {
            $data = api_error($e->getMessage());
            return response()->json($data, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function create(BannerRequest $request)
    {
        try {
            $data = $this->banner->create($request->validated());
            $data = api_successWithData('banner created successfully', $data);
            return response()->json($data, Response::HTTP_CREATED);
        } catch (\Exception $e) {
            $data = api_error($e->getMessage());
            return response()->json($data, Response::HTTP_CONFLICT);
        }
    }

    public function update($id, UpdateBannerRequest $request)
    {
        try {
            $data = $this->banner->update($id, $request->validated());
            $data = api_successWithData('banner updated', $data);
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
            $slot = $this->banner->findById($id, relations: ['file']);

            return response()->json(api_successWithData('banner details', $slot), Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json(api_error($e->getMessage()), Response::HTTP_CONFLICT);
        }
    }

    public function status($id)
    {
        try {
            $this->banner->status($id);
            $speciality = $this->banner->findById($id);
            $data = api_successWithData('status has been updated', $speciality);
            return response()->json($data, Response::HTTP_OK);
        } catch (\Exception $e) {
            $data = api_error($e->getMessage());
            return response()->json($data, Response::HTTP_CONFLICT);
        }
    }

    public function destroy($id)
    {
        try {

            $this->banner->delete($id);
            $data = api_success('Banner deleted');
            return response()->json($data, Response::HTTP_OK);
        } catch (\Exception $e) {
            $data = api_error($e->getMessage());
            return response()->json($data, Response::HTTP_BAD_REQUEST);
        }
    }

    public function banners(Request $request, BannerFilters $filters)
    {
        try {
            $filters->extendRequest([
                'sort' => 1,
                'status' => 1
            ]);

            $data = $this->banner
                ->findAll(
                    filter: $filters,
                    relations: ['file']
                );
            $data = api_successWithData('banner data', $data);
            return response()->json($data, Response::HTTP_OK);
        } catch (\Exception $e) {
            $data = api_error($e->getMessage());
            return response()->json($data, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
