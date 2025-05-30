<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('tontine_invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tontine_id')->constrained('tontines')->cascadeOnDelete();
            $table->foreignId('invited_by_id')->constrained('users');
            $table->foreignId('invited_user_id')->constrained('users');
            $table->string('status')->default('pending');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('tontine_invitations');
    }
};
