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
            'type'   => ['required', Rule::in($service->allowedTypes())],
            // The Football API id for league/match/team, our internal id for
            // post, or the screen number for type=screen.
            'id'     => ['required', 'integer', 'min:0'],
            'screen' => ['nullable'],
        ]);

        $id = (int) $data['id'];

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
            && !array_key_exists($id, $service->screens())) {
            return $this->errorResponse('Unknown screen', 422);
        }

        $link = $service->create($data['type'], $id, $screen);

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
