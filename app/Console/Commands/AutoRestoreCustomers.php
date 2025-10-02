<?php

namespace App\Console\Commands;

use App\Models\Customer;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class AutoRestoreCustomers extends Command
{
    protected $signature = 'customers:auto-restore';
    protected $description = 'Automatically restore restricted and deactivated customers after their time period expires';

    public function handle()
    {
        $this->info('Checking for customers to auto-restore...');

        $now = Carbon::now();

        // Find customers whose auto_restore_at has passed
        $customersToRestore = Customer::whereIn('status', ['restricted', 'deactivated'])
            ->whereNotNull('auto_restore_at')
            ->where('auto_restore_at', '<=', $now)
            ->get();

        if ($customersToRestore->isEmpty()) {
            $this->info('No customers to restore at this time.');
            return 0;
        }

        $restored = 0;
        foreach ($customersToRestore as $customer) {
            $oldStatus = $customer->status;

            $customer->status = 'active';
            $customer->status_changed_at = $now;
            $customer->auto_restore_at = null;
            $customer->save();

            $restored++;

            $this->info("Restored customer #{$customer->id} from {$oldStatus} to active");

            Log::info('Customer auto-restored to active', [
                'customer_id' => $customer->id,
                'customer_email' => $customer->email_address,
                'old_status' => $oldStatus,
                'restored_at' => $now->toDateTimeString()
            ]);
        }

        $this->info("Successfully restored {$restored} customer(s).");
        return 0;
    }
}
