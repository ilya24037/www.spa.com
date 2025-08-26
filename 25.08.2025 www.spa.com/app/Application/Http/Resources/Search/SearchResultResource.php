<?php

namespace App\Application\Http\Resources\Search;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SearchResultResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'data' => $this->resource->items(),
            'pagination' => [
                'total' => $this->resource->total(),
                'per_page' => $this->resource->perPage(),
                'current_page' => $this->resource->currentPage(),
                'last_page' => $this->resource->lastPage(),
                'from' => $this->resource->firstItem(),
                'to' => $this->resource->lastItem(),
            ],
            'links' => [
                'first' => $this->resource->url(1),
                'last' => $this->resource->url($this->resource->lastPage()),
                'prev' => $this->resource->previousPageUrl(),
                'next' => $this->resource->nextPageUrl(),
            ],
            'meta' => [
                'path' => $this->resource->path(),
                'has_more_pages' => $this->resource->hasMorePages(),
                'query_time' => $this->getQueryTime(),
                'search_type' => $request->get('type', 'ads'),
            ]
        ];
    }

    /**
     * Get query execution time if available
     */
    protected function getQueryTime(): ?float
    {
        return $this->resource->getCollection()->first()?->query_time ?? null;
    }
}