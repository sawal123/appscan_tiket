<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('tickets')) {
            return;
        }

        Schema::table('tickets', function (Blueprint $table): void {
            if (! Schema::hasColumn('tickets', 'event_id')) {
                $table->foreignId('event_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
            }

            if (! Schema::hasColumn('tickets', 'qr_code')) {
                $table->string('qr_code')->nullable()->after('code')->unique();
            }

            if (! Schema::hasColumn('tickets', 'status')) {
                $table->string('status')->default('registered')->after('qr_code')->index();
            }

            if (! Schema::hasColumn('tickets', 'registered_at')) {
                $table->timestamp('registered_at')->nullable()->after('status');
            }

            if (! Schema::hasColumn('tickets', 'registered_by')) {
                $table->foreignId('registered_by')->nullable()->after('registered_at')->constrained('users')->nullOnDelete();
            }
        });

        if (Schema::hasColumn('tickets', 'qr_code') && Schema::hasColumn('tickets', 'code')) {
            DB::table('tickets')->whereNull('qr_code')->update(['qr_code' => DB::raw('code')]);
        }

        if (Schema::hasColumn('tickets', 'event_id')) {
            DB::statement(
                'update tickets set event_id = (select event_id from ticket_categories where ticket_categories.id = tickets.ticket_category_id) where event_id is null'
            );
        }

        if (Schema::hasColumn('tickets', 'registered_at')) {
            DB::table('tickets')->whereNull('registered_at')->update(['registered_at' => DB::raw('created_at')]);
        }

        if (Schema::hasColumn('tickets', 'status') && Schema::hasColumn('tickets', 'checked_in_at')) {
            DB::table('tickets')->whereNotNull('checked_in_at')->update(['status' => 'checked_in']);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('tickets')) {
            return;
        }

        Schema::table('tickets', function (Blueprint $table): void {
            if (Schema::hasColumn('tickets', 'registered_by')) {
                $table->dropConstrainedForeignId('registered_by');
            }

            foreach (['registered_at', 'status', 'qr_code'] as $column) {
                if (Schema::hasColumn('tickets', $column)) {
                    $table->dropColumn($column);
                }
            }

            if (Schema::hasColumn('tickets', 'event_id')) {
                $table->dropConstrainedForeignId('event_id');
            }
        });
    }
};
