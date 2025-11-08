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
        Schema::table('transactions', function (Blueprint $table) {
            // إضافة حقول لنقل المواد بين المخازن
            $table->uuid('warehouse_from_id')->nullable()->after('warehouse_id');
            $table->uuid('warehouse_to_id')->nullable()->after('warehouse_from_id');
            
            // Foreign keys
            $table->foreign('warehouse_from_id')->references('id')->on('warehouses')->onDelete('set null');
            $table->foreign('warehouse_to_id')->references('id')->on('warehouses')->onDelete('set null');
            
            // Index for better performance
            $table->index('warehouse_from_id');
            $table->index('warehouse_to_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['warehouse_from_id']);
            $table->dropForeign(['warehouse_to_id']);
            $table->dropIndex(['warehouse_from_id']);
            $table->dropIndex(['warehouse_to_id']);
            $table->dropColumn(['warehouse_from_id', 'warehouse_to_id']);
        });
    }
};

