<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('personal_access_tokens')) {
            return;
        }

        if (! Schema::hasColumn('personal_access_tokens', 'expires_at')) {
            Schema::table('personal_access_tokens', function (Blueprint $table): void {
                $table->timestamp('expires_at')->nullable();
            });
        }

        if (! Schema::hasIndex('personal_access_tokens', 'personal_access_tokens_expires_at_index')) {
            Schema::table('personal_access_tokens', function (Blueprint $table): void {
                $table->index('expires_at');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('personal_access_tokens') || ! Schema::hasColumn('personal_access_tokens', 'expires_at')) {
            return;
        }

        if (Schema::hasIndex('personal_access_tokens', 'personal_access_tokens_expires_at_index')) {
            Schema::table('personal_access_tokens', function (Blueprint $table): void {
                $table->dropIndex(['expires_at']);
            });
        }

        Schema::table('personal_access_tokens', function (Blueprint $table): void {
            $table->dropColumn('expires_at');
        });
    }
};
