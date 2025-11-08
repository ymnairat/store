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
        Schema::table('products', function (Blueprint $table) {
            // Drop foreign key constraint first, then drop column
            if (Schema::hasColumn('products', 'team_id')) {
                // Try to drop foreign key - it may have a specific name
                try {
                    $table->dropForeign(['team_id']);
                } catch (\Exception $e) {
                    // Foreign key might not exist or have different name, try alternative method
                    try {
                        $table->dropForeign('products_team_id_foreign');
                    } catch (\Exception $e2) {
                        // Ignore if foreign key doesn't exist
                    }
                }
                
                $table->dropColumn('team_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->uuid('team_id')->nullable()->after('description');
            // Note: teams table may not exist, so we don't add foreign key in rollback
        });
    }
};
