<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    /**
     * Get notifications for the current user (API endpoint)
     */
    public function index(Request $request)
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return response()->json([
                    'error' => 'Unauthorized',
                    'notifications' => [],
                    'unread_count' => 0
                ], 401);
            }

            // Get notifications for the current user, ordered by newest first
            $notifications = Notification::where('user_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->limit(50) // Limit to last 50 notifications for dropdown
                ->get();

            // Count unread notifications
            $unreadCount = Notification::where('user_id', $user->id)
                ->where('is_read', false)
                ->count();

            // Log for debugging
            Log::info('Notifications fetched', [
                'user_id' => $user->id,
                'count' => $notifications->count(),
                'unread_count' => $unreadCount
            ]);

            return response()->json([
                'success' => true,
                'notifications' => $notifications,
                'unread_count' => $unreadCount
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching notifications: ' . $e->getMessage());

            return response()->json([
                'error' => 'Failed to fetch notifications: ' . $e->getMessage(),
                'notifications' => [],
                'unread_count' => 0
            ], 500);
        }
    }

    /**
     * Display all notifications page (Blade view)
     */
    public function showAll(Request $request)
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return redirect()->route('admin.login')->with('error', 'Please log in to access notifications.');
            }

            // Get per page setting from request or default to 20
            $perPage = $request->get('per_page', 20);

            // Get status filter (all, unread, read)
            $status = $request->get('status', 'all');

            // Build query
            $query = Notification::where('user_id', $user->id)
                ->orderBy('created_at', 'desc');

            // Apply status filter
            if ($status === 'unread') {
                $query->where('is_read', false);
            } elseif ($status === 'read') {
                $query->where('is_read', true);
            }

            // Get paginated notifications
            $notifications = $query->paginate($perPage);

            // Get counts for filter tabs
            $totalCount = Notification::where('user_id', $user->id)->count();
            $unreadCount = Notification::where('user_id', $user->id)->where('is_read', false)->count();
            $readCount = $totalCount - $unreadCount;

            return view('admin.notifications.index', compact(
                'notifications',
                'totalCount',
                'unreadCount',
                'readCount',
                'status',
                'perPage'
            ));

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
     * Bulk delete notifications
     */
    public function bulkDelete(Request $request)
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }

            $request->validate([
                'notification_ids' => 'required|array',
                'notification_ids.*' => 'integer|exists:notifications,id'
            ]);

            $deletedCount = Notification::where('user_id', $user->id)
                ->whereIn('id', $request->notification_ids)
                ->delete();

            Log::info('Bulk notifications deleted', [
                'user_id' => $user->id,
                'deleted_count' => $deletedCount
            ]);

            return response()->json([
                'success' => true,
                'message' => "Successfully deleted {$deletedCount} notifications",
                'deleted_count' => $deletedCount
            ]);

        } catch (\Exception $e) {
            Log::error('Error bulk deleting notifications: ' . $e->getMessage());

            return response()->json([
                'error' => 'Failed to delete notifications: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Clear all read notifications for the current user
     */
    public function clearRead(Request $request)
    {
        try {
            $user = Auth::user();

            if (!$user) {
                if ($request->expectsJson()) {
                    return response()->json(['error' => 'Unauthorized'], 401);
                }
                return back()->with('error', 'Unauthorized access.');
            }

            $deletedCount = Notification::where('user_id', $user->id)
                ->where('is_read', true)
                ->delete();

            Log::info('All read notifications cleared', [
                'user_id' => $user->id,
                'deleted_count' => $deletedCount
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => "Successfully cleared {$deletedCount} read notifications",
                    'deleted_count' => $deletedCount
                ]);
            } else {
                return back()->with('success', "Successfully cleared {$deletedCount} read notifications.");
            }

        } catch (\Exception $e) {
            Log::error('Error clearing read notifications: ' . $e->getMessage());

            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'Failed to clear read notifications'
                ], 500);
            } else {
                return back()->with('error', 'Failed to clear read notifications.');
            }
        }
    }
}
