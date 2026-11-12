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
        /*
        |--------------------------------------------------------------------------
        | WhatsApp AI Smart Reply
        |--------------------------------------------------------------------------
        */
        if (!Schema::hasTable('whatsapp_ai_smart_reply')) {
        Schema::create('whatsapp_ai_smart_reply', function (Blueprint $table) {
            $table->id();
            $table->string('id_secure')->nullable()->unique();
            $table->unsignedInteger('team_id')->nullable()->index();
            $table->string('instance_id')->nullable()->index();
            $table->mediumText('prompt')->nullable();
            $table->mediumText('fallback_caption')->nullable();
            $table->text('except')->nullable();
            $table->integer('delay')->default(1);
            $table->integer('send_to')->default(1);
            $table->integer('max_length')->default(120);
            $table->integer('sent')->default(0);
            $table->integer('failed')->default(0);
            $table->tinyInteger('status')->default(1);
            $table->integer('changed')->nullable();
            $table->integer('created')->nullable();
        });
        }

        /*
        |--------------------------------------------------------------------------
        | WhatsApp Autoresponder
        |--------------------------------------------------------------------------
        */
        if (!Schema::hasTable('whatsapp_autoresponder')) {
        Schema::create('whatsapp_autoresponder', function (Blueprint $table) {
            $table->id();
            $table->text('id_secure')->nullable();
            $table->unsignedInteger('team_id')->nullable()->index();
            $table->text('instance_id')->nullable();
            $table->integer('type')->nullable();
            $table->integer('template')->nullable();
            $table->text('caption')->nullable();
            $table->longText('media')->nullable();
            $table->longText('except')->nullable();
            $table->text('path')->nullable();
            $table->integer('delay')->nullable();
            $table->text('result')->nullable();
            $table->integer('sent')->nullable();
            $table->integer('failed')->nullable();
            $table->integer('send_to')->nullable();
            $table->integer('status')->nullable();
            $table->integer('changed')->nullable();
            $table->integer('created')->nullable();
        });
        }

        /*
        |--------------------------------------------------------------------------
        | WhatsApp Chatbot
        |--------------------------------------------------------------------------
        */
        if (!Schema::hasTable('whatsapp_chatbot')) {
        Schema::create('whatsapp_chatbot', function (Blueprint $table) {
            $table->id();
            $table->text('id_secure')->nullable();
            $table->text('name')->nullable();
            $table->text('keywords')->nullable();
            $table->text('instance_id')->nullable();
            $table->unsignedInteger('team_id')->nullable()->index();
            $table->integer('type_search')->default(1);
            $table->integer('template')->nullable();
            $table->integer('type')->nullable();
            $table->text('caption')->nullable();
            $table->text('media')->nullable();
            $table->integer('run')->default(1);
            $table->integer('sent')->nullable();
            $table->integer('failed')->nullable();
            $table->integer('send_to')->nullable();
            $table->integer('status')->nullable();
            $table->integer('changed')->nullable();
            $table->integer('created')->nullable();
        });
        }

        /*
        |--------------------------------------------------------------------------
        | WhatsApp Contacts
        |--------------------------------------------------------------------------
        */
        if (!Schema::hasTable('whatsapp_contacts')) {
        Schema::create('whatsapp_contacts', function (Blueprint $table) {
            $table->id();
            $table->string('id_secure', 32)->nullable();
            $table->unsignedInteger('team_id')->nullable()->index();
            $table->string('name')->nullable();
            $table->integer('status')->nullable();
            $table->integer('changed')->nullable();
            $table->integer('created')->nullable();
        });
        }

        /*
        |--------------------------------------------------------------------------
        | WhatsApp Phone Numbers
        |--------------------------------------------------------------------------
        */
        if (!Schema::hasTable('whatsapp_phone_numbers')) {
        Schema::create('whatsapp_phone_numbers', function (Blueprint $table) {
            $table->id();
            $table->string('id_secure', 15)->nullable();
            $table->unsignedInteger('team_id')->nullable()->index();
            $table->unsignedInteger('pid')->nullable();
            $table->string('phone')->nullable();
            $table->text('params')->nullable();
        });
        }

        /*
        |--------------------------------------------------------------------------
        | WhatsApp Schedules
        |--------------------------------------------------------------------------
        */
        if (!Schema::hasTable('whatsapp_schedules')) {
        Schema::create('whatsapp_schedules', function (Blueprint $table) {
            $table->id();
            $table->string('id_secure', 32)->nullable();
            $table->unsignedInteger('team_id')->nullable()->index();
            $table->text('accounts')->nullable();
            $table->integer('next_account')->nullable();
            $table->integer('contact_id')->nullable();
            $table->integer('type')->default(1);
            $table->integer('template')->nullable();
            $table->integer('time_post')->nullable();
            $table->integer('min_delay')->nullable();
            $table->string('schedule_time')->nullable();
            $table->string('timezone')->nullable();
            $table->integer('max_delay')->nullable();
            $table->string('name')->nullable();
            $table->text('caption')->nullable();
            $table->text('media')->nullable();
            $table->integer('sent')->default(0);
            $table->integer('failed')->default(0);
            $table->longText('result')->nullable();
            $table->integer('run')->default(0);
            $table->integer('status')->nullable();
            $table->integer('changed')->nullable();
            $table->integer('created');
        });
        }

        /*
        |--------------------------------------------------------------------------
        | WhatsApp Sessions
        |--------------------------------------------------------------------------
        */
        if (!Schema::hasTable('whatsapp_sessions')) {
        Schema::create('whatsapp_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('id_secure', 32)->nullable();
            $table->unsignedInteger('team_id')->nullable()->index();
            $table->string('instance_id')->nullable();
            $table->longText('data')->nullable();
            $table->integer('status')->nullable();
        });
        }

        /*
        |--------------------------------------------------------------------------
        | WhatsApp Stats
        |--------------------------------------------------------------------------
        */
        if (!Schema::hasTable('whatsapp_stats')) {
        Schema::create('whatsapp_stats', function (Blueprint $table) {
            $table->id();
            $table->string('id_secure', 32)->nullable();
            $table->unsignedInteger('team_id')->nullable()->index();
            $table->integer('wa_total_sent_by_month')->nullable();
            $table->integer('wa_total_sent')->nullable();
            $table->integer('wa_chatbot_count')->nullable();
            $table->integer('wa_autoresponder_count')->nullable();
            $table->integer('wa_api_count')->nullable();
            $table->integer('wa_bulk_total_count')->nullable();
            $table->integer('wa_bulk_sent_count')->nullable();
            $table->integer('wa_bulk_failed_count')->nullable();
            $table->integer('wa_time_reset')->nullable();
            $table->integer('next_update')->nullable();
        });
        }

        /*
        |--------------------------------------------------------------------------
        | WhatsApp Template
        |--------------------------------------------------------------------------
        */
        if (!Schema::hasTable('whatsapp_template')) {
        Schema::create('whatsapp_template', function (Blueprint $table) {
            $table->id();
            $table->string('id_secure', 32)->nullable();
            $table->unsignedInteger('team_id')->nullable()->index();
            $table->integer('type')->nullable();
            $table->string('name')->nullable();
            $table->longText('data')->nullable();
            $table->integer('changed')->nullable();
            $table->integer('created')->nullable();
        });
        }

        /*
        |--------------------------------------------------------------------------
        | WhatsApp Webhook
        |--------------------------------------------------------------------------
        */
        if (!Schema::hasTable('whatsapp_webhook')) {
        Schema::create('whatsapp_webhook', function (Blueprint $table) {
            $table->id();
            $table->text('id_secure')->nullable();
            $table->unsignedInteger('team_id')->nullable()->index();
            $table->text('instance_id')->nullable();
            $table->text('webhook_url')->nullable();
            $table->integer('status')->nullable();
        });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whatsapp_webhook');
        Schema::dropIfExists('whatsapp_template');
        Schema::dropIfExists('whatsapp_stats');
        Schema::dropIfExists('whatsapp_sessions');
        Schema::dropIfExists('whatsapp_schedules');
        Schema::dropIfExists('whatsapp_phone_numbers');
        Schema::dropIfExists('whatsapp_contacts');
        Schema::dropIfExists('whatsapp_chatbot');
        Schema::dropIfExists('whatsapp_autoresponder');
        Schema::dropIfExists('whatsapp_ai_smart_reply');
    }
};