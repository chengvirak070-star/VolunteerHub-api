<?php

namespace App\Http\Controllers;

use App\Enums\HourLogStatus;
use App\Http\Resources\HourLogResource;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class HoursController extends Controller
{
    /** GET /me/hours — all my hour logs + totals in meta. */
    public function mine(Request $request): AnonymousResourceCollection
    {
        $user = $request->user();

        $logs = $user->hourLogs()
            ->with('event.organization')
            ->latest()
            ->paginate(50);

        return HourLogResource::collection($logs)->additional([
            'meta' => [
                'approved_hours' => (float) $user->hourLogs()->where('status', HourLogStatus::Approved)->sum('hours'),
                'pending_hours' => (float) $user->hourLogs()->where('status', HourLogStatus::Pending)->sum('hours'),
                'events_attended' => $user->hourLogs()->where('status', HourLogStatus::Approved)->count(),
            ],
        ]);
    }

    /** GET /users/{username}/hours — approved hours only (you see all of your own). */
    public function forUser(Request $request, string $username): AnonymousResourceCollection
    {
        $user = User::active()->where('username', $username)->firstOrFail();
        $isMe = $request->user()?->id === $user->id;

        $logs = $user->hourLogs()
            ->when(! $isMe, fn ($q) => $q->where('status', HourLogStatus::Approved))
            ->with('event.organization')
            ->latest()
            ->paginate(15);

        return HourLogResource::collection($logs);
    }

    /** GET /me/certificate — PDF listing approved volunteer hours. */
    public function certificate(Request $request): Response
    {
        $user = $request->user();
        $logs = $user->hourLogs()
            ->where('status', HourLogStatus::Approved)
            ->with('event.organization')
            ->get()
            ->sortBy(fn ($log) => $log->event?->starts_at)
            ->values();

        $e = fn (?string $s) => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
        $fmt = fn ($n) => rtrim(rtrim(number_format((float) $n, 2), '0'), '.');
        $total = $fmt($logs->sum('hours'));

        $rows = $logs->map(fn ($log) => '<tr>'
            .'<td>'.$e($log->event?->title).'</td>'
            .'<td>'.$e($log->event?->organization?->name).'</td>'
            .'<td>'.$e($log->event?->starts_at?->format('j M Y')).'</td>'
            .'<td class="r">'.$fmt($log->hours).'</td>'
            .'</tr>')->implode('');

        if ($rows === '') {
            $rows = '<tr><td colspan="4" class="c">No approved hours yet.</td></tr>';
        }

        // Built as an HTML string on purpose: this project is API-only and has no Blade views.
        $html = <<<HTML
        <html><head><meta charset="utf-8"><style>
            body { font-family: DejaVu Sans, sans-serif; color: #13263a; font-size: 12px; margin: 30px; }
            .brand { font-size: 22px; font-weight: bold; } .brand span { color: #14967f; }
            h1 { font-size: 26px; margin: 26px 0 4px; } .muted { color: #6b7a86; }
            .name { font-size: 20px; font-weight: bold; margin: 18px 0 2px; }
            .total { margin: 18px 0; padding: 14px 18px; background: #d6f0ea; border-radius: 8px; font-size: 16px; }
            .total b { font-size: 26px; color: #0f7a65; }
            table { width: 100%; border-collapse: collapse; margin-top: 8px; }
            th { text-align: left; background: #13263a; color: #fff; padding: 8px; font-size: 11px; }
            td { padding: 8px; border-bottom: 1px solid #e3e8e6; } .r { text-align: right; } .c { text-align: center; color: #6b7a86; }
            .foot { margin-top: 28px; font-size: 10px; color: #6b7a86; }
        </style></head><body>
            <div class="brand">Volunteer<span>Hub</span></div>
            <h1>Certificate of Volunteer Hours</h1>
            <div class="muted">This certifies that</div>
            <div class="name">{$e($user->name)}</div>
            <div class="muted">@{$e($user->username)}</div>
            <div class="total">has completed <b>{$total}</b> approved volunteer hours across {$logs->count()} event(s).</div>
            <table>
                <thead><tr><th>Event</th><th>Organizer</th><th>Date</th><th class="r">Hours</th></tr></thead>
                <tbody>{$rows}</tbody>
            </table>
            <div class="foot">Issued on {$e(now()->format('j F Y'))} by VolunteerHub. Hours are confirmed by event organizers and approved by a VolunteerHub admin.</div>
        </body></html>
        HTML;

        return Pdf::loadHTML($html)->setPaper('a4')->download("volunteerhub-certificate-{$user->username}.pdf");
    }
}
