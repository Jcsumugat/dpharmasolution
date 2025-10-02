<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class AdminCustomerController extends Controller
{
    public function index()
    {
        // Auto-restore customers whose timer has expired
        $this->autoRestoreCustomers();

        $customers = Customer::whereNot('status', 'deleted')->orderBy('created_at', 'desc')->get();
        return view('customer.customer', compact('customers'));
    }

    /**
     * Automatically restore customers whose restriction/deactivation period has expired
     */
    private function autoRestoreCustomers()
    {
        $now = Carbon::now();

        // Find customers whose auto_restore_at has passed
        $customersToRestore = Customer::whereIn('status', ['restricted', 'deactivated'])
            ->whereNotNull('auto_restore_at')
            ->where('auto_restore_at', '<=', $now)
            ->get();

        foreach ($customersToRestore as $customer) {
            $oldStatus = $customer->status;
            $customer->status = 'active';
            $customer->status_changed_at = $now;
            $customer->auto_restore_at = null;
            $customer->save();

            Log::info('Customer auto-restored to active', [
                'customer_id' => $customer->id,
                'old_status' => $oldStatus,
                'restored_at' => $now
            ]);
        }
    }

    public function restrict(Request $request, Customer $customer)
    {
        Log::info('AdminCustomerController::restrict called', [
            'customer_id' => $customer->id,
            'request_method' => $request->method(),
            'authenticated' => Auth::check(),
            'user_id' => Auth::id(),
        ]);

        try {
            $now = Carbon::now();

            if ($customer->status === 'restricted') {
                // Manual unrestrict - set to active immediately
                $newStatus = 'active';
                $autoRestoreAt = null;
            } else {
                // Restrict for 3 days
                $newStatus = 'restricted';
                $autoRestoreAt = $now->copy()->addDays(3);
            }

            Log::info('About to update customer status', [
                'customer_id' => $customer->id,
                'old_status' => $customer->status,
                'new_status' => $newStatus,
                'auto_restore_at' => $autoRestoreAt
            ]);

            $customer->status = $newStatus;
            $customer->status_changed_at = $now;
            $customer->auto_restore_at = $autoRestoreAt;
            $customer->save();

            Log::info('Customer restriction status changed successfully', [
                'customer_id' => $customer->id,
                'old_status' => $customer->getOriginal('status'),
                'new_status' => $newStatus,
                'auto_restore_at' => $autoRestoreAt,
                'admin' => Auth::user()?->name ?? 'Unknown'
            ]);

            $message = $newStatus === 'restricted'
                ? 'Customer has been restricted (will auto-restore in 3 days)'
                : 'Customer restriction has been removed';

            return response()->json([
                'success' => true,
                'message' => $message,
                'status' => $newStatus,
                'auto_restore_at' => $autoRestoreAt ? $autoRestoreAt->toIso8601String() : null
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to change customer restriction status', [
                'customer_id' => $customer->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
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
            $now = Carbon::now();

            if ($customer->status === 'active') {
                // Deactivate for 7 days
                $newStatus = 'deactivated';
                $autoRestoreAt = $now->copy()->addDays(7);
            } else {
                // Manual activation - restore immediately
                $newStatus = 'active';
                $autoRestoreAt = null;
            }

            $customer->status = $newStatus;
            $customer->status_changed_at = $now;
            $customer->auto_restore_at = $autoRestoreAt;
            $customer->save();

            Log::info('Customer activation status changed', [
                'customer_id' => $customer->id,
                'old_status' => $customer->getOriginal('status'),
                'new_status' => $newStatus,
                'auto_restore_at' => $autoRestoreAt,
                'admin' => Auth::user()?->name ?? 'Unknown'
            ]);

            $message = $newStatus === 'active'
                ? 'Customer has been activated'
                : 'Customer has been deactivated (will auto-restore in 7 days)';

            return response()->json([
                'success' => true,
                'message' => $message,
                'status' => $newStatus,
                'auto_restore_at' => $autoRestoreAt ? $autoRestoreAt->toIso8601String() : null
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
            $now = Carbon::now();

            // Set status to deleted - no auto-restore
            $customer->status = 'deleted';
            $customer->status_changed_at = $now;
            $customer->auto_restore_at = null; // Deleted items don't auto-restore
            $customer->save();

            Log::info('Customer marked as deleted', [
                'customer_id' => $customerId,
                'customer_name' => $customerName,
                'admin' => Auth::user()?->name ?? 'Unknown'
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Customer has been deleted successfully (manual restore only)'
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

            $now = Carbon::now();
            $customer->status = 'active';
            $customer->status_changed_at = $now;
            $customer->auto_restore_at = null;
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
        // Auto-restore before getting stats
        $this->autoRestoreCustomers();

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
