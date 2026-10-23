<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->string('invoice_number')->nullable()->unique()->after('title');
            $table->date('invoice_date')->nullable()->after('invoice_number');
            $table->date('invoice_due_date')->nullable()->after('invoice_date');
        });
    }

    public function down(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->dropUnique(['invoice_number']);
            $table->dropColumn(['invoice_number', 'invoice_date', 'invoice_due_date']);
        });
    }
};
