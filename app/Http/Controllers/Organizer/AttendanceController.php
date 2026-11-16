<?php

namespace App\Http\Controllers\Organizer;

use App\Enums\HourLogStatus;
use App\Enums\RegistrationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Organizer\AttendanceRequest;
use App\Http\Resources\RegistrationResource;
use App\Models\EventRegistration;
use App\Models\HourLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class AttendanceController extends Controller
{
    /**
     * POST /organizer/registrations/{registration}/attendance  { status: attended|absent }
     * "attended" creates a pending hour log with the event's hours (admin approves it later).
     */
    public function store(AttendanceRequest $request, EventRegistration $registration): RegistrationResource
    {
        $event = $registration->event;
        Gate::authorize('manage', $event);

        if ($registration->status === RegistrationStatus::Cancelled) {
            abort(422, 'This volunteer cancelled their registration.');
        }

        $status = RegistrationStatus::from($request->string('status'));

        DB::transaction(function () use ($registration, $event, $status) {
            $registration->update(['status' => $status]);
            $log = $registration->hourLog;

            if ($status === RegistrationStatus::Attended) {
                if (! $log) {
                    HourLog::create([
                        'event_registration_id' => $registration->id,
                        'user_id' => $registration->user_id,
                        'event_id' => $event->id,
                        'hours' => $event->hours,
                        'status' => HourLogStatus::Pending,
                    ]);
                }
            } elseif ($log && $log->status === HourLogStatus::Pending) {
                // Changed to absent before the admin reviewed: remove the pending hours.
                $log->delete();
            }
        });

        return new RegistrationResource($registration->fresh(['user.organization', 'hourLog']));
    }
}
