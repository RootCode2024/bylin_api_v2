<?php

declare(strict_types=1);

namespace Modules\Notification\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\Http\Controllers\ApiController;
use Modules\Notification\Models\Notification;

class NotificationController extends ApiController
{
    /**
     * Get notifications for authenticated user
     */
    public function index(Request $request): JsonResponse
    {
        $user = auth()->user();
        
        // Determine notifiable type based on guard
        $notifiableType = $user instanceof \Modules\Customer\Models\Customer 
            ? 'Modules\\Customer\\Models\\Customer'
            : 'Modules\\User\\Models\\User';

        $query = Notification::forNotifiable($notifiableType, $user->id)
            ->latest();

        // Filter by read/unread
        if ($request->has('unread_only')) {
            $query->unread();
        }

        // Filter by channel
        if ($request->has('channel')) {
            $query->channel($request->channel);
        }

        // Filter by type
        if ($request->has('type')) {
            $query->type($request->type);
        }

        $notifications = $query->paginate($request->per_page ?? 15);

        return $this->successResponse($notifications);
    }

    /**
     * Mark a notification as read
     */
    public function markAsRead(string $id): JsonResponse
    {
        $user = auth()->user();
        
        $notifiableType = $user instanceof \Modules\Customer\Models\Customer 
            ? 'Modules\\Customer\\Models\\Customer'
            : 'Modules\\User\\Models\\User';

        $notification = Notification::forNotifiable($notifiableType, $user->id)
            ->findOrFail($id);

        $notification->markAsRead();

        return $this->successResponse($notification, 'Notification marked as read');
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead(): JsonResponse
    {
        $user = auth()->user();
        
        $notifiableType = $user instanceof \Modules\Customer\Models\Customer 
            ? 'Modules\\Customer\\Models\\Customer'
            : 'Modules\\User\\Models\\User';

        $count = Notification::forNotifiable($notifiableType, $user->id)
            ->unread()
            ->update(['read_at' => now()]);

        return $this->successResponse(
            ['count' => $count],
            "{$count} notification(s) marked as read"
        );
    }

    /**
     * Get unread count
     */
    public function unreadCount(): JsonResponse
    {
        $user = auth()->user();
        
        $notifiableType = $user instanceof \Modules\Customer\Models\Customer 
            ? 'Modules\\Customer\\Models\\Customer'
            : 'Modules\\User\\Models\\User';

        $count = Notification::forNotifiable($notifiableType, $user->id)
            ->unread()
            ->count();

        return $this->successResponse(['count' => $count]);
    }
}
