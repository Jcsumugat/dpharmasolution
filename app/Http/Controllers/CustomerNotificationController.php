<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\CustomerNotification;

class CustomerNotificationController extends Controller
{
    public function index(Request $request)
    {
        if (!Auth::guard('customer')->check()) {
            return redirect()->route('login.form')->with('error', 'Please log in to view notifications.');
        }

        $customer = Auth::guard('customer')->user();
        
        // Get notifications for this customer
        $notifications = CustomerNotification::with(['prescription'])
            ->forCustomer($customer->id)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        // Get unread count
        $unreadCount = CustomerNotification::forCustomer($customer->id)
            ->unread()
            ->count();

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'notifications' => $notifications->items(),
                'unread_count' => $unreadCount,
                'total' => $notifications->total()
            ]);
        }

        return view('client.notifications', compact('notifications', 'unreadCount'));
    }

    public function markAsRead(Request $request, $id)
    {
        if (!Auth::guard('customer')->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $customer = Auth::guard('customer')->user();

        try {
            $notification = CustomerNotification::where('id', $id)
                ->where('customer_id', $customer->id)
                ->first();

            if (!$notification) {
                return response()->json(['success' => false, 'message' => 'Notification not found'], 404);
            }

            $notification->update(['is_read' => true]);

            Log::info("Customer notification marked as read", [
                'notification_id' => $id,
                'customer_id' => $customer->id
            ]);

            return response()->json(['success' => true, 'message' => 'Notification marked as read']);
        } catch (\Exception $e) {
            Log::error('Error marking customer notification as read: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error updating notification'], 500);
        }
    }

    public function markAllAsRead(Request $request)
    {
        if (!Auth::guard('customer')->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $customer = Auth::guard('customer')->user();

        try {
            $updated = CustomerNotification::forCustomer($customer->id)
                ->unread()
                ->update(['is_read' => true]);

            Log::info("All customer notifications marked as read", [
                'customer_id' => $customer->id,
                'count' => $updated
            ]);

            return response()->json([
                'success' => true, 
                'message' => 'All notifications marked as read',
                'count' => $updated
            ]);
        } catch (\Exception $e) {
            Log::error('Error marking all customer notifications as read: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error updating notifications'], 500);
        }
    }

    public function getUnreadCount(Request $request)
    {
        if (!Auth::guard('customer')->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $customer = Auth::guard('customer')->user();

        $count = CustomerNotification::forCustomer($customer->id)
            ->unread()
            ->count();

        return response()->json([
            'success' => true,
            'unread_count' => $count
        ]);
    }

    public function destroy(Request $request, $id)
    {
        if (!Auth::guard('customer')->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $customer = Auth::guard('customer')->user();

        try {
            $notification = CustomerNotification::where('id', $id)
                ->where('customer_id', $customer->id)
                ->first();

            if (!$notification) {
                return response()->json(['success' => false, 'message' => 'Notification not found'], 404);
            }

            $notification->delete();

            return response()->json(['success' => true, 'message' => 'Notification deleted']);
        } catch (\Exception $e) {
            Log::error('Error deleting customer notification: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error deleting notification'], 500);
        }
    }
}