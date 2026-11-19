<?php

namespace App\Http\Controllers\Admin;

use App\Enums\EventStatus;
use App\Enums\HourLogStatus;
use App\Enums\Role;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\EventResource;
use App\Models\Event;
use App\Models\HourLog;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * GET /dashboard
     * { stats: { totalVolunteers, activeEvents, hoursLogged, pendingApprovals }, upcomingEvents, approvals, hoursPerMonth }
     */
    public function __invoke(Request $request): JsonResponse
    {
        $counts = ApprovalController::counts();

        $stats = [
            'totalVolunteers' => User::where('role', Role::Volunteer)->where('status', UserStatus::Active)->count(),
            'totalOrganizers' => User::where('role', Role::Organizer)->count(),
            'activeEvents' => Event::whereIn('status', [EventStatus::Open, EventStatus::Full])->where('starts_at', '>=', now())->count(),
            'hoursLogged' => (float) HourLog::where('status', HourLogStatus::Approved)->sum('hours'),
            'pendingApprovals' => $counts['total'],
            'pendingByType' => $counts,
        ];

        $upcoming = Event::forViewer($request->user())
            ->whereIn('status', [EventStatus::Open, EventStatus::Full])
            ->where('starts_at', '>=', now())
            ->orderBy('starts_at')
            ->limit(5)
            ->get();

        return response()->json([
            'data' => [
                'stats' => $stats,
                'upcomingEvents' => EventResource::collection($upcoming),
                'approvals' => ApprovalController::pending(null, 5)->take(5)->values(),
                'hoursPerMonth' => $this->hoursPerMonth(),
            ],
        ]);
    }

    /** Approved hours for each of the last 6 months (by event date): [{ month: '2026-06', label: 'Jun', hours }] */
    private function hoursPerMonth(): array
    {
        $start = now()->startOfMonth()->subMonths(5);
        $months = [];
        for ($i = 0; $i < 6; $i++) {
            $m = $start->copy()->addMonths($i);
            $months[$m->format('Y-m')] = ['month' => $m->format('Y-m'), 'label' => $m->format('M'), 'hours' => 0.0];
        }

        HourLog::with('event:id,starts_at')
            ->where('status', HourLogStatus::Approved)
            ->whereHas('event', fn ($q) => $q->where('starts_at', '>=', $start))
            ->get(['id', 'event_id', 'hours'])
            ->each(function ($log) use (&$months) {
                $key = $log->event?->starts_at?->format('Y-m');
                if ($key && isset($months[$key])) {
                    $months[$key]['hours'] += (float) $log->hours;
                }
            });

        return array_values($months);
    }
}
