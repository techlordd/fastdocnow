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
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->text('content');
            $table->enum('type', ['text', 'image', 'video', 'audio', 'file', 'system'])->default('text');
            $table->json('attachments')->nullable();
            $table->foreignId('reply_to_id')->nullable()->constrained('messages')->onDelete('set null');
            $table->boolean('is_edited')->default(false);
            $table->timestamp('edited_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['conversation_id', 'created_at']);
            $table->index(['user_id', 'created_at']);
            $table->index(['type', 'created_at']);
            $table->index('reply_to_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
