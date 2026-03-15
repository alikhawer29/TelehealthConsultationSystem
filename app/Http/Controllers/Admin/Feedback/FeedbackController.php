<?php

namespace App\Http\Controllers\Admin\Feedback;

use App\Models\Feedback;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Filters\Admin\FeedbackFilters;
use App\Repositories\Feedback\FeedbackRepository;

class FeedbackController extends Controller
{
    private FeedbackRepository $feedback;

    public function __construct(Feedback $feedback)
    {
        $feedbackRepo = new FeedbackRepository();
        $this->feedback = $feedbackRepo;
        $this->feedback->setModel($feedback);
    }

    public function index(Request $request, FeedbackFilters $filters)
    {
        try {
            $filters->extendRequest([
                'sortBy' => 1,
            ]);
            $data = $this->feedback->paginate(
                request('per_page', 10),
                filter: $filters,
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

            $details = $this->feedback->findById(
                $id,

            );
            $data = api_successWithData('feedback data', $details);
            return response()->json($data, Response::HTTP_OK);
        } catch (\Exception $e) {
            $data = api_error($e->getMessage());
            return response()->json($data, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
