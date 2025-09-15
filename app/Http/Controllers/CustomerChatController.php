<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\CustomerChat;
use App\Models\Customer;
use App\Models\ChatMessage;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class CustomerChatController extends Controller
{
    public function index(): View
    {
        return view('chat.chat');
    }

    public function test(): JsonResponse
    {
        try {
            // Test basic Customer model access
            $customerCount = Customer::count();
            $customers = Customer::select('customer_id', 'full_name', 'email_address', 'status', 'deleted_at')
                ->limit(5)
                ->get();

            // Test CustomerChat model access
            $chatCount = CustomerChat::count();
            $chatRecords = CustomerChat::select('customer_id', 'full_name', 'email_address', 'chat_status', 'is_online')
                ->limit(5)
                ->get();

            // Test the join query
            $joinQuery = Customer::leftJoin('customer_chats', 'customers.customer_id', '=', 'customer_chats.customer_id')
                ->select([
                    'customers.customer_id',
                    'customers.full_name',
                    'customers.email_address',
                    'customers.status as customer_status',
                    'customer_chats.chat_status',
                    'customer_chats.is_online'
                ])
                ->whereNull('customers.deleted_at')
                ->limit(5)
                ->get();

            return response()->json([
                'message' => 'Test endpoint working',
                'customers_table' => [
                    'count' => $customerCount,
                    'sample' => $customers
                ],
                'customer_chats_table' => [
                    'count' => $chatCount,
                    'sample' => $chatRecords
                ],
                'join_query' => [
                    'count' => $joinQuery->count(),
                    'sample' => $joinQuery
                ],
                'success' => true
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'success' => false
            ], 500);
        }
    }

   public function getCustomers(Request $request): JsonResponse
{
    try {
        // Debug: Let's first check if we can get customers at all
        $totalCustomers = Customer::count();

        // Query customers from the main Customer model and left join with customers_chat
        $query = Customer::leftJoin('customers_chat', 'customers.customer_id', '=', 'customers_chat.customer_id')
            ->select([
                'customers.customer_id',
                'customers.full_name',
                'customers.email_address',
                'customers.status as customer_status',
                'customers_chat.chat_status',
                'customers_chat.is_online',
                'customers_chat.last_active',
                'customers.created_at',
                'customers.updated_at'
            ])
            ->whereNull('customers.deleted_at'); // Exclude soft deleted customers

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('customers.full_name', 'LIKE', "%{$search}%")
                  ->orWhere('customers.email_address', 'LIKE', "%{$search}%")
                  ->orWhere('customers.customer_id', 'LIKE', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            if ($request->status === 'offline') {
                // Show customers without chat records or with offline status
                $query->where(function ($q) {
                    $q->whereNull('customers_chat.chat_status')
                      ->orWhere('customers_chat.chat_status', 'offline');
                });
            } else {
                $query->where('customers_chat.chat_status', $request->status);
            }
        }

        $customers = $query->orderByRaw("CASE WHEN customers_chat.is_online = 1 THEN 0 ELSE 1 END")
            ->orderBy('customers_chat.last_active', 'desc')
            ->orderBy('customers.full_name', 'asc')
            ->get();

        $transformedCustomers = $customers->map(function ($customer) {
            return [
                'customer_id' => $customer->customer_id,
                'full_name' => $customer->full_name,
                'email_address' => $customer->email_address,
                'chat_status' => $customer->chat_status ?? 'offline',
                'is_online' => $customer->is_online ? (bool) $customer->is_online : false,
                'last_active' => $customer->last_active,
                'created_at' => $customer->created_at,
                'updated_at' => $customer->updated_at,
            ];
        });

        return response()->json([
            'customers' => $transformedCustomers,
            'success' => true,
            'count' => $transformedCustomers->count(),
            'total_in_db' => $totalCustomers, // Debug info
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'error' => 'Failed to fetch customers',
            'message' => $e->getMessage(),
            'success' => false
        ], 500);
    }
}

   public function getChatStats(): JsonResponse
{
    try {
        $totalCustomers = 0;
        $onlineCustomers = 0;
        $activeChats = 0;

        try {
            $totalCustomers = Customer::count();
        } catch (\Exception $e) {
            // Log error but don't fail completely
        }

        try {
            $onlineCustomers = CustomerChat::where('is_online', true)->count();
        } catch (\Exception $e) {
            // customers_chat table might not exist or have different structure
        }

        try {
            // Check if Conversation model/table exists
            if (class_exists('App\Models\Conversation')) {
                $activeChats = \App\Models\Conversation::where('status', 'active')->count();
            }
        } catch (\Exception $e) {
            // Conversation table might not exist
        }

        $stats = [
            'online' => $onlineCustomers,
            'total' => $totalCustomers,
            'active_chats' => $activeChats,
            'offline' => max(0, $totalCustomers - $onlineCustomers),
        ];

        return response()->json([
            'stats' => $stats,
            'success' => true
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'error' => 'Failed to fetch stats',
            'message' => $e->getMessage(),
            'success' => false
        ], 500);
    }
}
    public function updateOnlineStatus(Request $request, $customerId): JsonResponse
    {
        try {
            // First, try to find existing CustomerChat record
            $customerChat = CustomerChat::where('customer_id', $customerId)->first();

            // If no CustomerChat record exists, create one
            if (!$customerChat) {
                // Verify the customer exists in the main customers table
                $customer = Customer::where('customer_id', $customerId)->first();
                if (!$customer) {
                    return response()->json([
                        'error' => 'Customer not found',
                        'success' => false
                    ], 404);
                }

                // Create new CustomerChat record
                $customerChat = CustomerChat::create([
                    'customer_id' => $customerId,
                    'full_name' => $customer->full_name,
                    'email_address' => $customer->email_address,
                    'is_online' => $request->boolean('is_online', true),
                    'last_active' => now(),
                    'chat_status' => $request->boolean('is_online', true) ? 'available' : 'offline'
                ]);
            } else {
                // Update existing record using the model's method
                $customerChat->updateOnlineStatus($request->boolean('is_online', true));
            }

            return response()->json([
                'message' => 'Online status updated successfully',
                'customer' => $customerChat->fresh(),
                'success' => true
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to update status',
                'message' => $e->getMessage(),
                'success' => false
            ], 500);
        }
    }
}
