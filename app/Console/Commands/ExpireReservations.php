<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Reservation;
use Carbon\Carbon;

class ExpireReservations extends Command
{
    protected $signature = 'reservations:expire';
    protected $description = '将当天已预约未签到的记录标记为已过期';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $today = Carbon::now('Asia/Shanghai')->toDateString();
        $reservations = Reservation::where('status', '已预约')
            ->whereDate('reservation_time', $today)
            ->get();

        foreach ($reservations as $reservation) {
            $reservation->status = '已过期';
            $reservation->save();
        }

        $this->info('当天已预约未签到的记录已标记为已过期');
    }
}