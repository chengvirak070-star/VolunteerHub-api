<?php

namespace App\Http\Controllers\Admin;

use App\Enums\EventStatus;
use App\Enums\HourLogStatus;
use App\Enums\OrganizationStatus;
use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\HourLog;
use App\Models\Organization;
use App\Notifications\HoursReviewed;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class ApprovalController extends Controller
{
    public const TYPES = ['organization', 'event', 'hour_log'];

    /** GET /admin/approvals?type= — pending organizations, events and hour logs in one list. */
    public function index(Request $request): JsonResponse
    {
        $type = $request->input('type');
        $items = self::pending($type && in_array($type, self::TYPES, true) ? $type : null);

        return response()->json([
            'data' => $items->values(),
            'meta' => [
                'total' => $items->count(),
                'counts' => self::counts(),
            ],
        ]);
    }

    /** POST /approvals/{type}/{id}/approve */
    public function approve(Request $request, string $type, int $id): JsonResponse
    {
        return $this->review($request, $type, $id, true);
    }

    /** POST /approvals/{type}/{id}/decline */
    public function decline(Request $request, string $type, int $id): JsonResponse
    {
        return $this->review($request, $type, $id, false);
    }

    private function review(Request $request, string $type, int $id, bool $approve): JsonResponse
    {
        abort_unless(in_array($type, self::TYPES, true), 404);

        switch ($type) {
            case 'organization':
                $org = Organization::findOrFail($id);
                abort_if($org->status !== OrganizationStatus::Pending, 422, 'This organization was already reviewed.');
                $org->update($approve
                    ? ['status' => OrganizationStatus::Approved, 'verified' => true]
                    : ['status' => OrganizationStatus::Rejected]);
                break;

            case 'event':
                $event = Event::findOrFail($id);
                abort_if($event->status !== EventStatus::Pending, 422, 'This event was already reviewed.');
                $event->update(['status' => $approve ? EventStatus::Open : EventStatus::Rejected]);
                if ($approve) {
                    $event->refreshCapacityStatus();
                }
                break;

            case 'hour_log':
                $log = HourLog::with('event')->findOrFail($id);
                abort_if($log->status !== HourLogStatus::Pending, 422, 'These hours were already reviewed.');
                $log->update([
                    'status' => $approve ? HourLogStatus::Approved : HourLogStatus::Declined,
                    'reviewed_by' => $request->user()->id,
                    'reviewed_at' => now(),
                ]);
                $log->user?->notify(new HoursReviewed($log));
                break;
        }

        return response()->json([
            'data' => ['type' => $type, 'id' => $id, 'status' => $approve ? 'approved' : 'declined'],
            'meta' => ['counts' => self::counts()],
        ]);
    }

    /** @return array{organization:int, event:int, hour_log:int, total:int} */
    public static function counts(): array
    {
        $c = [
            'organization' => Organization::where('status', OrganizationStatus::Pending)->count(),
            'event' => Event::where('status', EventStatus::Pending)->count(),
            'hour_log' => HourLog::where('status', HourLogStatus::Pending)->count(),
        ];

        return $c + ['total' => array_sum($c)];
    }

    /** Pending items as one list, newest first. Each has a "type" field. */
    public static function pending(?string $type = null, int $limit = 200): Collection
    {
        $items = collect();

        if (! $type || $type === 'organization') {
            Organization::with('user')->where('status', OrganizationStatus::Pending)->latest()->limit($limit)->get()
                ->each(fn ($o) => $items->push([
                    'type' => 'organization',
                    'id' => $o->id,
                    'title' => $o->name,
                    'subtitle' => 'Organizer: '.($o->user?->name ?? '—').' (@'.($o->user?->username ?? '—').') · '.($o->user?->email ?? ''),
                    'details' => $o->description,
                    'link' => $o->user ? '/u/'.$o->user->username : null,
                    'created_at' => $o->created_at?->toIso8601String(),
                ]));
        }

        if (! $type || $type === 'event') {
            Event::with('organization')->where('status', EventStatus::Pending)->latest()->limit($limit)->get()
                ->each(fn ($e) => $items->push([
                    'type' => 'event',
                    'id' => $e->id,
                    'title' => $e->title,
                    'subtitle' => ($e->organization?->name ?? '—').' · '.$e->starts_at?->format('D, j M Y g:i A').' · '.$e->location,
                    'details' => $e->description,
                    'link' => '/events/'.$e->id,
                    'created_at' => $e->created_at?->toIso8601String(),
                ]));
        }

        if (! $type || $type === 'hour_log') {
            HourLog::with(['user', 'event.organization'])->where('status', HourLogStatus::Pending)->latest()->limit($limit)->get()
                ->each(fn ($l) => $items->push([
                    'type' => 'hour_log',
                    'id' => $l->id,
                    'title' => ($l->user?->name ?? '—').' — '.rtrim(rtrim(number_format((float) $l->hours, 2), '0'), '.').' h',
                    'subtitle' => ($l->event?->title ?? '—').' · '.$l->event?->starts_at?->format('j M Y').' · confirmed by '.($l->event?->organization?->name ?? '—'),
                    'details' => null,
                    'link' => $l->user ? '/u/'.$l->user->username : null,
                    'created_at' => $l->created_at?->toIso8601String(),
                ]));
        }

        return $items->sortByDesc('created_at')->values();
    }
}
