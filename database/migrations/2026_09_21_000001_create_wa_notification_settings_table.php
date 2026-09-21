<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('wa_notification_settings', function (Blueprint $table) {
            $table->id();
            $table->boolean('is_enabled')->default(true);
            $table->string('target_phones')->nullable(); // e.g. "081234567890, 089876543210"
            $table->string('send_time')->default('08:00');
            
            // Rent (Sewa) settings
            $table->boolean('remind_rent')->default(true);
            $table->string('rent_days_before')->default('30,14,7,1'); // comma-separated days
            
            // Insurance (Asuransi) settings
            $table->boolean('remind_insurance')->default(true);
            $table->string('insurance_days_before')->default('30,14,7,1'); // comma-separated days
            
            // Request setting (Cutoff Pengajuan Barang)
            $table->boolean('remind_request_cutoff')->default(true);
            $table->string('request_cutoff_days_before')->default('3,1'); // comma-separated days
            
            $table->timestamps();
        });

        // Insert default initial row
        DB::table('wa_notification_settings')->insert([
            'is_enabled' => true,
            'target_phones' => '',
            'send_time' => '08:00',
            'remind_rent' => true,
            'rent_days_before' => '30,14,7,1',
            'remind_insurance' => true,
            'insurance_days_before' => '30,14,7,1',
            'remind_request_cutoff' => true,
            'request_cutoff_days_before' => '3,1',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wa_notification_settings');
    }
};
