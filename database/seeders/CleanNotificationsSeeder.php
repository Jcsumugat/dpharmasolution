<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Notification;
use App\Services\NotificationService;

class CleanNotificationsSeeder extends Seeder
{
    public function run(): void
    {
        // Clear all existing notifications
        Notification::truncate();
        
        // Create sample pharmacy notifications
        $sampleNotifications = [
            [
                'user_id' => 1,
                'title' => 'Low Stock Alert',
                'message' => 'Paracetamol 500mg is running low. Current stock: 8 units.',
                'is_read' => false
            ],
            [
                'user_id' => 1,
                'title' => 'New Order Received', 
                'message' => 'New prescription order #P123 has been submitted and requires review.',
                'is_read' => false
            ],
            [
                'user_id' => 1,
                'title' => 'Out of Stock Alert',
                'message' => 'Amoxicillin 250mg is now out of stock. Immediate restocking required.',
                'is_read' => false
            ]
        ];

        foreach ($sampleNotifications as $notification) {
            Notification::create($notification);
        }

        // Generate automatic low stock notifications for existing products
        NotificationService::createLowStockNotifications();
    }
}