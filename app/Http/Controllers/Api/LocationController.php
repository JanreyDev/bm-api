<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Locations\LocationDirectoryService;
use Illuminate\Http\JsonResponse;

class LocationController extends Controller
{
    public function __construct(
        private readonly LocationDirectoryService $locationDirectoryService,
    ) {
    }

    public function index(): JsonResponse
    {
        $payload = $this->locationDirectoryService->build();

        return response()->json([
            'message' => 'Address directory loaded.',
            'count' => $payload['count'],
            'data' => $payload['data'],
            'directory' => $payload['directory'],
        ]);
    }
}
