<?php

namespace App\Http\Controllers\Api\Official;

use App\Http\Controllers\Controller;
use App\Models\CommunityPost;
use App\Models\MarketProduct;
use App\Models\ResidentProfile;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardSummaryController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $user = $this->authenticatedUserOrNull();
        if ($user === null) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $barangay = trim((string) $user->barangay);
        if ($barangay === '') {
            $barangay = trim((string) $request->query('barangay', ''));
        }
        if ($barangay === '') {
            return response()->json([
                'message' => 'Set your barangay in your profile before opening dashboard summary.',
            ], 422);
        }

        $residentUsers = User::query()
            ->where('role', 'resident')
            ->where('barangay', $barangay)
            ->count();

        $populationFromHousehold = ResidentProfile::query()
            ->whereHas('user', static function ($query) use ($barangay): void {
                $query
                    ->where('role', 'resident')
                    ->where('barangay', $barangay);
            })
            ->sum('household_size');

        $population = max((int) $residentUsers, (int) $populationFromHousehold);

        $totalRequests = ServiceRequest::query()
            ->inBarangay($barangay)
            ->count();

        $pendingRequests = ServiceRequest::query()
            ->inBarangay($barangay)
            ->whereRaw('LOWER(status) = ?', ['pending'])
            ->count();

        $products = MarketProduct::query()
            ->inBarangay($barangay);

        $totalProducts = (clone $products)->count();
        $verifiedProducts = (clone $products)->where('verified', true)->count();

        $communityPosts = CommunityPost::query()
            ->inBarangay($barangay)
            ->count();

        return response()->json([
            'message' => 'Official dashboard summary loaded.',
            'summary' => [
                'barangay' => $barangay,
                'population' => $population,
                'registered_residents' => (int) $residentUsers,
                'population_from_household_size' => (int) $populationFromHousehold,
                'requests_total' => (int) $totalRequests,
                'total_requests' => (int) $totalRequests,
                'requests_pending' => (int) $pendingRequests,
                'pending_requests' => (int) $pendingRequests,
                'market_products_total' => (int) $totalProducts,
                'market_products' => (int) $totalProducts,
                'market_products_verified' => (int) $verifiedProducts,
                'verified_market_products' => (int) $verifiedProducts,
                'community_posts_total' => (int) $communityPosts,
                'community_posts' => (int) $communityPosts,
            ],
            'population' => (int) $population,
        ]);
    }

    private function authenticatedUserOrNull(): ?User
    {
        /** @var User|null $user */
        return Auth::guard('api')->user();
    }
}
