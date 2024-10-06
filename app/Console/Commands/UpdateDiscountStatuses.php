<?php

namespace App\Console\Commands;

use App\Models\Discount;
use Carbon\Carbon;
use Illuminate\Console\Command;

class UpdateDiscountStatuses extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-discount-statuses';

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
        Discount::where('status', 'active')
            ->where('expired_date', '<', Carbon::now()->toDateString())
            ->update(['status' => 'inactive']);

        $this->info('Discount statuses updated successfully.');
    }
}
