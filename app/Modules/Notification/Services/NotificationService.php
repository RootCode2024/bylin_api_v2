<?php

declare(strict_types=1);

namespace Modules\Notification\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Queue;
use Modules\Notification\Models\Notification;

class NotificationService
{
    /**
     * Send notification to user
     */
    public function notify(
        Model $user,
        string $type,
        string $title,
        string $message,
        array $data = [],
        array $channels = ['database'],
        string $priority = 'normal'
    ): Notification {
        $notification = Notification::create([
            'notifiable_id' => $user->id,
            'notifiable_type' => get_class($user),
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'channel' => implode(',', $channels),
            'priority' => $priority,
            'data' => array_merge($data, [
                'channels' => $channels,
            ]),
            'status' => Notification::STATUS_PENDING,
        ]);

        // Queue notifications for processing
        if (in_array('email', $channels)) {
            Queue::push(new \Modules\Notification\Jobs\SendEmailNotification($notification));
        }

        if (in_array('database', $channels)) {
            // Database notification is already created, just mark as sent
            $notification->markAsSent();
        }

        return $notification;
    }

    /**
     * Send order confirmation notification
     */
    public function orderConfirmation(Model $customer, $order): void
    {
        $this->notify(
            $customer,
            Notification::TYPE_ORDER_CONFIRMATION,
            'Order Confirmed',
            "Your order #{$order->id} has been confirmed and is being processed.",
            [
                'order_id' => $order->id,
                'order_total' => $order->total,
                'action_url' => route('api.customer.orders.show', $order->id),
                'action_text' => 'View Order',
            ],
            ['database', 'email'],
            'high'
        );
    }

    /**
     * Send payment success notification
     */
    public function paymentSuccess(Model $customer, $order, $amount): void
    {
        $this->notify(
            $customer,
            Notification::TYPE_PAYMENT_SUCCESS,
            'Payment Successful',
            "Your payment of {$amount} has been processed successfully.",
            [
                'order_id' => $order->id,
                'amount' => $amount,
            ],
            ['database', 'email'],
            'high'
        );
    }

    /**
     * Send payment failed notification
     */
    public function paymentFailed(Model $customer, $order, string $reason): void
    {
        $this->notify(
            $customer,
            Notification::TYPE_PAYMENT_FAILED,
            'Payment Failed',
            "Your payment could not be processed. Reason: {$reason}",
            [
                'order_id' => $order->id,
                'reason' => $reason,
                'action_url' => route('api.customer.orders.show', $order->id),
                'action_text' => 'Retry Payment',
            ],
            ['database', 'email'],
            'urgent'
        );
    }

    /**
     * Send order shipped notification
     */
    public function orderShipped(Model $customer, $order, string $trackingNumber = null): void
    {
        $message = "Your order #{$order->id} has been shipped";
        if ($trackingNumber) {
            $message .= " with tracking number: {$trackingNumber}";
        }

        $this->notify(
            $customer,
            Notification::TYPE_ORDER_SHIPPED,
            'Order Shipped',
            $message,
            [
                'order_id' => $order->id,
                'tracking_number' => $trackingNumber,
                'action_url' => route('api.customer.orders.show', $order->id),
                'action_text' => 'Track Order',
            ],
            ['database', 'email']
        );
    }

    /**
     * Send new device alert
     */
    public function newDeviceAlert(Model $user, array $deviceInfo, array $location): void
    {
        $this->notify(
            $user,
            'new_device_login',
            'New Device Login Detected',
            "A new login from {$deviceInfo['device_name']} in {$location['city']}, {$location['country']} was detected.",
            [
                'device' => $deviceInfo,
                'location' => $location,
                'icon' => 'shield-exclamation',
            ],
            ['database', 'email'],
            'high'
        );
    }

    /**
     * Mark notification as read
     */
    public function markAsRead(string $notificationId): void
    {
        $notification = Notification::find($notificationId);
        if ($notification) {
            $notification->markAsRead();
        }
    }

    /**
     * Mark all notifications as read for user
     */
    public function markAllAsRead(Model $user): int
    {
        return Notification::forNotifiable(get_class($user), $user->id)
            ->unread()
            ->update(['read_at' => now()]);
    }

    /**
     * Get unread count for user
     */
    public function getUnreadCount(Model $user): int
    {
        return Notification::forNotifiable(get_class($user), $user->id)
            ->unread()
            ->count();
    }

    /**
     * Get recent notifications for user
     */
    public function getRecent(Model $user, int $limit = 10)
    {
        return Notification::forNotifiable(get_class($user), $user->id)
            ->latest()
            ->limit($limit)
            ->get();
    }
}
