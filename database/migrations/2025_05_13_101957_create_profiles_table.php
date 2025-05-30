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
        Schema::create('profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('first_name');
            $table->string('second_name');
            $table->string('whatsapp')->nullable();
            $table->string('id_type')->nullable();
            $table->timestamp('id_verified_at')->nullable();
            $table->string('niu')->nullable();
            $table->timestamp('niu_verified_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->text('comment')->nullable();
            $table->string('status')->default('pending');
            $table->string('id_number')->nullable(); // Added
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('profiles');
    }
};
