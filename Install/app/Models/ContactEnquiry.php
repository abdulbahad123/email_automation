<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

class ContactEnquiry extends Model
{
    protected $table = 'contact_enquiries';

    protected $fillable = [
        'id_secure',
        'name',
        'email',
        'phone',
        'subject',
        'message',
        'ip_address',
        'status',
        'created',
        'changed',
    ];

    /**
     * Ensure the database table exists on any environment without needing manual migration.
     */
    public static function checkTable(): void
    {
        try {
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
                    $table->integer('status')->default(0); // 0 = unread, 1 = read, 2 = replied
                    $table->bigInteger('created')->nullable();
                    $table->bigInteger('changed')->nullable();
                    $table->timestamps();
                });
            }
        } catch (\Throwable $e) {
            // Silently continue if already exists or permission restricted
        }
    }
}
