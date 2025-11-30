<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class NotificationController extends Controller
{
    /**
     * Get notifications for the current user (API endpoint)
     */
    public function index()
    {
        try {
            $notifications = DB::table('notifications')
                ->orderBy('created_at', 'desc')
                ->limit(50)
                ->get();

            $unreadCount = DB::table('notifications')
                ->where('is_read', false)
                ->count();

            // If it's an AJAX request for the dropdown
            if (request()->wantsJson() || request()->ajax()) {
                return response()->json([
                    'notifications' => $notifications,
                    'unread_count' => $unreadCount
                ]);
            }

            // If it's a page request for all notifications
            return response()->json([
                'notifications' => DB::table('notifications')
                    ->orderBy('created_at', 'desc')
                    ->get(),
                'unread_count' => $unreadCount
            ]);
        } catch (\Exception $e) {
            Log::error('Load notifications error: ' . $e->getMessage());

            return response()->json([
                'notifications' => [],
                'unread_count' => 0,
                'error' => 'Failed to load notifications'
            ], 500);
        }
    }
    public function showAll()
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return redirect()->route('login')->with('error', 'Please log in to access notifications.');
            }

            // Return the view - make sure this path matches your actual file location
            return view('admin.notifications-all');
        } catch (\Exception $e) {
            Log::error('Error displaying notifications page: ' . $e->getMessage());
            return back()->with('error', 'Failed to load notifications page.');
        }
    }
    /**
     * Mark a specific notification as read
     */
    public function markAsRead(Request $request, $id)
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }

            $notification = Notification::where('id', $id)
                ->where('user_id', $user->id)
                ->first();

            if (!$notification) {
                return response()->json(['error' => 'Notification not found'], 404);
            }

            // Only update if it's currently unread
            if (!$notification->is_read) {
                $notification->update(['is_read' => true]);

                Log::info('Notification marked as read', [
                    'notification_id' => $id,
                    'user_id' => $user->id
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Notification marked as read',
                'notification' => $notification
            ]);
        } catch (\Exception $e) {
            Log::error('Error marking notification as read: ' . $e->getMessage());

            return response()->json([
                'error' => 'Failed to mark notification as read: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mark all notifications as read for the current user
     */
    public function markAllAsRead(Request $request)
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }

            $updatedCount = Notification::where('user_id', $user->id)
                ->where('is_read', false)
                ->update(['is_read' => true]);

            Log::info('All notifications marked as read', [
                'user_id' => $user->id,
                'updated_count' => $updatedCount
            ]);

            return response()->json([
                'success' => true,
                'message' => 'All notifications marked as read',
                'updated_count' => $updatedCount
            ]);
        } catch (\Exception $e) {
            Log::error('Error marking all notifications as read: ' . $e->getMessage());

            return response()->json([
                'error' => 'Failed to mark all notifications as read: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get unread notification count
     */
    public function getUnreadCount(Request $request)
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }

            $unreadCount = Notification::where('user_id', $user->id)
                ->where('is_read', false)
                ->count();

            return response()->json([
                'success' => true,
                'unread_count' => $unreadCount
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting unread count: ' . $e->getMessage());

            return response()->json([
                'error' => 'Failed to get unread count'
            ], 500);
        }
    }

    /**
     * Delete a specific notification
     */
    public function destroy(Request $request, $id)
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }

            $notification = Notification::where('id', $id)
                ->where('user_id', $user->id)
                ->first();

            if (!$notification) {
                return response()->json(['error' => 'Notification not found'], 404);
            }

            $notification->delete();

            Log::info('Notification deleted', [
                'notification_id' => $id,
                'user_id' => $user->id
            ]);

            // Return appropriate response based on request type
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Notification deleted successfully'
                ]);
            } else {
                return back()->with('success', 'Notification deleted successfully.');
            }
        } catch (\Exception $e) {
            Log::error('Error deleting notification: ' . $e->getMessage());

            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'Failed to delete notification'
                ], 500);
            } else {
                return back()->with('error', 'Failed to delete notification.');
            }
        }
    }

    /**
     * Delete multiple notifications at once
     */
    public function bulkDelete(Request $request)
    {
        $ids = $request->input('ids', []);

        if (empty($ids)) {
            return response()->json([
                'success' => false,
                'message' => 'No notifications selected'
            ], 400);
        }

        try {
            $user = Auth::user();

            if (!$user) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }

            // Only delete notifications that belong to the current user
            $deleted = Notification::whereIn('id', $ids)
                ->where('user_id', $user->id)
                ->delete();

            Log::info('Bulk delete notifications', [
                'user_id' => $user->id,
                'deleted_count' => $deleted
            ]);

            return response()->json([
                'success' => true,
                'message' => $deleted . ' notification(s) deleted successfully',
                'deleted_count' => $deleted
            ]);
        } catch (\Exception $e) {
            Log::error('Bulk delete notifications error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete notifications'
            ], 500);
        }
    }

    /**
     * Clear all read notifications for current user
     */
    public function clearRead()
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }

            $deleted = Notification::where('user_id', $user->id)
                ->where('is_read', true)
                ->delete();

            Log::info('Clear read notifications', [
                'user_id' => $user->id,
                'deleted_count' => $deleted
            ]);

            return response()->json([
                'success' => true,
                'message' => $deleted . ' read notification(s) cleared successfully',
                'deleted_count' => $deleted
            ]);
        } catch (\Exception $e) {
            Log::error('Clear read notifications error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to clear read notifications'
            ], 500);
        }
    }
}
