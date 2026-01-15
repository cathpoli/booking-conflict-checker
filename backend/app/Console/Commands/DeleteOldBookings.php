<?php

namespace App\Console\Commands;

use App\Models\Booking;
use Carbon\Carbon;
use Illuminate\Console\Command;

class DeleteOldBookings extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bookings:delete-old';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete bookings older than 30 days';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $cutoffDate = Carbon::now()->subDays(30)->format('Y-m-d');
        
        $deletedCount = Booking::where('date', '<', $cutoffDate)->delete();
        
        $this->info("Deleted {$deletedCount} booking(s) older than 30 days.");
        
        return Command::SUCCESS;
    }
}
