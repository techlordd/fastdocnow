<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->foreignId('contact_id')->nullable()->after('created_by')->constrained('contacts')->onDelete('cascade');
            $table->index('contact_id');
        });
    }

    public function down()
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->dropForeign(['contact_id']);
            $table->dropColumn('contact_id');
        });
    }
};
