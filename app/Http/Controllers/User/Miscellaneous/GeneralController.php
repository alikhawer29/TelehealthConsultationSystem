<?php

namespace App\Http\Controllers\User\Miscellaneous;

use App\Models\City;
use App\Models\User;
use App\Models\State;
use App\Models\Country;
use App\Models\Service;
use App\Models\Specialty;
use Illuminate\Http\Request;
use App\Models\ServiceBundle;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use App\Filters\Player\HomeFilters;
use App\Filters\User\BundleFilters;
use App\Filters\Shelter\CityFilters;
use App\Filters\User\DoctorsFilters;
use App\Filters\User\ServiceFilters;
use App\Http\Controllers\Controller;
use App\Filters\Shelter\StateFilters;
use App\Filters\Shelter\CountryFilters;
use App\Filters\User\SpecialityFilters;
use App\Http\Resources\ServiceResource;
use App\Repositories\City\CityRepository;
use App\Repositories\User\UserRepository;
use App\Http\Requests\Misc\GetCityRequest;
use App\Models\Review;
use App\Repositories\State\StateRepository;
use App\Repositories\Bundle\BundleRepository;
use App\Repositories\Country\CountryRepository;
use App\Repositories\Service\ServiceRepository;
use App\Repositories\Speciality\SpecialityRepository;

class GeneralController extends Controller
{

    private CountryRepository $country;
    private StateRepository $state;
    private CityRepository $city;
    private UserRepository $user;
    private ServiceRepository $service;
    private BundleRepository $bundle;
    private SpecialityRepository $specialty;

    public function __construct(
        CountryRepository $countryRepo,
        StateRepository $stateRepo,
        CityRepository $cityRepo,
        Country $country,
        State $state,
        City $city,
        UserRepository $userRepo,
        User $user,
        ServiceRepository $serviceRepo,
        Service $service,
        BundleRepository $bundleRepo,
        ServiceBundle $bundle,
        SpecialityRepository $specialtyRepo,
        Specialty $specialty

    ) {
        $this->user = $userRepo;
        $this->country = $countryRepo;
        $this->state = $stateRepo;
        $this->city = $cityRepo;
        $this->service = $serviceRepo;
        $this->bundle = $bundleRepo;
        $this->specialty = $specialtyRepo;

        $this->user->setModel($user);
        $this->country->setModel($country);
        $this->state->setModel($state);
        $this->city->setModel($city);
        $this->service->setModel($service);
        $this->bundle->setModel($bundle);
        $this->specialty->setModel($specialty);
    }

    public function doctorsLisitng(DoctorsFilters $filter): JsonResponse
    {
        try {

            $filter->extendRequest([
                'sort' => 1,
                'role' => 'doctor',
                'status' => 1,
            ]);

            $users = $this->user
                ->withCount([
                    'reviews as rating_avg' => fn($q) => $q->select(\DB::raw('AVG(rating)')),
                    'reviews as total_reviews',
                ])
                ->paginate(
                    request('per_page', 10),
                    filter: $filter,
                    relations: ['education', 'license', 'sessionType', 'reviews.reviewer', 'reviews.reviewer.file', 'file']
                );

            $data = api_successWithData('doctors listing', $users);
            return response()->json($data, Response::HTTP_OK);
        } catch (\Exception $e) {
            $data = api_error($e->getMessage());
            return response()->json($data, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function doctorsDetail($id, DoctorsFilters $filter): JsonResponse
    {
        try {

            $filter->extendRequest([
                'sort' => 1,
                'role' => 'doctor',
                'status' => 1,
            ]);

            $users = $this->user
                ->withCount([
                    'reviews as rating_avg' => fn($q) => $q->select(\DB::raw('AVG(rating)')),
                    'reviews as total_reviews',
                    'appointments as total_patients',
                ])
                ->findById(
                    $id,
                    filter: $filter,
                    relations: [
                        'education',
                        'license.file',
                        'sessionType',
                        'file',
                        'reviews.reviewer',
                        'reviews.reviewer.file',
                    ]
                );

            $data = api_successWithData('doctors detail', $users);
            return response()->json($data, Response::HTTP_OK);
        } catch (\Exception $e) {
            $data = api_error($e->getMessage());
            return response()->json($data, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function serviceLisitng(ServiceFilters $filter): JsonResponse
    {
        try {

            $filter->extendRequest([
                'sort' => 1,
                'status' => 1,
            ]);

            $services = $this->service
                ->paginate(
                    request('per_page', 10),
                    filter: $filter,
                    relations: [
                        'file:id,path,fileable_id,fileable_type',
                        'icon',
                        'reviews',
                        'reviews.reviewer.file',
                    ]
                );

            // Convert full pagination with transformed items
            $transformedPaginator = $services->toArray();
            $transformedPaginator['data'] = ServiceResource::collection($services->items());

            // Return with your custom helper
            $data = api_successWithData('services listing', $transformedPaginator);
            return response()->json($data, Response::HTTP_OK);
        } catch (\Exception $e) {
            $data = api_error($e->getMessage());
            return response()->json($data, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function serviceDetail($id, ServiceFilters $filter): JsonResponse
    {
        try {
            $filter->extendRequest([
                'sort' => 1,
                'status' => 1,
            ]);

            $service = $this->service
                ->withCount([
                    'reviews as rating_avg' => fn($q) => $q->select(\DB::raw('COALESCE(AVG(rating), 0)')),
                    'reviews as total_reviews',
                ])
                ->findById(
                    $id,
                    filter: $filter,
                    relations: ['file:id,path,fileable_id,fileable_type', 'slots', 'reviews', 'icon']
                );

            $serviceType = $service->type;

            // 🔁 Get similar services (exclude current one)
            $similarServices = Service::with('file:id,path,fileable_id,fileable_type') // add other relations if needed
                ->withCount([
                    'reviews as rating_avg' => fn($q) => $q->select(\DB::raw('COALESCE(AVG(rating), 0)')),
                    'reviews as total_reviews',
                ])
                ->where('id', '!=', $id)
                ->where('type', $serviceType)
                ->where('status', 1) // Optional: only active
                ->get();

            // ⛏️ Add similar_service to response
            $service['similar_service'] = $similarServices;

            $data = api_successWithData('service detail', $service);
            return response()->json($data, Response::HTTP_OK);
        } catch (\Exception $e) {
            $data = api_error($e->getMessage());
            return response()->json($data, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function bundleLisitng(BundleFilters $filter): JsonResponse
    {
        try {

            $filter->extendRequest([
                'sort' => 1,
                'status' => 1,
                'type' => 'lab_bundle'

            ]);

            $users = $this->bundle
                ->withCount([
                    'reviews as rating_avg' => fn($q) => $q->select(\DB::raw('AVG(rating)')),
                    'reviews as total_reviews',
                ])
                ->paginate(
                    request('per_page', 10),
                    filter: $filter,
                    relations: [
                        'file:id,path,fileable_id,fileable_type',
                        'services.file',
                        'reviews',
                        'reviews.reviewer.file',
                        'icon:id,path,fileable_id,fileable_type',


                    ]
                );
            // Transform the collection to add new keys
            $users->getCollection()->transform(function ($bundle) {
                return $this->enhanceBundleData($bundle);
            });
            $data = api_successWithData('bundle listing', $users);
            return response()->json($data, Response::HTTP_OK);
        } catch (\Exception $e) {
            $data = api_error($e->getMessage());
            return response()->json($data, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    private function enhanceBundleData($bundle)
    {
        // Add new keys to each bundle
        return array_merge($bundle->toArray(), [
            'name' => $bundle->bundle_name,
        ]);
    }

    public function bundleDetail($id, BundleFilters $filter): JsonResponse
    {
        try {


            $filter->extendRequest([
                'sort' => 1,
                'status' => 1,
                'type' => 'lab_bundle'
            ]);

            $service = $this->bundle
                ->withCount([
                    'reviews as rating_avg' => fn($q) => $q->select(\DB::raw('COALESCE(AVG(rating), 0)')),
                    'reviews as total_reviews',
                ])
                ->findById(
                    $id,
                    filter: $filter,
                    relations: [
                        'file:id,path,fileable_id,fileable_type',
                        'services.file:id,path,fileable_id,fileable_type',
                        'reviews',
                        'reviews.reviewer.file:id,path,fileable_id,fileable_type',
                        'icon:id,path,fileable_id,fileable_type',
                    ]
                );

            $serviceType = $service->type;
            // 🔁 Get similar services (exclude current one)
            $similarServices = ServiceBundle::with('file:id,path,fileable_id,fileable_type') // add other relations if needed
                ->withCount([
                    'reviews as rating_avg' => fn($q) => $q->select(\DB::raw('COALESCE(AVG(rating), 0)')),
                    'reviews as total_reviews',
                ])
                ->where('id', '!=', $id)
                ->where('type', $serviceType)
                ->where('status', 1) // Optional: only active
                ->get();

            // ⛏️ Add similar_service to response
            $service['similar_service'] = $similarServices;
            $service['name'] = $service->bundle_name;

            $data = api_successWithData('bundle service detail', $service);
            return response()->json($data, Response::HTTP_OK);
        } catch (\Exception $e) {
            $data = api_error($e->getMessage());
            return response()->json($data, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function specialtyListing(SpecialityFilters $filter): JsonResponse
    {
        try {

            $filter->extendRequest([
                'sort' => 1,
                'status' => 1,
            ]);

            $users = $this->specialty
                ->findAll(
                    filter: $filter,
                    relations: ['file']
                );

            $data = api_successWithData('specialty listing', $users);
            return response()->json($data, Response::HTTP_OK);
        } catch (\Exception $e) {
            $data = api_error($e->getMessage());
            return response()->json($data, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }



    public function aboutUs(): JsonResponse
    {

        try {
            $data = "Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry's standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting, remaining essentially unchanged. It was popularised in the 1960s with the release of Letraset sheets containing Lorem Ipsum passages, and more recently with desktop publishing software like Aldus PageMaker including versions of Lorem Ipsum.";
            $data = api_successWithData('about us data', $data);
            return response()->json($data, Response::HTTP_OK);
        } catch (\Exception $e) {
            $data = api_error($e->getMessage());
            return response()->json($data, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    public function index(): JsonResponse
    {

        try {
            $about_us = content('about_us');
            $privacy_policy = content('privacy_policy');
            $terms_condition = content('terms_condition');
            $data = [
                'privacy_policy' => $privacy_policy,
                'terms_condition' => $terms_condition,
                'about_us' => $about_us,
            ];
            $data = api_successWithData('home data', $data);
            return response()->json($data, Response::HTTP_OK);
        } catch (\Exception $e) {
            $data = api_error($e->getMessage());
            return response()->json($data, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function reminderTypes(): JsonResponse
    {

        try {

            $data = [
                ['value' => "none", 'label' => "None"],
                ['value' => "at_time", 'label' => "At time of event"],
                ['value' => "5_min", 'label' => "5 mins before"],
                ['value' => "10_min", 'label' => "10 mins before"],
                ['value' => "15_min", 'label' => "15 mins before"],
                ['value' => "30_min", 'label' => "30 mins before"],
                ['value' => "1_hour", 'label' => "1 hour before"],
                ['value' => "1_day", 'label' => "1 day before"],
                ['value' => "custom", 'label' => "Custom"],
            ];

            $data = api_successWithData('reminder types', $data);
            return response()->json($data, Response::HTTP_OK);
        } catch (\Exception $e) {
            $data = api_error($e->getMessage());
            return response()->json($data, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    public function countries(Request $request, CountryFilters $filter)
    {
        try {
            $filter->extendRequest(['states' => 1]);
            $countries = $this->country->withCount(['states'])->findAll($filter);
            $data = api_successWithData('countries list', $countries);
            return response()->json($data);
        } catch (\Exception $e) {
            $data = api_error($e->getMessage());
            return response()->json($data);
        }
    }


    public function states(Request $request, StateFilters $filter)
    {
        try {
            // GetStateRequest
            $filter->extendRequest(['cities' => 1, 'order' => 1]);
            $countries = $this->state->withCount(['cities'])->findAll($filter);
            $data = api_successWithData('countries list', $countries);
            return response()->json($data);
        } catch (\Exception $e) {
            $data = api_error($e->getMessage());
            return response()->json($data);
        }
    }

    public function cities(GetCityRequest $request, CityFilters $filter)
    {

        $cities = $this->city->findAll($filter);
        $data = api_successWithData('cities list', $cities);
        return response()->json($data);
        try {
        } catch (\Exception $e) {
            $data = api_error($e->getMessage());
            return response()->json($data, Response::HTTP_CONFLICT);
        }
    }

    public function locations(Request $request, CityFilters $filter)
    {

        try {
            $filter->extendRequest([
                'only' => 10,
            ]);
            $cities = $this->city
                ->withCount([
                    'country as country_name' => fn($q) => $q->select('name'),
                    'state as state_name' => fn($q) => $q->select('name'),
                ])
                ->findAll($filter);
            $data = request()->filled('search') ? $cities : [];
            $data = api_successWithData('cities list', $data);
            return response()->json($data);
        } catch (\Exception $e) {
            $data = api_error($e->getMessage());
            return response()->json($data, Response::HTTP_CONFLICT);
        }
    }

    public function topDoctors()
    {
        try {
            $data = User::where('status', 1)
                ->where('role', 'doctor')
                ->withCount(['appointments', 'ratting'])
                ->havingRaw('(appointments_count > 0 OR ratting_count > 0)')
                ->orderByDesc('appointments_count')
                ->orderByDesc('ratting_count')
                ->take(10)
                ->get();
            return response()->json(api_successWithData('Top doctors list', $data));
        } catch (\Exception $e) {
            return response()->json(api_error($e->getMessage()), Response::HTTP_CONFLICT);
        }
    }


    public function recommendedDoctors()
    {
        try {
            $data = User::where('status', 1)
                ->where('role', 'doctor')
                ->withAvg('ratting', 'rating') // assumes 'rating' column in reviews table
                ->withCount('ratting') // count of ratings
                ->having('ratting_count', '>', 0) // only doctors with ratings
                ->orderByDesc('ratting_avg_rating')
                ->take(10)
                ->get();

            return response()->json(api_successWithData('Recommended doctors list', $data));
        } catch (\Exception $e) {
            return response()->json(api_error($e->getMessage()), Response::HTTP_CONFLICT);
        }
    }

    public function recentDoctors()
    {
        try {
            $data = User::where('status', 1)
                ->where('role', 'doctor')
                ->orderByDesc('created_at')
                ->take(10)
                ->get();

            return response()->json(api_successWithData('Recent doctors list', $data));
        } catch (\Exception $e) {
            return response()->json(api_error($e->getMessage()), Response::HTTP_CONFLICT);
        }
    }

    public function reviews($id)
    {
        try {
            $data = Review::with('reviewer:id,first_name,last_name', 'reviewer.file')
                ->where('reviewable_id', $id)
                ->where('reviewable_type', 'App\Models\User')
                ->select('*', \DB::raw('AVG(rating) OVER() as rating_avg'), \DB::raw('COUNT(*) OVER() as total_reviews'))
                ->get();

            return response()->json(api_successWithData('All Ratings', $data));
        } catch (\Exception $e) {
            return response()->json(api_error($e->getMessage()), Response::HTTP_CONFLICT);
        }
    }
}
