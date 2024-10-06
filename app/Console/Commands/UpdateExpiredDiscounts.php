<?php

namespace App\Console\Commands;

use App\Models\Discount;
use Illuminate\Console\Command;

class UpdateExpiredDiscounts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-expired-discounts';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        Discount::where('expired_date', '<=', now())
            ->where('status', 'active')
            ->update(['status' => 'inactive']);

        $this->info('Expired discounts have been updated to inactive status.');
    }
}
