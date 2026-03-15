<?php

namespace App\Http\Controllers\User\Review;

use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Http\Controllers\Controller;
use App\Filters\Player\ReviewFilters;
use App\Repositories\Review\ReviewRepository;
use App\Http\Requests\Review\CreateReviewRequest;

class ReviewController extends Controller
{
    protected $review;

    public function __construct(ReviewRepository $repo, Review $review)
    {
        $this->review =  $repo;
        $this->review->setModel($review);
    }

    public function create(CreateReviewRequest $request)
    {
        try {
            $this->review->create(array_merge($request->validated(), ['user_id' => auth('user')->id()]));
            return response()->json(['status' => true, 'message' => 'Your review has been posted successfully']);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], Response::HTTP_CONFLICT);
        }
    }
}
