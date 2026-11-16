<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->text('description');
            $table->string('location');
            $table->dateTime('starts_at')->index();
            $table->dateTime('ends_at');
            $table->decimal('hours', 5, 2);
            $table->unsignedInteger('capacity');
            $table->string('cover_path')->nullable();
            $table->string('status', 20)->default('pending')->index(); // pending | open | full | closed | rejected
            $table->timestamp('reminder_sent_at')->nullable(); // used by the 1-day reminder command
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
