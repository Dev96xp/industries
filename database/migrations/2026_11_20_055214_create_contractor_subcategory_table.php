<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contractor_subcategory', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contractor_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subcategory_id')->constrained()->restrictOnDelete();
            $table->timestamps();

            $table->unique(['contractor_id', 'subcategory_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contractor_subcategory');
    }
};
