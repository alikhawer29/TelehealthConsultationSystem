<?php

namespace App\Http\Controllers\User\Contact;

use App\Http\Controllers\Controller;
use App\Http\Requests\Feedback\CreateFeedbackRequest;
use App\Mail\ContactUsMail;
use App\Models\Admin;
use App\Models\Feedback;
use App\Models\SiteInformation;
use App\Models\SupportType;
use App\Repositories\Feedback\FeedbackRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Mail;


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
            $admin = Admin::first();
            $this->feedback->notification()->via(['database'])->send(
                $admin,
                title: 'New Feedback',
                body: "Patient {$feedback->name} has submitted an inquiry.",
                data: [
                    'id' => $feedback->id,
                    'type' => 'feedback',
                ]
            );
            /** send mail to admin **/
            Mail::to($admin->email)->send(new ContactUsMail($feedback));
            $data = api_success('your query has been sent to admin');
            return response()->json($data, Response::HTTP_CREATED);
        } catch (\Exception $e) {
            $data = api_error($e->getMessage());
            return response()->json($data, Response::HTTP_CONFLICT);
        }
    }

    public function siteInformation()
    {
        try {

            $data = SiteInformation::latest()->first();

            $data = api_successWithData('site information', $data);
            return response()->json($data, Response::HTTP_OK);
        } catch (\Exception $e) {
            $data = api_error($e->getMessage());
            return response()->json($data, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
