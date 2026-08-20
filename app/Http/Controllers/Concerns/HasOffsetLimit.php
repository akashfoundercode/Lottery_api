<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

trait HasOffsetLimit
{
    protected function offsetItems(Builder $query, Request $request, int $defaultLimit = 10): array
    {
        $offset = max(0, (int) $request->query('offset', 0));
        $limit  = min(100, max(1, (int) $request->query('limit', $defaultLimit)));
        $total  = (clone $query)->toBase()->getCountForPagination();
        $items  = $query->skip($offset)->take($limit)->get();

        return [$items, [
            'total'    => $total,
            'offset'   => $offset,
            'limit'    => $limit,
            'has_more' => $offset + $items->count() < $total,
        ]];
    }

    protected function paginateWithOffset(Builder $query, Request $request): LengthAwarePaginator
    {
        $offset = max(0, (int) $request->query('offset', 0));
        $limit = min(100, max(1, (int) $request->query('limit', 10)));
        $total = (clone $query)->toBase()->getCountForPagination();
        $items = $query->skip($offset)->take($limit)->get();

        return new LengthAwarePaginator(
            $items,
            $total,
            $limit,
            (int) floor($offset / $limit) + 1,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );
    }

    protected function offsetLimit(Request $request, int $defaultLimit = 10): array
    {
        return [
            max(0, (int) $request->query('offset', 0)),
            min(100, max(1, (int) $request->query('limit', $defaultLimit))),
        ];
    }
}
