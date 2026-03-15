<?php

namespace App\Http\Controllers\User\Doctors;

use App\Models\User;
use App\Models\SessionType;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use App\Filters\User\DoctorsFilters;
use App\Http\Controllers\Controller;
use App\Http\Resources\DoctorResource;
use App\Repositories\User\UserRepository;

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
                    relations: ['education', 'license', 'sessionType', 'reviews.reviewer', 'reviews.reviewer.file', 'file']
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
            return response()->json($data, Response::HTTP_OK);
        } catch (\Exception $e) {
            $data = api_error($e->getMessage());
            return response()->json($data, Response::HTTP_INTERNAL_SERVER_ERROR);
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
                ->withCount([
                    'appointments as total_patients',
                ])
                ->findById(
                    $id,
                    filter: $filter,
                    relations: [
                        'education',
                        'license.file',
                        'sessionType',
                        'reviews.reviewer',
                        'reviews.reviewer.file',
                        'file'
                    ]
                );
            $data = api_successWithData('doctors detail', new DoctorResource($doctors));
            return response()->json($data, Response::HTTP_OK);
        } catch (\Exception $e) {
            $data = api_error($e->getMessage());
            return response()->json($data, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function services($id)
    {
        try {
            $data = SessionType::where('user_id', $id)->get();
            $transformed = $data->map(function ($item) {
                return [
                    'id'           => $item->id,
                    'session_type' => strtolower($item->session_type) === 'video call'
                        ? 'Video Consultation'
                        : $item->session_type,
                    'price'        => $item->price,
                ];
            });

            $data = api_successWithData('service types by doctor id', $transformed);
            return response()->json($data, Response::HTTP_OK);
        } catch (\Exception $e) {
            $data = api_error($e->getMessage());
            return response()->json($data, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
