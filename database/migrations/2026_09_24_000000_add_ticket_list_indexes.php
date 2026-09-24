<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table): void {
            $table->index(['event_id', 'created_at'], 'tickets_event_created_at_index');
            $table->index(['ticket_category_id', 'created_at'], 'tickets_category_created_at_index');
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table): void {
            $table->dropIndex('tickets_event_created_at_index');
            $table->dropIndex('tickets_category_created_at_index');
        });
    }
};
