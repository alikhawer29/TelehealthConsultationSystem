<?php

namespace App\Console\Commands;

use App\Models\Appointment;
use Illuminate\Support\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;


class UpdateAppointmentStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'appointment:update-status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     *
     * @return int
     */

    public function handle()
    {
        $now = Carbon::now();

        // ✅ 1. Mark "inprogress" appointments
        $inProgressAppointments = Appointment::whereIn('appointment_status', ['upcoming', 'ontheway'])
            ->where('status', 'scheduled')
            ->where('service_type', 'doctor')
            ->whereDate('appointment_date', $now->toDateString())
            ->whereTime('appointment_start_time', '<=', $now->toTimeString())
            ->whereTime('appointment_end_time', '>=', $now->toTimeString())
            ->get();

        foreach ($inProgressAppointments as $appointment) {
            $appointment->update(['appointment_status' => 'inprogress']);
            // Log::info("🟡 Appointment ID {$appointment->id} marked as INPROGRESS at {$now->toDateTimeString()}");
        }

        if ($inProgressAppointments->count()) {
            // Log::info('⏰ Total Inprogress Appointments Updated: ' . $inProgressAppointments->count());
        }

        // ✅ 2. Mark "completed" appointments
        $completedAppointments = Appointment::where('appointment_status', 'inprogress')
            ->where('status', 'scheduled')
            ->where('service_type', 'doctor')
            ->where(function ($query) use ($now) {
                $query->whereDate('appointment_date', '<', $now->toDateString())
                    ->orWhere(function ($q) use ($now) {
                        $q->whereDate('appointment_date', $now->toDateString())
                            ->whereTime('appointment_end_time', '<', $now->toTimeString());
                    });
            })
            ->get();

        foreach ($completedAppointments as $appointment) {
            $appointment->update(['appointment_status' => 'completed', 'is_live' => 0]);
            // Log::info("✅ Appointment ID {$appointment->id} marked as COMPLETED at {$now->toDateTimeString()}");
        }

        if ($completedAppointments->count()) {
            // Log::info('⏰ Total Completed Appointments Updated: ' . $completedAppointments->count());
        }

        $this->info('Appointment statuses updated successfully.');
    }
}
