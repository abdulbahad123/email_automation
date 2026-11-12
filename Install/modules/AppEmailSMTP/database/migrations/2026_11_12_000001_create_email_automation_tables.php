<?php

use Illuminate\Database\Migrations\Migration;
use Modules\AppEmailSMTP\Services\EmailSchemaService;

return new class extends Migration
{
    public function up(): void
    {
        EmailSchemaService::ensureTables();
    }

    public function down(): void
    {
        // Keep user campaign data intact on accidental rollback unless explicitly dropped.
    }
};

