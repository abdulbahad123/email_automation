<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('contact_enquiries')) {
            Schema::create('contact_enquiries', function (Blueprint $table) {
                $table->id();
                $table->string('id_secure', 32)->nullable()->index();
                $table->string('name', 191)->nullable();
                $table->string('email', 191)->nullable()->index();
                $table->string('phone', 100)->nullable();
                $table->string('subject', 191)->nullable();
                $table->text('message')->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->integer('status')->default(0); // 0 = unread/new, 1 = read, 2 = replied
                $table->bigInteger('created')->nullable();
                $table->bigInteger('changed')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_enquiries');
    }
};
