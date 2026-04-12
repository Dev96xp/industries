<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->enum('status', ['draft', 'planning', 'in_progress', 'on_hold', 'completed', 'cancelled'])
                ->default('draft')
                ->change();
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->enum('status', ['planning', 'in_progress', 'completed'])
                ->default('planning')
                ->change();
        });
    }
};
