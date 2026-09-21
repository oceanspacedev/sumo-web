<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('wa_notification_logs', function (Blueprint $table) {
            $table->id();
            $table->string('notif_type'); // RENT, INSURANCE, REQUEST_SETTING, TEST
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('phone');
            $table->date('sent_date');
            $table->string('status'); // SUCCESS, FAILED
            $table->text('message_preview')->nullable();
            $table->text('response_payload')->nullable();
            $table->timestamps();

            $table->index(['notif_type', 'reference_id', 'sent_date', 'phone'], 'wa_notif_unique_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wa_notification_logs');
    }
};
