<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('id_secure', 32)->nullable();
            $table->integer('role')->nullable();
            $table->string('pid', 20)->nullable();
            $table->string('login_type', 20)->nullable();
            $table->string('fullname')->nullable();
            $table->string('username')->nullable();
            $table->string('email', 100)->nullable();
            $table->string('password', 100)->nullable();
            $table->string('avatar')->nullable();
            $table->integer('plan_id')->nullable();
            $table->bigInteger('expiration_date')->nullable();
            $table->string('timezone', 50)->nullable();
            $table->string('language', 10)->nullable();
            $table->mediumText('data')->nullable();
            $table->string('secret_key', 50)->nullable();
            $table->integer('last_login')->nullable();
            $table->integer('status')->nullable();
            $table->string('remember_token', 100)->nullable();
            $table->integer('changed')->nullable();
            $table->integer('created')->nullable();
        });

        Schema::create('addons', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('id_secure', 32)->nullable();
            $table->integer('source')->nullable();
            $table->integer('product_id')->nullable();
            $table->string('module_name', 50)->nullable();
            $table->string('purchase_code', 191)->nullable();
            $table->integer('is_main')->nullable();
            $table->string('version', 50)->nullable();
            $table->string('install_path', 255)->nullable();
            $table->string('relative_path', 255)->nullable();
            $table->integer('status')->nullable();
            $table->integer('changed')->nullable();
            $table->integer('created')->nullable();
            $table->index('purchase_code');
            $table->index('product_id');
        });

        Schema::create('affiliate', function (Blueprint $table) {
            $table->increments('id');
            $table->string('id_secure', 32);
            $table->integer('affiliate_uid');
            $table->integer('payment_id')->nullable();
            $table->float('amount');
            $table->float('commission_rate');
            $table->float('commission');
            $table->integer('status');
            $table->integer('created');
        });

        Schema::create('affiliate_info', function (Blueprint $table) {
            $table->increments('id');
            $table->string('id_secure', 32)->nullable();
            $table->integer('affiliate_uid')->nullable();
            $table->integer('clicks')->default(0);
            $table->integer('conversions')->default(0);
            $table->float('total_withdrawal')->default(0);
            $table->float('total_approved')->nullable();
            $table->float('total_balance')->nullable();
        });

        Schema::create('affiliate_withdrawal', function (Blueprint $table) {
            $table->increments('id');
            $table->string('id_secure', 32)->nullable();
            $table->integer('affiliate_uid')->nullable();
            $table->float('amount')->nullable();
            $table->text('bank')->nullable();
            $table->text('notes')->nullable();
            $table->integer('status')->nullable();
            $table->integer('created')->nullable();
        });

        Schema::create('ai_categories', function (Blueprint $table) {
            $table->increments('id');
            $table->string('id_secure', 32)->nullable();
            $table->string('name', 100)->nullable();
            $table->string('desc', 500)->nullable();
            $table->string('icon', 150)->nullable();
            $table->string('color', 30)->nullable();
            $table->integer('status')->nullable();
            $table->integer('changed')->nullable();
            $table->integer('created')->nullable();
        });

        Schema::create('ai_posts', function (Blueprint $table) {
            $table->increments('id');
            $table->string('id_secure', 32)->nullable();
            $table->integer('team_id')->nullable();
            $table->string('name')->nullable();
            $table->string('accounts', 500)->nullable();
            $table->longText('prompts')->nullable();
            $table->integer('time_post')->nullable();
            $table->integer('end_date')->nullable();
            $table->integer('next_try')->nullable();
            $table->text('data')->nullable();
            $table->string('result', 500)->nullable();
            $table->integer('status')->nullable();
            $table->integer('changed')->nullable();
            $table->integer('created')->nullable();
        });

        Schema::create('ai_prompts', function (Blueprint $table) {
            $table->increments('id');
            $table->string('id_secure', 32)->nullable();
            $table->integer('team_id')->nullable();
            $table->text('prompt')->nullable();
        });

        Schema::create('ai_templates', function (Blueprint $table) {
            $table->increments('id');
            $table->string('id_secure', 32)->nullable();
            $table->integer('cate_id')->nullable();
            $table->text('content')->nullable();
            $table->integer('status')->nullable();
            $table->integer('changed')->nullable();
            $table->integer('created')->nullable();
        });

        Schema::create('ai_models', function (Blueprint $table) {
            $table->id();
            $table->string('id_secure', 50)
                  ->nullable()
                  ->unique();
            $table->string('provider');       // openai, claude, gemini, deepseek...
            $table->string('model_key');      // gpt-4o, gpt-5, claude-haiku...
            $table->string('name');           // Friendly name
            $table->string('category')->default('text'); 
            $table->string('type')->nullable(); 
            $table->boolean('is_active')->default(true);
            $table->string('api_type')->default('chat')
                  ->comment('API endpoint type: chat, responses, audio, image, video, embedding...');
            $table->json('api_params')->nullable()
                  ->comment('Custom API params mapping, e.g., {"max_tokens":"max_output_tokens"}');
            $table->json('meta')->nullable();

            $table->timestamps();

            $table->unique(['provider', 'model_key', 'category']);
        });
        
        Schema::create('accounts', function (Blueprint $table) {
            $table->increments('id');
            $table->string('id_secure', 32)->nullable();
            $table->string('module')->nullable();
            $table->string('social_network')->nullable();
            $table->string('category')->nullable();
            $table->string('reconnect_url', 255)->nullable();
            $table->integer('team_id')->nullable();
            $table->integer('login_type')->nullable();
            $table->integer('can_post')->nullable();
            $table->string('pid')->nullable();
            $table->string('name')->nullable();
            $table->string('username')->nullable();
            $table->text('token')->nullable();
            $table->string('avatar', 500)->nullable();
            $table->string('url')->nullable();
            $table->string('tmp')->nullable();
            $table->mediumText('data')->nullable();
            $table->integer('proxy')->nullable();
            $table->integer('run')->nullable();
            $table->integer('status')->nullable();
            $table->integer('changed')->nullable();
            $table->integer('created')->nullable();
        });

        Schema::create('articles', function (Blueprint $table) {
            $table->increments('id');
            $table->string('id_secure', 32)->nullable();
            $table->string('type', 100)->nullable();
            $table->integer('cate_id')->nullable()->default(0);
            $table->string('slug', 500);
            $table->string('title', 500)->nullable();
            $table->text('desc')->nullable();
            $table->longText('content')->nullable();
            $table->string('thumbnail', 500)->nullable();
            $table->text('custom_1')->nullable();
            $table->text('custom_2')->nullable();
            $table->text('custom_3')->nullable();
            $table->integer('status')->nullable();
            $table->integer('changed')->nullable();
            $table->integer('created')->nullable();
        });

        Schema::create('article_categories', function (Blueprint $table) {
            $table->increments('id');
            $table->string('id_secure', 32)->nullable();
            $table->string('type', 100)->nullable();
            $table->string('name', 255)->nullable();
            $table->string('slug', 255)->nullable();
            $table->text('desc')->nullable();
            $table->string('icon', 150)->nullable();
            $table->string('color', 30)->nullable();
            $table->integer('status')->nullable();
            $table->integer('changed')->nullable();
            $table->integer('created')->nullable();
        });

        Schema::create('article_map_tags', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('article_id')->nullable();
            $table->integer('tag_id')->nullable();
        });

        Schema::create('article_tags', function (Blueprint $table) {
            $table->increments('id');
            $table->string('id_secure', 32)->nullable();
            $table->string('name', 100)->nullable();
            $table->string('slug', 100)->nullable();
            $table->string('desc', 500)->nullable();
            $table->string('icon', 150)->nullable();
            $table->string('color', 30)->nullable();
            $table->integer('status')->nullable();
            $table->integer('changed')->nullable();
            $table->integer('created')->nullable();
        });

        Schema::create('cache', function (Blueprint $table) {
            $table->string('key', 191)->primary();
            $table->text('value');
            $table->integer('expiration');
        });

        Schema::create('cache_locks', function (Blueprint $table) {
            $table->string('key', 191)->primary();
            $table->string('owner', 191);
            $table->integer('expiration');
        });

        Schema::create('captions', function (Blueprint $table) {
            $table->increments('id');
            $table->string('id_secure', 32)->nullable();
            $table->integer('team_id')->nullable();
            $table->integer('type')->nullable();
            $table->string('name', 255)->nullable();
            $table->text('content')->nullable();
            $table->integer('changed')->nullable();
            $table->integer('created')->nullable();
        });

        Schema::create('coupons', function (Blueprint $table) {
            $table->increments('id');
            $table->string('id_secure', 32)->nullable();
            $table->string('name', 255)->nullable();
            $table->string('code', 32)->nullable();
            $table->integer('type')->default(1);
            $table->float('discount')->nullable();
            $table->integer('start_date')->nullable();
            $table->integer('end_date')->nullable();
            $table->text('plans')->nullable();
            $table->integer('usage_limit')->nullable();
            $table->integer('usage_count')->nullable();
            $table->integer('status')->default(1);
            $table->integer('changed')->nullable();
            $table->integer('created')->nullable();
        });

        Schema::create('credit_usages', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('team_id')->nullable();
            $table->string('feature', 50)->nullable();
            $table->string('model', 100)->nullable();
            $table->integer('date')->nullable();
            $table->integer('credits_used')->default(0);
            $table->integer('changed')->nullable();
            $table->integer('created')->nullable();
            $table->unique(['team_id', 'feature', 'model', 'date'], 'team_feature_model_date_unique');
        });

        Schema::create('files', function (Blueprint $table) {
            $table->increments('id');
            $table->mediumText('id_secure')->nullable();
            $table->integer('is_folder')->default(0);
            $table->integer('pid')->default(0);
            $table->integer('team_id')->nullable();
            $table->mediumText('name')->nullable();
            $table->mediumText('file')->nullable();
            $table->mediumText('type')->nullable();
            $table->mediumText('extension')->nullable();
            $table->text('detect')->nullable();
            $table->float('size')->nullable();
            $table->integer('is_image')->nullable();
            $table->integer('width')->nullable();
            $table->integer('height')->nullable();
            $table->mediumText('note')->nullable();
            $table->integer('created')->nullable();
        });

        Schema::create('groups', function (Blueprint $table) {
            $table->increments('id');
            $table->string('id_secure', 32)->nullable();
            $table->integer('team_id')->nullable();
            $table->string('name')->nullable();
            $table->string('color', 32)->nullable();
            $table->longText('accounts')->nullable();
            $table->integer('changed')->nullable();
            $table->integer('created')->nullable();
        });

        Schema::create('jobs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('queue', 191);
            $table->longText('payload');
            $table->tinyInteger('attempts')->unsigned()->default(0);
            $table->integer('reserved_at')->unsigned()->nullable();
            $table->integer('available_at')->unsigned()->nullable();
            $table->integer('created_at')->unsigned();
            $table->index('queue');
        });

        Schema::create('languages', function (Blueprint $table) {
            $table->increments('id');
            $table->string('id_secure', 255)->nullable();
            $table->string('name', 255)->nullable();
            $table->string('code', 10)->nullable();
            $table->string('icon', 32)->nullable();
            $table->string('dir', 3)->nullable();
            $table->integer('is_default')->nullable();
            $table->integer('auto_translate')->nullable();
            $table->integer('status')->nullable();
            $table->integer('changed')->nullable();
            $table->integer('created')->nullable();
        });

        Schema::create('language_items', function (Blueprint $table) {
            $table->increments('id');
            $table->string('code', 5);
            $table->text('name');
            $table->text('value')->nullable();
            $table->integer('custom')->default(0);
        });

        Schema::create('module_statuses', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('module', 191);
            $table->boolean('enabled')->default(0);
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->unique('module');
        });

        Schema::create('notifications', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('id_secure', 32)->nullable();
            $table->unsignedBigInteger('user_id');
            $table->enum('source', ['auto', 'manual']);
            $table->unsignedBigInteger('mid')->nullable();
            $table->string('type', 50)->default('news');
            $table->text('message')->nullable();
            $table->string('url', 255)->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            $table->index('mid');
            $table->index('user_id');
        });
        
        Schema::create('notification_manual', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('id_secure', 32)->nullable();
            $table->string('title', 255)->nullable();
            $table->text('message');
            $table->string('url', 255)->nullable();
            $table->string('type', 50)->default('news');
            $table->unsignedBigInteger('created_by');
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });

        Schema::create('options', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 255)->nullable();
            $table->longText('value')->nullable();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email', 191)->primary();
            $table->string('token', 255);
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('payment_getways', function (Blueprint $table) {
            $table->increments('id');
            $table->string('id_sercure', 32)->nullable();
            $table->string('name', 250)->nullable();
            $table->string('desc', 500)->nullable();
            $table->string('module', 250)->nullable();
            $table->integer('status')->nullable();
        });

        Schema::create('payment_history', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('id_secure', 32)->nullable();
            $table->integer('uid')->nullable();
            $table->integer('plan_id')->nullable();
            $table->string('from', 32)->nullable();
            $table->string('transaction_id')->nullable();
            $table->string('currency', 10)->nullable();
            $table->integer('by')->nullable();
            $table->float('amount')->nullable();
            $table->integer('status')->nullable();
            $table->integer('changed')->nullable();
            $table->integer('created')->nullable();
        });

        Schema::create('payment_manual', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('id_secure', 32)->nullable();
            $table->integer('uid')->nullable();
            $table->integer('plan_id')->nullable();
            $table->string('payment_id')->nullable();
            $table->text('payment_info')->nullable();
            $table->float('amount')->nullable();
            $table->string('currency', 10)->nullable();
            $table->text('notes')->nullable();
            $table->integer('status')->nullable();
            $table->integer('changed')->nullable();
            $table->integer('created')->nullable();
        });

        Schema::create('payment_subscriptions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('id_secure', 32)->nullable();
            $table->integer('uid')->nullable();
            $table->integer('plan_id')->nullable();
            $table->integer('type')->nullable();
            $table->string('service', 200)->nullable();
            $table->string('source', 50)->nullable();
            $table->text('subscription_id')->nullable();
            $table->text('customer_id')->nullable();
            $table->float('amount')->nullable();
            $table->string('currency', 20)->nullable();
            $table->integer('status')->nullable();
            $table->integer('changed')->nullable();
            $table->integer('created')->nullable();
        });

        Schema::create('plans', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('id_secure', 32)->nullable();
            $table->string('name', 255)->nullable();
            $table->text('desc')->nullable();
            $table->integer('type')->nullable();
            $table->float('price')->nullable();
            $table->integer('trial_day')->nullable();
            $table->integer('free_plan')->nullable();
            $table->integer('featured')->nullable();
            $table->integer('position')->nullable();
            $table->mediumText('permissions')->nullable();
            $table->mediumText('data')->nullable();
            $table->integer('status')->nullable();
            $table->integer('changed')->nullable();
            $table->integer('created')->nullable();
        });

        Schema::create('proxies', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('id_secure', 32)->nullable();
            $table->integer('team_id')->default(0);
            $table->integer('is_system')->nullable();
            $table->string('description', 255)->nullable();
            $table->string('proxy', 255)->nullable();
            $table->string('location', 100)->nullable();
            $table->float('limit')->nullable();
            $table->integer('is_free')->nullable();
            $table->integer('active')->nullable();
            $table->integer('status')->nullable();
            $table->integer('changed')->nullable();
            $table->integer('created')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id', 191)->primary();
            $table->bigInteger('user_id')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity');
        });

        Schema::create('support_categories', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('id_secure', 32)->nullable();
            $table->string('name', 255)->nullable();
            $table->text('desc')->nullable();
            $table->string('icon', 150)->nullable();
            $table->string('color', 32)->nullable();
            $table->integer('status')->nullable();
            $table->integer('changed')->nullable();
            $table->integer('created')->nullable();
        });

        Schema::create('support_comments', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('id_secure', 32)->nullable();
            $table->integer('ticket_id')->nullable();
            $table->integer('user_id')->nullable();
            $table->longText('comment')->nullable();
            $table->integer('changed')->nullable();
            $table->integer('created')->nullable();
        });

        Schema::create('support_labels', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('id_secure', 32)->nullable();
            $table->string('name', 100)->nullable();
            $table->string('desc', 500)->nullable();
            $table->string('icon', 150)->nullable();
            $table->string('color', 30)->nullable();
            $table->integer('status')->nullable();
            $table->integer('changed')->nullable();
            $table->integer('created')->nullable();
        });

        Schema::create('support_map_labels', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('ticket_id')->nullable();
            $table->integer('label_id')->nullable();
        });

        Schema::create('support_tickets', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('id_secure', 32)->nullable();
            $table->integer('user_read')->nullable();
            $table->integer('admin_read')->nullable();
            $table->integer('cate_id')->nullable();
            $table->integer('type_id')->nullable();
            $table->integer('team_id')->nullable();
            $table->integer('open_by')->nullable();
            $table->integer('user_id')->nullable();
            $table->integer('pin')->nullable();
            $table->string('title', 250)->nullable();
            $table->longText('content')->nullable();
            $table->integer('status')->nullable();
            $table->integer('changed')->nullable();
            $table->integer('created')->nullable();
        });

        Schema::create('support_types', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('id_secure', 32)->nullable();
            $table->string('name', 150)->nullable();
            $table->string('icon', 150)->nullable();
            $table->string('color', 150)->nullable();
            $table->integer('status')->nullable();
            $table->integer('changed')->nullable();
            $table->integer('created')->nullable();
        });

        Schema::create('teams', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('id_secure', 32)->nullable();
            $table->string('name', 50)->nullable();
            $table->integer('owner')->nullable();
            $table->longText('permissions')->nullable();
            $table->longText('data')->nullable();
        });

        Schema::create('team_members', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('id_secure', 32)->nullable();
            $table->integer('uid')->nullable();
            $table->integer('team_id')->nullable();
            $table->longText('permissions')->nullable();
            $table->string('invite_token', 50)->nullable();
            $table->string('pending', 255)->nullable();
            $table->integer('status')->nullable();
        });

        DB::table('plans')->insert([
            'id' => 2,
            'id_secure' => substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 10),
            'name' => 'WhatsApp Bulk Plan',
            'desc' => 'Perfect for WhatsApp automation beginners',
            'type' => 1,
            'price' => 350,
            'trial_day' => 7,
            'free_plan' => 1,
            'featured' => 1,
            'position' => 2,
            'permissions' => '[
                {"key":"credits","label":"Credits","value":"100000"},
                {"key":"ai_word_credits","label":"Ai Word Credits","value":"1000"},
                {"key":"appchannels","label":"Channels","value":"1"},
                {"key":"max_channels","label":"Max channels","value":"-1"},
                {"key":"channel_calculate_by","label":"Channel Calculate By","value":"1"},
                {"key":"appchannels.appchannelwhatsappunofficial","label":"WhatsApp Unofficial","value":"1"},
                {"key":"appwhatsappprofileinfo","label":"Profile Info","value":"1"},
                {"key":"appwhatsappreport","label":"Reports","value":"1"},
                {"key":"appwhatsappchat","label":"Live Chat","value":"1"},
                {"key":"appwhatsappbulk","label":"Bulk campaigns","value":"1"},
                {"key":"appwhatsappaismartreply","label":"AI Smart Reply","value":"1"},
                {"key":"appwhatsappautoreply","label":"Auto Reply","value":"1"},
                {"key":"appwhatsappchatbot","label":"Chatbot","value":"1"},
                {"key":"appwhatsappcontact","label":"Contacts","value":"1"},
                {"key":"appwhatsappparticipantsexport","label":"Export participants","value":"1"},
                {"key":"appwhatsappapi","label":"REST API","value":"1"},
                {"key":"whatsapp_chatbot_item_limit","label":"Chatbot item limit per account","value":"5"},
                {"key":"whatsapp_bulk_max_contact_group","label":"Maximum contact groups","value":"5"},
                {"key":"whatsapp_bulk_max_phone_numbers","label":"Maximum phone numbers per contact group","value":"10000"},
                {"key":"whatsapp_message_per_month","label":"Monthly WhatsApp message limit","value":"10000"},
                {"key":"appaicontents","label":"AI Contents","value":"1"},
                {"key":"appsupport","label":"Support","value":"1"}
            ]',

            'data' => null,
            'status' => 1,
            'changed' => time(),
            'created' => time(),
        ]);

        Schema::create('whatsapp_ai_smart_reply', function (Blueprint $table) {
            $table->increments('id');
            $table->string('id_secure', 191)->nullable()->unique();
            $table->integer('team_id')->nullable()->index();
            $table->string('instance_id', 191)->nullable()->index();
            $table->mediumText('prompt')->nullable();
            $table->mediumText('fallback_caption')->nullable();
            $table->text('except')->nullable();
            $table->integer('delay')->default(1);
            $table->integer('send_to')->default(1);
            $table->integer('max_length')->default(120);
            $table->integer('sent')->default(0);
            $table->integer('failed')->default(0);
            $table->integer('status')->default(1);
            $table->integer('changed')->nullable();
            $table->integer('created')->nullable();
        });

        Schema::create('whatsapp_autoresponder', function (Blueprint $table) {
            $table->increments('id');
            $table->text('id_secure')->nullable();
            $table->integer('team_id')->nullable();
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

        Schema::create('whatsapp_chatbot', function (Blueprint $table) {
            $table->increments('id');
            $table->text('id_secure')->nullable();
            $table->text('name')->nullable();
            $table->text('keywords')->nullable();
            $table->text('instance_id')->nullable();
            $table->integer('team_id')->nullable();
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

        Schema::create('whatsapp_contacts', function (Blueprint $table) {
            $table->increments('id');
            $table->string('id_secure', 32)->nullable();
            $table->integer('team_id')->nullable();
            $table->string('name')->nullable();
            $table->integer('status')->nullable();
            $table->integer('changed')->nullable();
            $table->integer('created')->nullable();
        });

        Schema::create('whatsapp_phone_numbers', function (Blueprint $table) {
            $table->increments('id');
            $table->string('id_secure', 15)->nullable();
            $table->integer('team_id')->nullable();
            $table->integer('pid')->nullable();
            $table->string('phone', 50)->nullable();
            $table->text('params')->nullable();
        });

        Schema::create('whatsapp_schedules', function (Blueprint $table) {
            $table->increments('id');
            $table->string('id_secure', 32)->nullable();
            $table->integer('team_id')->nullable();
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
            $table->integer('created')->nullable();
        });

        Schema::create('whatsapp_sessions', function (Blueprint $table) {
            $table->increments('id');
            $table->string('id_secure', 32)->nullable();
            $table->integer('team_id')->nullable();
            $table->string('instance_id')->nullable();
            $table->longText('data')->nullable();
            $table->integer('status')->nullable();
        });

        Schema::create('whatsapp_stats', function (Blueprint $table) {
            $table->increments('id');
            $table->string('id_secure', 32)->nullable();
            $table->integer('team_id')->nullable();
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

        Schema::create('whatsapp_template', function (Blueprint $table) {
            $table->increments('id');
            $table->string('id_secure', 32)->nullable();
            $table->integer('team_id')->nullable();
            $table->integer('type')->nullable();
            $table->string('name')->nullable();
            $table->longText('data')->nullable();
            $table->integer('changed')->nullable();
            $table->integer('created')->nullable();
        });

        Schema::create('whatsapp_webhook', function (Blueprint $table) {
            $table->increments('id');
            $table->text('id_secure')->nullable();
            $table->integer('team_id')->nullable();
            $table->text('instance_id')->nullable();
            $table->text('webhook_url')->nullable();
            $table->integer('status')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('team_members');
        Schema::dropIfExists('teams');
        Schema::dropIfExists('support_types');
        Schema::dropIfExists('support_tickets');
        Schema::dropIfExists('support_map_labels');
        Schema::dropIfExists('support_labels');
        Schema::dropIfExists('support_comments');
        Schema::dropIfExists('support_categories');
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('proxies');
        Schema::dropIfExists('plans');
        Schema::dropIfExists('payment_subscriptions');
        Schema::dropIfExists('payment_manual');
        Schema::dropIfExists('payment_history');
        Schema::dropIfExists('payment_getways');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('options');
        Schema::dropIfExists('notification_manual');
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('module_statuses');
        Schema::dropIfExists('migrations');
        Schema::dropIfExists('language_items');
        Schema::dropIfExists('languages');
        Schema::dropIfExists('jobs');
        Schema::dropIfExists('groups');
        Schema::dropIfExists('files');
        Schema::dropIfExists('credit_usages');
        Schema::dropIfExists('coupons');
        Schema::dropIfExists('captions');
        Schema::dropIfExists('cache_locks');
        Schema::dropIfExists('cache');
        Schema::dropIfExists('article_tags');
        Schema::dropIfExists('article_map_tags');
        Schema::dropIfExists('article_categories');
        Schema::dropIfExists('articles');
        Schema::dropIfExists('accounts');
        Schema::dropIfExists('ai_templates');
        Schema::dropIfExists('ai_prompts');
        Schema::dropIfExists('ai_posts');
        Schema::dropIfExists('ai_categories');
        Schema::dropIfExists('affiliate_withdrawal');
        Schema::dropIfExists('affiliate_info');
        Schema::dropIfExists('affiliate');
        Schema::dropIfExists('addons');
        Schema::dropIfExists('users');
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
