<?php

namespace App\Http\Controllers\Admin\Page;

use Illuminate\Http\Response;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Repositories\Page\PageRepository;
use App\Filters\Admin\PageFilters;
use App\Models\Page;

class PageController extends Controller
{
    private PageRepository $page;

    public function __construct(PageRepository $pageRepo)
    {
        $this->page = $pageRepo;
        $this->page->setModel(Page::make());
    }

    public function index(PageFilters $filter)
    {
        try {
            $pages = $this->page->paginate(
                request('per_page', 10),
                filter: $filter
            );

            $data = api_successWithData('Pages listing', $pages);
            return response()->json($data, Response::HTTP_OK);
        } catch (\Exception $e) {
            $data = api_error($e->getMessage());
            return response()->json($data, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function show($id, PageFilters $filter): JsonResponse
    {
        try {
            $page = $this->page->findById($id, filter: $filter, relations: ['file']);
            $data = api_successWithData('Page detail', $page);
            return response()->json($data, Response::HTTP_OK);
        } catch (\Exception $e) {
            $data = api_error($e->getMessage());
            return response()->json($data, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'slug' => 'required|string|max:255|unique:pages,slug',
                'title' => 'required|string|max:255',
                'description' => 'nullable|string',
                'status' => 'sometimes|boolean',
                'file' => 'nullable|image|mimes:jpeg,png,jpg,gif',
            ]);

            $page = $this->page->create($validated);
            $data = api_successWithData('Page created successfully', $page);
            return response()->json($data, Response::HTTP_CREATED);
        } catch (\Exception $e) {
            $data = api_error($e->getMessage());
            return response()->json($data, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'name' => 'sometimes|string|max:255',
                'slug' => 'sometimes|string|max:255|unique:pages,slug,' . $id,
                'title' => 'sometimes|string|max:255',
                'description' => 'nullable|string',
                'status' => 'sometimes|boolean',
                'file' => 'nullable|image|mimes:jpeg,png,jpg,gif',

            ]);

            $page = $this->page->updatePage($id, $validated);
            $data = api_successWithData('Page updated successfully', $page);
            return response()->json($data, Response::HTTP_OK);
        } catch (\Exception $e) {
            $data = api_error($e->getMessage());
            return response()->json($data, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function destroy($id)
    {
        try {
            $page = $this->page->findById($id);
            $page->delete();

            $data = api_success('Page deleted successfully');
            return response()->json($data, Response::HTTP_OK);
        } catch (\Exception $e) {
            $data = api_error($e->getMessage());
            return response()->json($data, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function status($id)
    {
        try {
            $page = $this->page->status($id);
            $data = api_successWithData('Page status updated', $page);
            return response()->json($data, Response::HTTP_OK);
        } catch (\Exception $e) {
            $data = api_error($e->getMessage());
            return response()->json($data, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
