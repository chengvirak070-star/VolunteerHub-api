<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Optional screenshot attached to a bug report. */
    public function up(): void
    {
        Schema::table('bug_reports', function (Blueprint $table) {
            $table->string('screenshot_path')->nullable()->after('page_url');
        });
    }

    public function down(): void
    {
        Schema::table('bug_reports', function (Blueprint $table) {
            $table->dropColumn('screenshot_path');
        });
    }
};
