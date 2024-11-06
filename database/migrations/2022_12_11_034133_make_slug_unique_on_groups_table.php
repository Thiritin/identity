<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up()
    {
        Schema::table('groups', function (Blueprint $table) {
            $table->string('slug')->unique()->index()->change();
        });
    }

    public function down()
    {
        Schema::table('groups', function (Blueprint $table) {
            $table->string('slug')->change();
        });
    }
};
