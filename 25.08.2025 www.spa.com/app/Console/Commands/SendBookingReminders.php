<?php

namespace App\Console\Commands;

use App\Domain\Booking\Services\BookingService;
use Illuminate\Console\Command;

class SendBookingReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'booking:send-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send reminders for upcoming bookings';

    /**
     * Execute the console command.
     */
    public function handle(BookingService $bookingService): int
    {
        $this->info('Sending booking reminders...');
        
        try {
            $sentCount = $bookingService->sendUpcomingBookingReminders();
            
            $this->info("Successfully sent {$sentCount} booking reminders.");
            
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Failed to send booking reminders: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}