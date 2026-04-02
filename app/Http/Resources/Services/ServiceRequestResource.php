<?php

namespace App\Http\Resources\Services;

use App\Models\ServiceRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ServiceRequest */
class ServiceRequestResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'service_category' => $this->service_category,
            'service_title' => $this->service_title,
            'request_id' => $this->request_id,
            'purpose' => $this->purpose,
            'details' => $this->details,
            'status' => $this->status,
            'submitted_at' => optional($this->created_at)?->toIso8601String(),
        ];
    }
}

