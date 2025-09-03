<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\CustomerNotification;
use App\Models\Product;
use App\Models\Prescription;
use App\Models\Order;
use App\Models\Sale;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    /**
     * Create a notification for a specific user or all admins
     */
    public static function createNotification($userId, $title, $message, $isRead = false)
    {
        try {
            $notification = Notification::create([
                'user_id' => $userId,
                'title' => $title,
                'message' => $message,
                'is_read' => $isRead
            ]);

            return $notification;
        } catch (\Exception $e) {
            Log::error('Failed to create notification: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Create a notification for a specific customer
     */
    public static function createCustomerNotification($customerId, $prescriptionId, $title, $message, $type = 'order_update', $data = null, $isRead = false)
    {
        try {
            $notification = CustomerNotification::create([
                'customer_id' => $customerId,
                'prescription_id' => $prescriptionId,
                'title' => $title,
                'message' => $message,
                'type' => $type,
                'data' => $data,
                'is_read' => $isRead
            ]);

            return $notification;
        } catch (\Exception $e) {
            Log::error('Failed to create customer notification: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Create notifications for all admin users
     */
    public static function createNotificationForAllAdmins($title, $message, $isRead = false)
    {
        try {
            $adminUserIds = [1];
            $notifications = [];
            foreach ($adminUserIds as $userId) {
                $notification = self::createNotification($userId, $title, $message, $isRead);
                if ($notification) {
                    $notifications[] = $notification;
                }
            }

            return $notifications;
        } catch (\Exception $e) {
            Log::error('Failed to create notifications for admins: ' . $e->getMessage());
            return [];
        }
    }

    // ==================== CUSTOMER NOTIFICATION METHODS ====================

    /**
     * Notify customer when their prescription is received
     */
    public static function notifyCustomerOrderReceived($prescription)
    {
        try {
            if (!$prescription->customer_id) {
                return null;
            }

            $message = "Your order has been received and is being reviewed by our pharmacists. You will receive updates on the status of your order.";

            return self::createCustomerNotification(
                $prescription->customer_id,
                $prescription->id,
                'Order Received',
                $message,
                'order_received',
                [
                    'prescription_id' => $prescription->id,
                    'status' => $prescription->status,
                    'mobile_number' => $prescription->mobile_number
                ]
            );
        } catch (\Exception $e) {
            Log::error('Error creating customer order received notification: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Notify customer when their prescription is approved
     */
    public static function notifyCustomerOrderApproved($prescription)
    {
        try {
            if (!$prescription->customer_id) {
                return null;
            }

            $message = "Good news! Your prescription order #P{$prescription->id} has been approved by our pharmacist. ";

            if ($prescription->admin_message) {
                $message .= "Message from pharmacist: " . $prescription->admin_message;
            } else {
                $message .= "Your medications are being prepared for pickup or delivery.";
            }

            return self::createCustomerNotification(
                $prescription->customer_id,
                $prescription->id,
                'Order Approved ✅',
                $message,
                'order_approved',
                [
                    'prescription_id' => $prescription->id,
                    'status' => $prescription->status,
                    'admin_message' => $prescription->admin_message
                ]
            );
        } catch (\Exception $e) {
            Log::error('Error creating customer order approved notification: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Notify customer when their prescription is partially approved
     */
    public static function notifyCustomerOrderPartiallyApproved($prescription)
    {
        try {
            if (!$prescription->customer_id) {
                return null;
            }

            $message = "Your prescription order #P{$prescription->id} has been partially approved. Some medications are ready while others require additional review. ";

            if ($prescription->admin_message) {
                $message .= "Message from pharmacist: " . $prescription->admin_message;
            }

            return self::createCustomerNotification(
                $prescription->customer_id,
                $prescription->id,
                'Order Partially Approved ⚠️',
                $message,
                'order_partially_approved',
                [
                    'prescription_id' => $prescription->id,
                    'status' => $prescription->status,
                    'admin_message' => $prescription->admin_message
                ]
            );
        } catch (\Exception $e) {
            Log::error('Error creating customer partial approval notification: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Notify customer when their order is ready/completed
     */
    public static function notifyCustomerOrderReady($prescription, $sale = null)
    {
        try {
            if (!$prescription->customer_id) {
                return null;
            }

            $message = "Your prescription order #P{$prescription->id} is ready for pickup! ";

            if ($sale) {
                $message .= "Total amount: ₱" . number_format($sale->total_amount, 2) . ". ";
                $message .= "Payment method: " . ucfirst($sale->payment_method) . ". ";
            }

            $message .= "Please bring a valid ID when picking up your medications.";

            return self::createCustomerNotification(
                $prescription->customer_id,
                $prescription->id,
                'Order Ready for Pickup 🎉',
                $message,
                'order_ready',
                [
                    'prescription_id' => $prescription->id,
                    'status' => $prescription->status,
                    'sale_id' => $sale ? $sale->id : null,
                    'total_amount' => $sale ? $sale->total_amount : null,
                    'payment_method' => $sale ? $sale->payment_method : null
                ]
            );
        } catch (\Exception $e) {
            Log::error('Error creating customer order ready notification: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Notify customer when their order is cancelled
     */
    public static function notifyCustomerOrderCancelled($prescription)
    {
        try {
            if (!$prescription->customer_id) {
                return null;
            }

            $message = "Unfortunately, your prescription order #P{$prescription->id} has been cancelled. ";

            if ($prescription->admin_message) {
                $message .= "Reason: " . $prescription->admin_message . " ";
            }

            $message .= "Please contact us if you have any questions or would like to submit a new prescription.";

            return self::createCustomerNotification(
                $prescription->customer_id,
                $prescription->id,
                'Order Cancelled ❌',
                $message,
                'order_cancelled',
                [
                    'prescription_id' => $prescription->id,
                    'status' => $prescription->status,
                    'admin_message' => $prescription->admin_message
                ]
            );
        } catch (\Exception $e) {
            Log::error('Error creating customer order cancelled notification: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Create notification for new order received (ADMIN)
     */
    public static function notifyNewOrder($prescription)
    {
        try {
            $customerInfo = $prescription->customer
                ? $prescription->customer->email_address
                : ($prescription->mobile_number ?? 'Unknown customer');

            self::createNotificationForAllAdmins(
                'New Order Received',
                "New order received from {$customerInfo}. Status: {$prescription->status}."
            );

            // Also notify the customer that their order was received
            self::notifyCustomerOrderReceived($prescription);
        } catch (\Exception $e) {
            Log::error('Error creating new order notification: ' . $e->getMessage());
        }
    }

    /**
     * Create notification for order approval (ADMIN)
     */
    public static function notifyOrderApproved($prescription)
    {
        try {
            $customerInfo = $prescription->customer
                ? $prescription->customer->email_address
                : ($prescription->mobile_number ?? 'Unknown customer');

            self::createNotificationForAllAdmins(
                'Order Approved',
                "Prescription order #P{$prescription->id} for {$customerInfo} has been approved and is ready for processing."
            );

            // Also notify the customer that their order was approved
            self::notifyCustomerOrderApproved($prescription);
        } catch (\Exception $e) {
            Log::error('Error creating order approved notification: ' . $e->getMessage());
        }
    }

    /**
     * Create notification for completed sale (ADMIN)
     */
    public static function notifyOrderCompleted($sale)
    {
        try {
            $customerInfo = $sale->customer
                ? $sale->customer->email_address
                : 'Walk-in customer';

            self::createNotificationForAllAdmins(
                'Order Completed',
                "Sale #{$sale->id} completed for {$customerInfo}. Total amount: ₱" . number_format($sale->total_amount, 2) . ". Payment method: {$sale->payment_method}."
            );

            // Also notify the customer that their order is ready
            if ($sale->prescription_id) {
                $prescription = Prescription::find($sale->prescription_id);
                if ($prescription) {
                    self::notifyCustomerOrderReady($prescription, $sale);
                }
            }
        } catch (\Exception $e) {
            Log::error('Error creating order completed notification: ' . $e->getMessage());
        }
    }

    /**
     * Create notification for high-value sales
     */
    public static function notifyHighValueSale($sale, $threshold = 5000)
    {
        try {
            if ($sale->total_amount >= $threshold) {
                $customerInfo = $sale->customer
                    ? $sale->customer->full_name
                    : 'Walk-in customer';

                self::createNotificationForAllAdmins(
                    'High Value Sale',
                    "High-value sale #{$sale->id} completed! Customer: {$customerInfo}, Amount: ₱" . number_format($sale->total_amount, 2) . "."
                );
            }
        } catch (\Exception $e) {
            Log::error('Error creating high value sale notification: ' . $e->getMessage());
        }
    }

    public static function notifyDailySalesReport()
    {
        try {
            $todaySales = Sale::whereDate('sale_date', today())
                ->where('status', 'completed')
                ->sum('total_amount');

            $todayOrderCount = Sale::whereDate('sale_date', today())
                ->where('status', 'completed')
                ->count();

            if ($todayOrderCount > 0) {
                self::createNotificationForAllAdmins(
                    'Daily Sales Report',
                    "Today's sales summary: {$todayOrderCount} orders completed, Total revenue: ₱" . number_format($todaySales, 2) . "."
                );
            }
        } catch (\Exception $e) {
            Log::error('Error creating daily sales report notification: ' . $e->getMessage());
        }
    }

    /**
     * Clean up old notifications (older than 30 days)
     */
    public static function cleanupOldNotifications($daysOld = 30)
    {
        try {
            $deleted = Notification::where('created_at', '<', now()->subDays($daysOld))->delete();
            $customerDeleted = CustomerNotification::where('created_at', '<', now()->subDays($daysOld))->delete();

            $total = $deleted + $customerDeleted;
            return $total;
        } catch (\Exception $e) {
            Log::error('Error cleaning up old notifications: ' . $e->getMessage());
            return 0;
        }
    }
}
