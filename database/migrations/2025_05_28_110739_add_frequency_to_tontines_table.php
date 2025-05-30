<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('tontines', function (Blueprint $table) {
            $table->date('start_date')
                  ->nullable()
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
