<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            // Notification settings
            $table->boolean('sms_notifications')->default(false)->after('email_notifications');
            $table->string('notification_email')->nullable()->after('sms_notifications');
            $table->string('notification_phone')->nullable()->after('notification_email');
            $table->enum('notification_frequency', ['instant', 'hourly', 'daily'])->default('instant')->after('notification_phone');
            $table->time('quiet_hours_start')->nullable()->after('notification_frequency');
            $table->time('quiet_hours_end')->nullable()->after('quiet_hours_start');
            
            // Theme settings
            $table->string('theme_color', 7)->default('#6600ff')->after('theme');
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'sms_notifications',
                'notification_email',
                'notification_phone',
                'notification_frequency',
                'quiet_hours_start',
                'quiet_hours_end',
                'theme_color'
            ]);
        });
    }
};
