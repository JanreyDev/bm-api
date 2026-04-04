<?php

namespace App\Http\Controllers\Api\Official;

use App\Http\Controllers\Controller;
use App\Models\CommunityPost;
use App\Models\MarketProduct;
use App\Models\MerchantRegistration;
use App\Models\OfficialBarangaySetup;
use App\Models\ResidentProfile;
use App\Models\ResidentRbiRecord;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
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

        if ($user->role !== 'official') {
            return response()->json([
                'message' => 'Only official accounts can access dashboard summary.',
            ], 403);
        }

        [$province, $city, $barangay] = $this->resolveScope($request, $user);
        if ($barangay === '') {
            return response()->json([
                'message' => 'Set your barangay in your profile before opening dashboard summary.',
            ], 422);
        }

        $municipalUsersQuery = User::query()
            ->whereIn('role', ['resident', 'official'])
            ->where(static function (Builder $query) use ($province, $city): void {
                if (trim($province) !== '') {
                    self::applyScopeFilter($query, 'province', $province);
                }
                if (trim($city) !== '') {
                    self::applyScopeFilter($query, 'city_municipality', $city);
                }
            });
        $residentUsers = (clone $municipalUsersQuery)
            ->where('role', 'resident')
            ->count();
        $officialUsers = (clone $municipalUsersQuery)
            ->where('role', 'official')
            ->count();
        $registeredUsersTotal = (clone $municipalUsersQuery)
            ->count();

        $populationFromHousehold = ResidentProfile::query()
            ->whereHas('user', static function (Builder $query) use ($province, $city): void {
                $query
                    ->where('role', 'resident')
                    ->where(static function (Builder $scope) use ($province, $city): void {
                        if (trim($province) !== '') {
                            self::applyScopeFilter($scope, 'province', $province);
                        }
                        if (trim($city) !== '') {
                            self::applyScopeFilter($scope, 'city_municipality', $city);
                        }
                    });
            })
            ->sum('household_size');

        // Dashboard "Total Population" should reflect registered account count
        // (resident + official) for the official's municipality scope.
        $population = (int) $registeredUsersTotal;

        $verifiedRbiCount = ResidentRbiRecord::query()
            ->where('barangay', $barangay)
            ->where('verification_step', '>=', 2)
            ->count();

        if ($verifiedRbiCount === 0) {
            $verifiedRbiCount = ResidentProfile::query()
            ->whereHas('user', static function ($query) use ($barangay): void {
                $query
                    ->where('role', 'resident')
                    ->where('barangay', $barangay);
            })
            ->where('is_verified', true)
            ->count();
        }

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
        
        $verifiedMerchants = MerchantRegistration::query()
            ->inBarangay($barangay)
            ->where('merchant_verified', true)
            ->count();

        return response()->json([
            'message' => 'Official dashboard summary loaded.',
            'summary' => [
                'barangay' => $barangay,
                'province' => $province,
                'city_municipality' => $city,
                'population' => $population,
                'registered_residents' => (int) $residentUsers,
                'registered_officials' => (int) $officialUsers,
                'registered_users_total' => (int) $registeredUsersTotal,
                'rbi_count' => (int) $verifiedRbiCount,
                'verified_rbi_count' => (int) $verifiedRbiCount,
                'population_from_household_size' => (int) $populationFromHousehold,
                'requests_total' => (int) $totalRequests,
                'total_requests' => (int) $totalRequests,
                'requests_pending' => (int) $pendingRequests,
                'pending_requests' => (int) $pendingRequests,
                'market_products_total' => (int) $totalProducts,
                'market_products' => (int) $totalProducts,
                'market_products_verified' => (int) $verifiedProducts,
                'verified_market_products' => (int) $verifiedProducts,
                'verified_merchants' => (int) $verifiedMerchants,
                'merchant_verified_count' => (int) $verifiedMerchants,
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

    /**
     * @return array{string,string,string}
     */
    private function resolveScope(Request $request, User $user): array
    {
        $province = trim((string) $user->province);
        $city = trim((string) $user->city_municipality);
        $barangay = trim((string) $user->barangay);

        if ($province === '' || $city === '' || $barangay === '') {
            $setup = OfficialBarangaySetup::query()
                ->where('updated_by_user_id', $user->id)
                ->latest('id')
                ->first();
            if ($setup !== null) {
                if ($province === '') {
                    $province = trim((string) $setup->province);
                }
                if ($city === '') {
                    $city = trim((string) $setup->city_municipality);
                }
                if ($barangay === '') {
                    $barangay = trim((string) $setup->barangay);
                }
            }
        }
        $updates = [];
        if ($province === '') {
            $province = trim((string) $request->query('province', ''));
            if ($province !== '') {
                $updates['province'] = mb_substr($province, 0, 100);
            }
        }
        if ($city === '') {
            $city = trim((string) $request->query('city_municipality', ''));
            if ($city !== '') {
                $updates['city_municipality'] = mb_substr($city, 0, 100);
            }
        }
        if ($barangay === '') {
            $fallback = trim((string) $request->query('barangay', ''));
            if ($fallback !== '') {
                $barangay = mb_substr($fallback, 0, 191);
                $updates['barangay'] = $barangay;
            }
        }

        if ($updates !== []) {
            $user->forceFill($updates)->save();
        }

        return [$province, $city, $barangay];
    }

    private static function applyScopeFilter(Builder $query, string $column, string $value): void
    {
        $query->whereRaw(
            sprintf('LOWER(TRIM(%s)) = ?', $column),
            [mb_strtolower(trim($value))]
        );
    }
}
