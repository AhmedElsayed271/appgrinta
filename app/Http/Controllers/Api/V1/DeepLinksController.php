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
     * Create (or reuse) a short link for a post, league, match, team or screen.
     */
    public function store(Request $request, DeepLinkService $service): JsonResponse
    {
        $data = $request->validate([
            'type'        => ['required', Rule::in($service->allowedTypes())],
            // `id` is our local database id (screen number for type=screen).
            'id'          => ['nullable', 'integer', 'min:0'],
            'football_id' => ['nullable', 'integer', 'min:0'],
            'screen'      => ['nullable'],
        ]);

        $targetId   = $data['id'] ?? null;
        $footballId = $data['football_id'] ?? null;

        // A Football API id must be sent in `football_id`, never in `id`.
        if ($targetId === null && $footballId === null) {
            return $this->errorResponse('id or football_id is required', 422);
        }

        // `screen` is caller-supplied and stored with the link. It is omitted
        // (null) when not sent, and rejected when it is an unknown value.
        $screen = null;
        if (array_key_exists('screen', $data) && $data['screen'] !== null) {
            $screen = $service->normalizeScreen($data['screen']);

            if ($screen === null) {
                return $this->errorResponse('Unknown screen', 422);
            }
        }

        if ($data['type'] === DeepLinkService::SCREEN_TYPE
            && ($targetId === null || !array_key_exists((int) $targetId, $service->screens()))) {
            return $this->errorResponse('Unknown screen', 422);
        }

        $link = $service->create(
            $data['type'],
            $targetId !== null ? (int) $targetId : null,
            $footballId !== null ? (int) $footballId : null,
            $screen
        );

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
