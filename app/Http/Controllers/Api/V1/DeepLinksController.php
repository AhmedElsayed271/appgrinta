<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\DeepLinkService;
use App\Traits\ApiResponser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DeepLinksController extends Controller
{
    use ApiResponser;

    /**
     * Create (or reuse) a short link for a post, league, match or team.
     */
    public function store(Request $request, DeepLinkService $service): JsonResponse
    {
        $data = $request->validate([
            'type' => ['required', Rule::in($service->allowedTypes())],
            'id'   => ['required', 'integer', 'min:1'],
        ]);

        $link = $service->create($data['type'], (int) $data['id']);

        if (!$link) {
            return $this->errorResponse('Target not found', 404);
        }

        return $this->successResponse($service->resolve($link->code), 200);
    }

    /**
     * Used by the mobile app to turn a code (share link, Play referrer or
     * manually entered iOS claim code) back into a content target.
     */
    public function resolve(Request $request, DeepLinkService $service): JsonResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:32'],
        ]);

        $payload = $service->resolve($data['code']);

        if (!$payload) {
            return $this->errorResponse('Deep link not found', 404);
        }

        return $this->successResponse($payload, 200);
    }

    public function click(Request $request, DeepLinkService $service): JsonResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:32'],
        ]);

        $payload = $service->registerClick($data['code']);

        if (!$payload) {
            return $this->errorResponse('Deep link not found', 404);
        }

        return $this->successResponse($payload, 200);
    }

    public function claim(Request $request, DeepLinkService $service): JsonResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:32'],
        ]);

        $payload = $service->resolve($data['code']);

        if (!$payload) {
            return $this->errorResponse('Deep link not found', 404);
        }

        return $this->successResponse($payload, 200);
    }
}
