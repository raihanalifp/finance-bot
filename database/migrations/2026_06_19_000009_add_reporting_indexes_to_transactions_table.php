<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Purpose: Adds covering indexes for high-volume financial reporting queries.
     * Design: User/date/type indexes accelerate period scans for daily/weekly/monthly/yearly reports, while user/type/date/category
     * supports top-category aggregation without forcing broad table scans as transaction volume grows.
     */
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->index(['user_id', 'transaction_date', 'type', 'amount'], 'transactions_report_user_date_type_amount_index');
            $table->index(['user_id', 'type', 'transaction_date', 'category_id', 'amount'], 'transactions_report_user_type_date_category_amount_index');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex('transactions_report_user_date_type_amount_index');
            $table->dropIndex('transactions_report_user_type_date_category_amount_index');
        });
    }
};
