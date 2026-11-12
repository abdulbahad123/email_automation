<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('options')) {
            $updates = [
                'landing_hero_title' => "WhatsApp operations,<br><span class=\"text-[#059669]\">campaigns,</span> and<br>automation",
                'landing_stack_title' => 'Whatsappbulk <span class="text-[#059669]">Workspace</span>',
                'landing_stack_subtitle' => 'Everything you need to run WhatsApp at scale',
                'landing_stat1_label' => 'Operational modules included',
                'landing_stat2_label' => 'Unified WhatsApp workflow',
                'landing_stat3_label' => 'Automation & reply coverage',
            ];

            foreach ($updates as $name => $val) {
                if (DB::table('options')->where('name', $name)->exists()) {
                    DB::table('options')->where('name', $name)->update(['value' => $val]);
                } else {
                    DB::table('options')->insert(['name' => $name, 'value' => $val]);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
    }
};