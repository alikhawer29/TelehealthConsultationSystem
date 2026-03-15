<?php

namespace App\Http\Controllers\Buyer\Category;

use App\Models\Category;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Category\CategoryRequest;
use App\Repositories\Category\CategoryRepository;

class CategoryController extends Controller
{
    private CategoryRepository $category;

    public function __construct(CategoryRepository $categoryRepo)
    {
        $this->category = $categoryRepo;
        $this->category->setModel(Category::make());
    }

    public function index()
    {
        try {
            $data = $this->category->get();
            $data = api_successWithData('category data', $data);
            return response()->json($data, Response::HTTP_OK);
        } catch (\Exception $e) {
            $data = api_error($e->getMessage());
            return response()->json($data, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function create(CategoryRequest $request): JsonResponse
    {
        try {
            $params = $request->validated();
            $this->category->create($params);
            $data = api_success('added successfully.');
            return response()->json($data, Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return response()->json($e->getMessage());
        }
    }

    public function update(CategoryRequest $request, $id): JsonResponse
    {
        try {
            $this->category->update($id, $request->validated());
            $data = api_success('Successfully updated.');
            return response()->json($data, Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return response()->json($e->getMessage());
        }
    }



    public function status($id): JsonResponse
    {
        try {
            $this->category->status($id);
            $data = api_success('status has been updated');
            return response()->json($data, Response::HTTP_OK);
        } catch (\Exception $e) {
            $data = api_error($e->getMessage());
            return response()->json($data, Response::HTTP_CONFLICT);
        }
    }
}
