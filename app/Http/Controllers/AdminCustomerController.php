<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class AdminCustomerController extends Controller
{
    public function index()
    {
        $customers = Customer::whereNot('status', 'deleted')->orderBy('created_at', 'desc')->get();
        return view('customer.customer', compact('customers'));
    }

    public function restrict(Request $request, Customer $customer)
{
    // Add extensive debugging
    Log::info('AdminCustomerController::restrict called', [
        'customer_id' => $customer->id,
        'request_method' => $request->method(),
        'request_url' => $request->fullUrl(),
        'authenticated' => Auth::check(),
        'user_id' => Auth::id(),
        'user_email' => Auth::check() ? Auth::user()->email : null,
        'user_role' => Auth::check() ? Auth::user()->role : null,
    ]);

    try {
        $newStatus = $customer->status === 'restricted' ? 'active' : 'restricted';
        
        Log::info('About to update customer status', [
            'customer_id' => $customer->id,
            'old_status' => $customer->status,
            'new_status' => $newStatus
        ]);
        
        $customer->status = $newStatus;
        $customer->save();

        Log::info('Customer restriction status changed successfully', [
            'customer_id' => $customer->id,
            'old_status' => $customer->getOriginal('status'),
            'new_status' => $newStatus,
            'admin' => Auth::user()?->name ?? 'Unknown'
        ]);

        return response()->json([
            'success' => true,
            'message' => $newStatus === 'restricted' 
                ? 'Customer has been restricted' 
                : 'Customer restriction has been removed',
            'status' => $newStatus
        ]);
    } catch (\Exception $e) {
        Log::error('Failed to change customer restriction status', [
            'customer_id' => $customer->id,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Failed to change restriction status: ' . $e->getMessage()
        ], 500);
    }
}

    public function toggleActivation(Request $request, Customer $customer)
    {
        try {
            $newStatus = $customer->status === 'active' ? 'deactivated' : 'active';
            $customer->status = $newStatus;
            $customer->save();

            Log::info('Customer activation status changed', [
                'customer_id' => $customer->id,
                'old_status' => $customer->getOriginal('status'),
                'new_status' => $newStatus,
                'admin' => Auth::user()?->name ?? 'Unknown'
            ]);

            return response()->json([
                'success' => true,
                'message' => $newStatus === 'active' 
                    ? 'Customer has been activated' 
                    : 'Customer has been deactivated',
                'status' => $newStatus
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to change customer activation status', [
                'customer_id' => $customer->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to change activation status'
            ], 500);
        }
    }

    public function destroy(Customer $customer)
    {
        try {
            $customerName = $customer->getAttribute('full_name');
            $customerId = $customer->id;
            
            // Set status to deleted instead of soft delete
            $customer->status = 'deleted';
            $customer->save();

            Log::info('Customer marked as deleted', [
                'customer_id' => $customerId,
                'customer_name' => $customerName,
                'admin' => Auth::user()?->name ?? 'Unknown'
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Customer has been deleted successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to delete customer', [
                'customer_id' => $customer->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete customer'
            ], 500);
        }
    }

    public function show(Customer $customer)
    {
        return response()->json([
            'success' => true,
            'customer' => $customer
        ]);
    }

    public function search(Request $request)
    {
        $request->validate([
            'query' => 'required|string|min:1'
        ]);

        $query = $request->get('query');
        
        $customers = Customer::where('status', '!=', 'deleted')
            ->where(function($q) use ($query) {
                $q->where('full_name', 'like', "%{$query}%")
                  ->orWhere('email_address', 'like', "%{$query}%")
                  ->orWhere('contact_number', 'like', "%{$query}%");
            })
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'customers' => $customers,
            'count' => $customers->count()
        ]);
    }

    public function restore($id)
    {
        try {
            $customer = Customer::findOrFail($id);
            
            if ($customer->status !== 'deleted') {
                return response()->json([
                    'success' => false,
                    'message' => 'Customer is not deleted'
                ], 400);
            }
            
            $customer->status = 'active';
            $customer->save();

            Log::info('Customer restored', [
                'customer_id' => $id,
                'customer_name' => $customer->full_name,
                'admin' => Auth::user()?->name ?? 'Unknown'
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Customer has been restored successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to restore customer', [
                'customer_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to restore customer'
            ], 500);
        }
    }

    public function deleted()
    {
        $customers = Customer::where('status', 'deleted')->orderBy('updated_at', 'desc')->get();
        return view('customer.deleted', compact('customers'));
    }

    public function forceDelete($id)
    {
        try {
            $customer = Customer::findOrFail($id);
            
            if ($customer->status !== 'deleted') {
                return response()->json([
                    'success' => false,
                    'message' => 'Customer must be marked as deleted first'
                ], 400);
            }
            
            $customerName = $customer->full_name;
            $customer->forceDelete();

            Log::warning('Customer permanently deleted', [
                'customer_id' => $id,
                'customer_name' => $customerName,
                'admin' => Auth::user()?->name ?? 'Unknown'
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Customer has been permanently deleted'
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to permanently delete customer', [
                'customer_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to permanently delete customer'
            ], 500);
        }
    }

    public function getStats()
    {
        $stats = [
            'total' => Customer::whereNot('status', 'deleted')->count(),
            'active' => Customer::where('status', 'active')->count(),
            'restricted' => Customer::where('status', 'restricted')->count(),
            'deactivated' => Customer::where('status', 'deactivated')->count(),
            'deleted' => Customer::where('status', 'deleted')->count()
        ];

        return response()->json([
            'success' => true,
            'stats' => $stats
        ]);
    }
}