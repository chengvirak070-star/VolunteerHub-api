<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BugReportStatus;
use App\Enums\ContentStatus;
use App\Enums\ReportStatus;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ResolveReportRequest;
use App\Http\Resources\BugReportResource;
use App\Http\Resources\ReportResource;
use App\Models\BugReport;
use App\Models\Comment;
use App\Models\Post;
use App\Models\Report;
use App\Models\User;
use App\Notifications\BugReportFixed;
use App\Notifications\ReportReviewed;
use Illuminate\Support\Facades\Notification;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ModerationController extends Controller
{
    /** GET /admin/reports?status=open|resolved|dismissed */
    public function reports(Request $request): AnonymousResourceCollection
    {
        $reports = Report::with(['reporter.organization', 'reportable'])
            ->where('status', $request->input('status', 'open'))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        // Authors of reported posts/comments
        $reports->getCollection()->each(function ($r) {
            if ($r->reportable instanceof Post || $r->reportable instanceof Comment) {
                $r->reportable->loadMissing('user.organization');
            }
        });

        return ReportResource::collection($reports);
    }

    /**
     * POST /admin/reports/{report}/resolve { action: hide_content | block_user | dismiss }
     * hide_content: post/comment becomes hidden · block_user: the author (or reported user) is blocked.
     * Other open reports about the same item are closed too.
     */
    public function resolve(ResolveReportRequest $request, Report $report): ReportResource
    {
        $action = $request->validated('action');
        $target = $report->reportable;

        if ($action === 'hide_content') {
            abort_unless($target instanceof Post || $target instanceof Comment, 422, 'Only posts and comments can be hidden. Use "block user" for people.');
            $target->update(['status' => ContentStatus::Hidden]);
        }

        if ($action === 'block_user') {
            $user = $target instanceof User ? $target : $target?->user;
            abort_unless($user, 422, 'This content no longer exists.');
            abort_if($user->isAdmin(), 422, 'Admins cannot be blocked.');
            $user->update(['status' => UserStatus::Blocked]);
            $user->tokens()->delete();
        }

        $status = $action === 'dismiss' ? ReportStatus::Dismissed : ReportStatus::Resolved;

        $openReports = Report::with('reporter')
            ->where('reportable_type', $report->reportable_type)
            ->where('reportable_id', $report->reportable_id)
            ->where('status', ReportStatus::Open);

        // Everyone who reported this item hears back (once each), without naming anyone.
        $reporters = $openReports->get()->pluck('reporter')->filter()->unique('id');
        $openReports->update(['status' => $status]);

        $link = match (true) {
            $target instanceof Post => '/posts/'.$target->id,
            $target instanceof Comment => '/posts/'.$target->post_id,
            $target instanceof User => '/u/'.$target->username,
            default => null,
        };
        Notification::send($reporters, new ReportReviewed($report->reportable_type, $action, $link));

        return new ReportResource($report->fresh(['reporter.organization', 'reportable']));
    }

    /** GET /admin/bug-reports?status=open|fixed */
    public function bugs(Request $request): AnonymousResourceCollection
    {
        $bugs = BugReport::with('user.organization')
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return BugReportResource::collection($bugs);
    }

    /** POST /admin/bug-reports/{bugReport}/fixed */
    public function fixed(BugReport $bugReport): BugReportResource
    {
        $wasOpen = $bugReport->status !== BugReportStatus::Fixed;
        $bugReport->update(['status' => BugReportStatus::Fixed]);

        // Tell the person who reported it (only the first time it is marked fixed)
        if ($wasOpen && $bugReport->user) {
            $bugReport->user->notify(new BugReportFixed($bugReport));
        }

        return new BugReportResource($bugReport->load('user.organization'));
    }
}
