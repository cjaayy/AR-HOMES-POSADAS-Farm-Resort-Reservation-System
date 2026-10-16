<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use App\Models\Review;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ReviewController extends Controller
{
    public function index(Request $request)
    {
        $userId = session('user_id');
        if (!$userId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        try {
            $reviews = Review::with(['reservation'])
                ->where('user_id', $userId)
                ->where('status', '!=', 'deleted')
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'reviews' => $reviews
            ]);
        } catch (Exception $e) {
            Log::error('Get User Reviews Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function publicReviews(Request $request)
    {
        try {
            $reviews = Review::with('user:user_id,full_name,given_name,last_name,loyalty_level')
                ->where('status', 'active')
                ->orderBy('created_at', 'desc')
                ->take(50)
                ->get()
                ->map(function ($rev) {
                    return [
                        'review_id' => $rev->review_id,
                        'rating' => $rev->rating,
                        'title' => $rev->title,
                        'content' => $rev->content,
                        'helpful_count' => $rev->helpful_count,
                        'created_at' => $rev->created_at ? $rev->created_at->format('M d, Y') : null,
                        'author_name' => $rev->user ? $rev->user->full_name : 'Anonymous Guest',
                        'loyalty_level' => $rev->user ? $rev->user->loyalty_level : 'Regular'
                    ];
                });

            $averageRating = Review::where('status', 'active')->avg('rating') ?: 5.0;

            return response()->json([
                'success' => true,
                'average_rating' => round($averageRating, 1),
                'total_reviews' => count($reviews),
                'reviews' => $reviews
            ]);
        } catch (Exception $e) {
            Log::error('Get Public Reviews Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function store(Request $request)
    {
        $userId = session('user_id');
        if (!$userId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $reservationId = trim($request->input('reservation_id', ''));
        $rating = (int)$request->input('rating', 0);
        $title = trim($request->input('title', ''));
        $content = trim($request->input('content', ''));

        if (empty($reservationId) || empty($title) || empty($content)) {
            return response()->json(['success' => false, 'message' => 'All fields are required'], 400);
        }

        if ($rating < 1 || $rating > 5) {
            return response()->json(['success' => false, 'message' => 'Rating must be between 1 and 5'], 400);
        }

        try {
            $reservation = Reservation::where('reservation_id', $reservationId)
                ->where('user_id', $userId)
                ->first();

            if (!$reservation) {
                return response()->json(['success' => false, 'message' => 'Reservation not found'], 404);
            }

            if (!in_array($reservation->status, ['completed', 'checked_out', 'confirmed'])) {
                return response()->json(['success' => false, 'message' => 'You can only review confirmed or completed stays'], 400);
            }

            if (Review::where('reservation_id', $reservationId)->exists()) {
                return response()->json(['success' => false, 'message' => 'You have already reviewed this reservation'], 400);
            }

            $review = Review::create([
                'user_id' => $userId,
                'reservation_id' => $reservationId,
                'rating' => $rating,
                'title' => $title,
                'content' => $content,
                'status' => 'active'
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Review submitted successfully!',
                'review_id' => $review->review_id
            ]);
        } catch (Exception $e) {
            Log::error('Submit Review Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request)
    {
        $userId = session('user_id');
        if (!$userId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $reviewId = $request->input('review_id');
        $rating = (int)$request->input('rating', 0);
        $title = trim($request->input('title', ''));
        $content = trim($request->input('content', ''));

        try {
            $review = Review::where('review_id', $reviewId)
                ->where('user_id', $userId)
                ->first();

            if (!$review) {
                return response()->json(['success' => false, 'message' => 'Review not found'], 404);
            }

            $review->update([
                'rating' => $rating ?: $review->rating,
                'title' => $title ?: $review->title,
                'content' => $content ?: $review->content,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Review updated successfully'
            ]);
        } catch (Exception $e) {
            Log::error('Update Review Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function destroy(Request $request)
    {
        $userId = session('user_id');
        if (!$userId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $reviewId = $request->input('review_id');

        try {
            $review = Review::where('review_id', $reviewId)
                ->where('user_id', $userId)
                ->first();

            if (!$review) {
                return response()->json(['success' => false, 'message' => 'Review not found'], 404);
            }

            $review->update(['status' => 'deleted']);

            return response()->json([
                'success' => true,
                'message' => 'Review deleted successfully'
            ]);
        } catch (Exception $e) {
            Log::error('Delete Review Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
