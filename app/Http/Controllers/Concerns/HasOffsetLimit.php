<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

trait HasOffsetLimit
{
    protected function offsetItems(Builder $query, Request $request, int $defaultLimit = 10): array
    {
        [$offset, $limit, $page] = $this->paginationParams($request, $defaultLimit);
        $total  = (clone $query)->toBase()->getCountForPagination();
        $items  = $query->skip($offset)->take($limit)->get();

        return [$items, [
            'total'    => $total,
            'offset'   => $offset,
            'limit'    => $limit,
            'per_page' => $limit,
            'current_page' => $page,
            'last_page' => (int) ceil($total / $limit),
            'has_more' => $offset + $items->count() < $total,
        ]];
    }

    protected function paginateWithOffset(Builder $query, Request $request): LengthAwarePaginator
    {
        [$offset, $limit, $page] = $this->paginationParams($request);
        $total = (clone $query)->toBase()->getCountForPagination();
        $items = $query->skip($offset)->take($limit)->get();

        return new LengthAwarePaginator(
            $items,
            $total,
            $limit,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );
    }

    protected function offsetLimit(Request $request, int $defaultLimit = 10): array
    {
        [$offset, $limit] = $this->paginationParams($request, $defaultLimit);

        return [$offset, $limit];
    }

    private function paginationParams(Request $request, int $defaultLimit = 10): array
    {
        $limit = min(100, max(1, (int) $request->query('limit', $defaultLimit)));

        if ($request->query->has('offset')) {
            $offset = max(0, (int) $request->query('offset', 0));
            $page = (int) floor($offset / $limit) + 1;

            return [$offset, $limit, $page];
        }

        $page = max(1, (int) $request->query('page', 1));

        return [($page - 1) * $limit, $limit, $page];
    }
}
