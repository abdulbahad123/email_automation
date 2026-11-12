<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('upi_incoming_credits')) {
            Schema::create('upi_incoming_credits', function (Blueprint $table) {
                $table->id();
                $table->string('utr_number', 64)->unique();
                $table->decimal('amount', 10, 2);
                $table->string('sender_name', 255)->nullable();
                $table->string('sender_upi', 255)->nullable();
                $table->string('source', 50)->default('gpay_sync');
                $table->text('raw_message')->nullable();
                $table->tinyInteger('is_used')->default(0);
                $table->unsignedBigInteger('used_by_user_id')->nullable();
                $table->timestamp('used_at')->nullable();
                $table->timestamps();

                $table->index(['utr_number', 'is_used']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('upi_incoming_credits');
    }
};
