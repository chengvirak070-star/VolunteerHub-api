<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Extra profile details (all optional). Phone is private: only the user and admins see it. */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('city', 100)->nullable()->after('bio');
            $table->string('school', 150)->nullable()->after('city');
            $table->json('skills')->nullable()->after('school');
            $table->string('telegram', 40)->nullable()->after('skills');
            $table->string('phone', 30)->nullable()->after('telegram');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['city', 'school', 'skills', 'telegram', 'phone']);
        });
    }
};
