<?php

namespace Modules\AppEmailSMTP\Services;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class EmailSchemaService
{
    protected static bool $ensured = false;

    public static function ensureTables(): void
    {
        if (self::$ensured) {
            return;
        }

        try {
            if (!Schema::hasTable('email_smtp_accounts')) {
                Schema::create('email_smtp_accounts', function (Blueprint $table) {
                    $table->id();
                    $table->string('id_secure', 32)->unique();
                    $table->unsignedBigInteger('team_id')->index();
                    $table->string('name', 191);
                    $table->string('host', 191);
                    $table->integer('port')->default(587);
                    $table->string('encryption', 20)->default('tls');
                    $table->string('username', 191)->nullable();
                    $table->text('password')->nullable();
                    $table->string('from_email', 191);
                    $table->string('from_name', 191)->nullable();
                    $table->string('reply_to', 191)->nullable();
                    $table->integer('daily_limit')->default(0);
                    $table->integer('sent_today')->default(0);
                    $table->date('last_reset_date')->nullable();
                    $table->tinyInteger('status')->default(1);
                    $table->text('last_error')->nullable();
                    $table->integer('last_tested_at')->nullable();
                    $table->integer('changed')->nullable();
                    $table->integer('created')->nullable();
                });
            }

            if (!Schema::hasTable('email_contact_groups')) {
                Schema::create('email_contact_groups', function (Blueprint $table) {
                    $table->id();
                    $table->string('id_secure', 32)->unique();
                    $table->unsignedBigInteger('team_id')->index();
                    $table->string('name', 191);
                    $table->tinyInteger('status')->default(1);
                    $table->integer('changed')->nullable();
                    $table->integer('created')->nullable();
                });
            }

            if (!Schema::hasTable('email_contacts')) {
                Schema::create('email_contacts', function (Blueprint $table) {
                    $table->id();
                    $table->string('id_secure', 32)->unique();
                    $table->unsignedBigInteger('team_id')->index();
                    $table->unsignedBigInteger('group_id')->index();
                    $table->string('email', 191)->index();
                    $table->string('name', 191)->nullable();
                    $table->text('params')->nullable();
                    $table->tinyInteger('unsubscribed')->default(0)->index();
                    $table->tinyInteger('status')->default(1);
                    $table->integer('created')->nullable();
                    $table->unique(['group_id', 'email'], 'email_contacts_group_email_unique');
                });
            }

            if (!Schema::hasTable('email_campaigns')) {
                Schema::create('email_campaigns', function (Blueprint $table) {
                    $table->id();
                    $table->string('id_secure', 32)->unique();
                    $table->unsignedBigInteger('team_id')->index();
                    $table->string('name', 191);
                    $table->string('subject', 255);
                    $table->longText('body');
                    $table->text('smtp_ids');
                    $table->unsignedBigInteger('group_id')->index();
                    $table->text('attachment')->nullable();
                    $table->integer('time_post')->index();
                    $table->integer('batch_size')->default(50);
                    $table->integer('min_delay')->default(1);
                    $table->integer('max_delay')->default(2);
                    $table->text('schedule_time')->nullable();
                    $table->string('timezone', 64)->nullable();
                    $table->integer('total_contacts')->default(0);
                    $table->integer('sent')->default(0);
                    $table->integer('failed')->default(0);
                    $table->integer('next_smtp_index')->default(0);
                    $table->unsignedBigInteger('last_contact_id')->default(0);
                    $table->tinyInteger('status')->default(1)->index();
                    $table->tinyInteger('is_processing')->default(0);
                    $table->integer('processing_started_at')->nullable();
                    $table->text('result')->nullable();
                    $table->integer('changed')->nullable();
                    $table->integer('created')->nullable();
                });
            }

            if (!Schema::hasTable('email_logs')) {
                Schema::create('email_logs', function (Blueprint $table) {
                    $table->id();
                    $table->string('id_secure', 32)->unique();
                    $table->unsignedBigInteger('team_id')->index();
                    $table->unsignedBigInteger('campaign_id')->nullable()->index();
                    $table->unsignedBigInteger('smtp_id')->nullable()->index();
                    $table->unsignedBigInteger('contact_id')->nullable()->index();
                    $table->string('to_email', 191)->index();
                    $table->string('subject', 255);
                    $table->tinyInteger('status')->default(0)->index();
                    $table->text('error_message')->nullable();
                    $table->integer('sent_at')->nullable();
                    $table->integer('created')->nullable();
                    $table->index(['campaign_id', 'contact_id'], 'email_logs_campaign_contact_idx');
                });
            }

            self::$ensured = true;
        } catch (\Throwable $e) {
            \Log::warning('EmailSchemaService::ensureTables warning: ' . $e->getMessage());
        }
    }
}

