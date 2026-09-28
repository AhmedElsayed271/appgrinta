<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationPaginationResource;
use App\Models\Notification;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;

class ClientNotificationsController extends Controller
{
    use ApiResponser;

    public function index(Request $request): \Illuminate\Http\JsonResponse
    {
        $clientId = $request->user()->getAuthIdentifier();
        $query    = Notification::query()->with('translations')->where('client_id', $clientId);

        if ($request->has('type') && $request->input('type') != '') {
            $query->where('type', $request->input('type'));
        }

        $query->orderByDesc('id');

        $perPage = 15;
        if ($request->has('per_page')) {
            $perPage = (int)$request->input('per_page');
            $perPage = max(2, min(50, $perPage));
        }

        return $this->successResponse(NotificationPaginationResource::make($query->paginate($perPage)), 200);
    }

    public function unreadCount(Request $request): \Illuminate\Http\JsonResponse
    {
        $count = Notification::query()->where('client_id', $request->user()->getAuthIdentifier())
            ->unread()
            ->count();

        return $this->successResponse(['count' => (int)$count], 200);
    }

    public function read(Request $request): \Illuminate\Http\JsonResponse
    {
        $hasId   = $request->filled('id');
        $hasIds  = $request->filled('ids');

        if (!$hasId && !$hasIds) {
            return $this->errorResponse('Provide id or ids', 422);
        }

        $request->validate([
            'id'    => 'nullable|integer',
            'ids'   => 'nullable|array',
            'ids.*' => 'integer',
        ]);

        $ids = $request->filled('ids') ? $request->input('ids') : [$request->input('id')];

        $count = Notification::query()->where('client_id', $request->user()->getAuthIdentifier())
            ->unread()
            ->whereIn('id', $ids)
            ->update(['is_read' => true, 'updated_at' => now()]);

        return $this->successResponse(['count' => (int)$count], 200);
    }

    public function readAll(Request $request): \Illuminate\Http\JsonResponse
    {
        $count = Notification::query()->where('client_id', $request->user()->getAuthIdentifier())
            ->unread()
            ->update(['is_read' => true, 'updated_at' => now()]);

        return $this->successResponse(['count' => (int)$count], 200);
    }

    public function destroy(Notification $notification, Request $request): \Illuminate\Http\JsonResponse
    {
        Notification::query()
            ->where('id', $notification->id)
            ->where('client_id', $request->user()->getAuthIdentifier())
            ->delete();

        return $this->successResponse(['id' => (int)$notification->id], 200);
    }
}