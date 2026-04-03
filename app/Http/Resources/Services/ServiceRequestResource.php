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
        $attachments = is_array($this->attachments_json) ? $this->attachments_json : [];
        $attachmentNames = [];
        foreach ($attachments as $item) {
            if (!is_array($item)) {
                continue;
            }
            $name = trim((string) ($item['file_name'] ?? ''));
            if ($name !== '') {
                $attachmentNames[] = $name;
            }
        }
        return [
            'id' => $this->id,
            'service_category' => $this->service_category,
            'service_title' => $this->service_title,
            'request_id' => $this->request_id,
            'purpose' => $this->purpose,
            'details' => $this->details,
            'attachment_names' => $attachmentNames,
            'attachment_count' => count($attachmentNames),
            'status' => $this->status,
            'submitted_at' => optional($this->created_at)?->toIso8601String(),
        ];
    }
}
