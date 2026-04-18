<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('number')->nullable()->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('status', ['draft', 'planning', 'in_progress', 'on_hold', 'completed', 'cancelled'])->default('draft');
            $table->string('address')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->unsignedInteger('geo_radius')->default(100);
            $table->date('start_date')->nullable();
            $table->date('estimated_completion_date')->nullable();
            $table->decimal('budget', 12, 2)->nullable();
            $table->foreignId('client_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('internal_notes')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_archived')->default(false);
            $table->unsignedBigInteger('location_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
