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
            // إضافة حقول لنقل المواد بين الفِرق
            $table->uuid('from_team_id')->nullable()->after('warehouse_id');
            $table->uuid('to_team_id')->nullable()->after('from_team_id');
            $table->uuid('transfer_transaction_id')->nullable()->after('to_team_id'); // للربط بين transaction الخروج والدخول
            
            // Foreign keys
            $table->foreign('from_team_id')->references('id')->on('teams')->onDelete('set null');
            $table->foreign('to_team_id')->references('id')->on('teams')->onDelete('set null');
            $table->foreign('transfer_transaction_id')->references('id')->on('transactions')->onDelete('set null');
            
            // Index for better performance
            $table->index('from_team_id');
            $table->index('to_team_id');
            $table->index('transfer_transaction_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['from_team_id']);
            $table->dropForeign(['to_team_id']);
            $table->dropForeign(['transfer_transaction_id']);
            $table->dropIndex(['from_team_id']);
            $table->dropIndex(['to_team_id']);
            $table->dropIndex(['transfer_transaction_id']);
            $table->dropColumn(['from_team_id', 'to_team_id', 'transfer_transaction_id']);
        });
    }
};
