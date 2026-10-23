<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Pagination\AbstractPaginator;

/**
 * One response envelope for the whole API:
 *   { "success": bool, "message": string, "data": ..., "meta": {...}, "errors": {...} }
 */
trait ApiResponse
{
    protected function success(string $message, mixed $data = null, int $status = 200): JsonResponse
    {
        $payload = ['success' => true, 'message' => $message];

        if ($data instanceof JsonResource) {
            $payload['data'] = $data->resolve(request());
        } elseif ($data !== null) {
            $payload['data'] = $data;
        }

        return response()->json($payload, $status);
    }

    protected function created(string $message, mixed $data = null): JsonResponse
    {
        return $this->success($message, $data, 201);
    }

    /** Paginated list: `data` is the resource collection, `meta` and `links` come from the paginator. */
    protected function paginated(string $message, AbstractPaginator $paginator, string $resourceClass): JsonResponse
    {
        $items = $resourceClass::collection($paginator->getCollection())->resolve(request());

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $items,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
            'links' => [
                'next' => $paginator->nextPageUrl(),
                'prev' => $paginator->previousPageUrl(),
            ],
        ]);
    }

    protected function collection(string $message, ResourceCollection|\Illuminate\Support\Collection $items, string $resourceClass = null): JsonResponse
    {
        $data = $resourceClass ? $resourceClass::collection($items)->resolve(request()) : $items;

        return response()->json(['success' => true, 'message' => $message, 'data' => $data]);
    }

    protected function error(string $message, mixed $errors = null, int $status = 400): JsonResponse
    {
        $payload = ['success' => false, 'message' => $message];

        if ($errors !== null) {
            $payload['errors'] = $errors;
        }

        return response()->json($payload, $status);
    }
}
