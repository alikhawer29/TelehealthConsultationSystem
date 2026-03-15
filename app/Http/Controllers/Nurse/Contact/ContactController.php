<?php

namespace App\Http\Controllers\User\Contact;

use App\Http\Controllers\Controller;
use App\Http\Requests\Feedback\CreateFeedbackRequest;
use App\Models\Admin;
use App\Models\Feedback;
use App\Models\SupportType;
use App\Repositories\Feedback\FeedbackRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ContactController extends Controller
{
    private FeedbackRepository $feedback;

    public function __construct(FeedbackRepository $repo, Feedback $feedback)
    {

        $this->feedback = $repo;
        $this->feedback->setModel($feedback);
    }

    public function index()
    {
        try {

            $data = SupportType::all();

            $data = api_successWithData('support types', $data);
            return response()->json($data, Response::HTTP_OK);
        } catch (\Exception $e) {
            $data = api_error($e->getMessage());
            return response()->json($data, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    public function create(CreateFeedbackRequest $request): JsonResponse
    {
        try {

            $feedback = $this->feedback->create($request->validated());
            /** send notification to admin **/
            $this->feedback->notification()->via(['database'])->send(
                Admin::first(),
                title: 'New Feedback',
                body: "{$feedback->name} has submitted a Feedback",
                data: [
                    'route' => [
                        'name' => 'admin.feedbacks.show',
                        'params' => [
                            'id' => $feedback->id,
                        ],
                    ],
                ]
            );
            $data = api_success('your query has been sent to admin');
            return response()->json($data, Response::HTTP_CREATED);
        } catch (\Exception $e) {
            $data = api_error($e->getMessage());
            return response()->json($data, Response::HTTP_CONFLICT);
        }
    }
}
