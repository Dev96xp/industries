<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('company_settings', function (Blueprint $table) {
            $table->unsignedInteger('livewire_max_payload_mb')->default(8)->after('linkedin');
            $table->boolean('ip_restriction_enabled')->default(false)->after('livewire_max_payload_mb');
            $table->text('allowed_ips')->nullable()->after('ip_restriction_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('company_settings', function (Blueprint $table) {
            $table->dropColumn(['livewire_max_payload_mb', 'ip_restriction_enabled', 'allowed_ips']);
        });
    }
};
