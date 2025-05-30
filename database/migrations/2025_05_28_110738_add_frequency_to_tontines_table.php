<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('tontines', function (Blueprint $table) {
            $table->enum('frequency', ['daily', 'weekly', 'monthly', 'yearly'])
                  ->default('monthly')
                  ->after('status');
        });
    }

    public function down()
    {
        Schema::table('tontines', function (Blueprint $table) {
            $table->dropColumn('frequency');
        });
    }
};
