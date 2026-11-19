<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBugReportRequest;
use App\Http\Requests\StoreReportRequest;
use App\Models\BugReport;
use App\Models\Comment;
use App\Models\Post;
use App\Models\Report;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class ReportController extends Controller
{
    /** POST /reports { type: post|comment|user, id, reason } */
    public function store(StoreReportRequest $request): JsonResponse
    {
        $data = $request->validated();
        $model = match ($data['type']) {
            'post' => Post::class,
            'comment' => Comment::class,
            'user' => User::class,
        };
        $target = $model::findOrFail($data['id']);
        $user = $request->user();

        if ($target instanceof User ? $target->id === $user->id : $target->user_id === $user->id) {
            abort(422, 'You cannot report your own content.');
        }

        // One open report per person per item
        $report = Report::firstOrCreate(
            ['reporter_id' => $user->id, 'reportable_type' => $data['type'], 'reportable_id' => $target->id, 'status' => 'open'],
            ['reason' => $data['reason']],
        );

        return response()->json(['data' => ['id' => $report->id, 'message' => 'Thank you. An admin will review this report.']], 201);
    }

    /** POST /bug-reports { message, page_url } */
    public function bug(StoreBugReportRequest $request): JsonResponse
    {
        $data = $request->safe()->only(['message', 'page_url']);

        // Optional screenshot (multipart/form-data)
        if ($request->hasFile('screenshot')) {
            $data['screenshot_path'] = $request->file('screenshot')->store('bug-reports', 'public');
        }

        $bug = BugReport::create($data + ['user_id' => $request->user()->id]);

        return response()->json(['data' => ['id' => $bug->id, 'message' => 'Thank you for reporting this problem.']], 201);
    }
}
