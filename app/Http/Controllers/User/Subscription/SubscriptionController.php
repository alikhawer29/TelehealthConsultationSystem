<?php

namespace App\Http\Controllers\User\Subscription;

use App\Models\User;
use App\Models\Branch;
use App\Models\Package;
use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Filters\User\SubscriptionFilters;
use App\Repositories\Subscription\SubscriptionRepository;

class SubscriptionController extends Controller
{
    private SubscriptionRepository $subscription;

    public function __construct(SubscriptionRepository $subscription)
    {
        $this->subscription = $subscription;
        $this->subscription->setModel(Subscription::make());
    }

    public function index(Request $request, SubscriptionFilters $filters)
    {
        try {
            $filters->extendRequest([
                'parent_id' => 1,
                'order' => 1
            ]);
            $data = $this->subscription
                ->paginate(
                    request('per_page', 10),
                    filter: $filters,
                    relations: [
                        'package',
                    ]
                );
            $data = api_successWithData('subscription logs', $data);
            return response()->json($data, Response::HTTP_OK);
        } catch (\Exception $e) {
            $data = api_error($e->getMessage());
            return response()->json($data, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function show()
    {
        try {
            $user = request()->user();
            $business_id = $user->role === 'employee' ? $user->parent_id : $user->id;

            $currentSubscription = Subscription::where(function ($query) use ($business_id) {
                $query->where('business_id', $business_id);
            })->where('status', 'active')
                ->latest()
                ->first();

            $data = api_successWithData('Current subscriptions', $currentSubscription ?? []);
            return response()->json($data, Response::HTTP_OK);
        } catch (\Exception $e) {
            $data = api_error($e->getMessage());
            return response()->json($data, Response::HTTP_CONFLICT);
        }
    }


    public function downgrade(Request $request): JsonResponse
    {
        try {
            // Get search, page, and per_page parameters
            $search = $request->input('search', '');
            $perPage = $request->input('per_page', 10); // Default to 10 items per page
            $page = $request->input('page', 1); // Default to page 1
            $type = $request->input('type', '');

            $user = request()->user();
            $id = $user->role === 'user' ? $user->id : $user->parent_id;

            if ($type === 'user') {
                // Query with search filter and pagination
                $logs = User::whereIn('parent_id', [$id])
                    ->where('role', 'employee')
                    ->when($search, function ($query, $search) {
                        $query->where('user_name', 'like', "%$search%");
                    })
                    ->select('id', 'user_id', 'user_name', 'phone', 'status')
                    ->paginate($perPage, ['*'], 'page', $page);
            } else {
                // Query with search filter and pagination
                $logs = Branch::whereIn('user_id', [$id])
                    ->when($search, function ($query, $search) {
                        $query->where('name', 'like', "%$search%");
                    })
                    ->with('manager:id,user_name', 'supervisor:id,user_name', 'currency:id,currency')
                    ->select('id', 'name', 'address', 'manager', 'supervisor', 'base_currency', 'status')
                    ->paginate($perPage, ['*'], 'page', $page);
            }

            return response()->json(api_successWithData($type . ' logs', $logs), Response::HTTP_OK);
        } catch (\Exception $e) {
            $data = api_error($e->getMessage());
            return response()->json($data, Response::HTTP_CONFLICT);
        }
    }

    public function downgradeBlock($id, Request $request)
    {
        try {
            $type = $request->input('type', '');

            if ($type === 'user') {
                $user = User::findOrFail($id); // Find the user by ID or throw an error if not found
                $newStatus = $user->status === 1 ? 0 : 1; // Toggle the status
                $user->update(['status' => $newStatus]);

                // Determine the status text based on the new value
                $statusText = $newStatus === 1 ? 'Unblocked' : 'Blocked';
            } else {
                $branch = Branch::findOrFail($id); // Find the branch by ID or throw an error if not found
                $newStatus = $branch->status === 'Unblocked' ? 'Blocked' : 'Unblocked'; // Toggle the status
                $branch->update(['status' => $newStatus]);

                // Set the status text based on the new status
                $statusText = $newStatus === 'Unblocked' ? 'Unblocked' : 'Blocked';
            }

            // Return success response with status text
            $data = api_success($statusText . ' Successfully');
            return response()->json($data, Response::HTTP_OK);
        } catch (\Exception $e) {
            // Return error response in case of exception
            $data = api_error($e->getMessage());
            return response()->json($data, Response::HTTP_CONFLICT);
        }
    }

    public function checkDowngrade1(): JsonResponse
    {
        try {
            $planId = request('plan_id');
            $plan = Package::find($planId);
            $user = request()->user();
            $users = false;
            $branches = false;
            $getUsers = User::where('parent_id', $user->id)->count();
            $getBranches = Branch::where('user_id', $user->id)->count();


            if ($plan) {
                // Get the owner's subscription details
                $currentSubscription = Subscription::where(function ($q) use ($user) {
                    $q->where('user_id', $user->parent_id)
                        ->orWhere('user_id', $user->id);
                })
                    ->where('status', 'active')
                    ->latest()
                    ->first();

                if ($currentSubscription) {
                    $currentPlan = Package::find($currentSubscription->package_id);

                    if ($currentPlan) {
                        $currentPlanUsers =  $currentPlan->no_of_users;
                        $currentPlanBranches =  $currentPlan->branches;
                        $newPlanUsers =  $plan->no_of_users;
                        $newPlanBranches =  $plan->branches;

                        // Check downgrade conditions separately
                        if ($newPlanUsers < $currentPlanUsers) {
                            $users = true;
                        }
                        if ($newPlanBranches < $currentPlanBranches) {
                            $branches = true;
                        }
                    }
                }
            }

            return response()->json(api_successWithData('check downgrade', [
                'users' => $users,
                'branches' => $branches
            ]), Response::HTTP_OK);
        } catch (\Exception $e) {
            $data = api_error($e->getMessage());
            return response()->json($data, Response::HTTP_CONFLICT);
        }
    }

    public function checkDowngrade(): JsonResponse
    {
        try {
            $planId = request('plan_id');
            $plan = Package::find($planId);
            $user = request()->user();
            $users = false;
            $branches = false;
            $id = $user->role === 'user' ? $user->id : $user->parent_id;

            if ($plan) {
                // Count currently active users and branches
                $currentUserCount = User::whereIn('parent_id', [$id])->where('role', 'employee')->where('status', 1)->count();
                $currentBranchCount = Branch::where(function ($query) use ($user) {
                    $query->where('user_id', $user->id)
                        ->orWhere('user_id', $user->parent_id);
                })
                    ->where('status', 'Unblocked')
                    ->count();
                // Check downgrade conditions
                if ($currentUserCount > $plan->no_of_users) {
                    $users = true;
                }
                if ($currentBranchCount > $plan->branches) {
                    $branches = true;
                }
            }

            return response()->json(api_successWithData('check downgrade', [
                'users' => $users,
                'branches' => $branches
            ]), Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json(api_error($e->getMessage()), Response::HTTP_CONFLICT);
        }
    }
}
