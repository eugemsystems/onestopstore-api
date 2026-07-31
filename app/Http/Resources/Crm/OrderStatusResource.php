<?php

namespace App\Http\Resources\Crm;

use Illuminate\Http\Resources\Json\JsonResource;

class OrderStatusResource extends JsonResource
{
    public function toArray($request)
    {
        $os = $this->resource;

        return [
            'id'               => $os->id,
            'name'             => $os->name,
            'slug'             => $os->slug,
            'sequence'         => $os->sequence,
            'created_at'       => $os->created_at,
            'updated_at'       => $os->updated_at,
        ];
    }
}
