<?php

namespace App\Http\Controllers\User\Doctors;

use App\Models\User;
use App\Models\Media;
use App\Models\PartyLedger;
use Illuminate\Http\Response;
use App\Models\WalkinCustomer;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Repositories\User\UserRepository;
use App\Filters\User\DoctorsFilters;
use App\Http\Requests\WalkinCustomer\WalkinCustomerAttachmentRequest;

class DoctorsController extends Controller
{
    private UserRepository $doctors;

    public function __construct(UserRepository $doctorsRepo)
    {
        $this->doctors = $doctorsRepo;
        $this->doctors->setModel(User::make());
    }

    public function index(DoctorsFilters $filter)
    {
        try {

            $filter->extendRequest([
                'sort' => 1,
                'role' => 'doctor',
                'status' => 1,
            ]);

            $doctors = $this->doctors
                ->withCount([
                    'reviews as rating_avg' => fn($q) => $q->select(\DB::raw('AVG(rating)')),
                    'reviews as total_reviews',
                ])
                ->paginate(
                    request('per_page', 10),
                    filter: $filter,
                    relations: ['education', 'license', 'sessionType', 'reviews.reviewer', 'reviews.reviewer.file']
                );

            $data = api_successWithData('doctors listing', $doctors);

            return response()->json($data, Response::HTTP_OK);
        } catch (\Exception $e) {
            $data = api_error($e->getMessage());
            return response()->json($data, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    public function slots($id)
    {
        try {
            $slots = $this->doctors
                ->slots($id, request('date'));
            $data = api_successWithData('slots details', $slots);
            return response()->json($data);
        } catch (\Exception $e) {

            $data = api_error($e->getMessage());
            return response()->json($data);
        }
    }

    public function show($id, DoctorsFilters $filter): JsonResponse
    {
        try {
            $filter->extendRequest([
                'sort' => 1,
                'role' => 'doctor',
                'status' => 1,
            ]);

            $doctors = $this->doctors
                ->withCount([
                    'reviews as rating_avg' => fn($q) => $q->select(\DB::raw('AVG(rating)')),
                    'reviews as total_reviews',
                ])
                ->findById(
                    $id,
                    filter: $filter,
                    relations: ['education', 'license', 'sessionType', 'reviews.reviewer', 'reviews.reviewer.file']
                );

            $data = api_successWithData('doctors detail', $doctors);
            return response()->json($data, Response::HTTP_OK);
        } catch (\Exception $e) {
            $data = api_error($e->getMessage());
            return response()->json($data, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }



    public function status($id): JsonResponse
    {
        try {
            $this->beneficiary->status($id);
            $data = api_success('status has been updated');
            return response()->json($data, Response::HTTP_OK);
        } catch (\Exception $e) {
            $data = api_error($e->getMessage());
            return response()->json($data, Response::HTTP_CONFLICT);
        }
    }


    public function destroy($id)
    {
        try {

            // Check if the warehouse is referenced in other tables
            if ($this->isForeignKeyReferenced($id, 'beneficiary_id')) {
                $data = api_error('The beneficiary cannot be deleted as it is currently in use.');
                return response()->json($data, Response::HTTP_BAD_REQUEST);
            }

            $this->beneficiary->delete($id);
            $data = api_success('beneficiary deleted');
            return response()->json($data, Response::HTTP_OK);
        } catch (\Exception $e) {
            $data = api_error($e->getMessage());
            return response()->json($data, Response::HTTP_BAD_REQUEST);
        }
    }

    public function uploadAttachment(WalkinCustomerAttachmentRequest $request, $id): JsonResponse
    {
        try {

            $params = $request->validated();
            $this->beneficiary->attachments($params, $id);
            $data = api_success('upload successfully.');
            return response()->json($data, Response::HTTP_CREATED);
        } catch (\Exception $e) {
            $data = api_error($e->getMessage());
            return response()->json($data, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getAttachments($id): JsonResponse
    {
        try {

            $walkinCustomer = Media::findOrFail($id)?->only(['id', 'fileable_type', 'fileable_id', 'path', 'file_url']);
            $data = api_successWithData('beneficiary attachment data', $walkinCustomer);
            return response()->json($data, Response::HTTP_OK);
        } catch (\Exception $e) {
            $data = api_error($e->getMessage());
            return response()->json($data, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function removeAttachment($id): JsonResponse
    {
        try {
            $walkinCustomer = Media::findOrFail($id);  // Retrieve the instance or fail
            $walkinCustomer->delete();  // Call delete on the instance
            $data = api_success('attachment deleted successfully.');
            return response()->json($data, Response::HTTP_OK);
        } catch (\Exception $e) {
            $data = api_error($e->getMessage());
            return response()->json($data, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function type(): JsonResponse
    {
        try {
            $type = request('type');
            $branchId = request()->user()->selected_branch;

            $data = $type === 'walkin'
                ? WalkinCustomer::where('branch_id', $branchId)->get(['id', 'customer_name as name'])
                : PartyLedger::where('branch_id', $branchId)->get(['id', 'account_title as name']);

            $msg = $type === 'walkin' ? 'Walk-in Customers' : 'Party Ledgers';

            return response()->json(api_successWithData($msg, $data), Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json(api_error($e->getMessage()), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
