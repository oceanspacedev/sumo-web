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
        Schema::table('rents', function (Blueprint $table) {
            $table->string('reminder_phones')->nullable()->after('notes');
        });

        Schema::table('insurances', function (Blueprint $table) {
            $table->string('reminder_phones')->nullable()->after('notes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rents', function (Blueprint $table) {
            $table->dropColumn('reminder_phones');
        });

        Schema::table('insurances', function (Blueprint $table) {
            $table->dropColumn('reminder_phones');
        });
    }
};
