<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('tontine_members_order', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tontine_id')->constrained();
            $table->foreignId('member_id')->constrained("tontine_members");
            $table->integer('position')->default(0);
            $table->boolean('colleted')->default(false);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('tontine_members_order');
    }
};
