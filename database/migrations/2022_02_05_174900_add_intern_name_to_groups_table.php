<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddInternNameToGroupsTable extends Migration
{
    public function up()
    {
        Schema::table('groups', function (Blueprint $table) {
            $table->string('internal_name')->after('id')->unique();
            $table->json('name')->change();
            $table->json('description')->change();
        });
    }

    public function down()
    {
        Schema::table('groups', function (Blueprint $table) {
            $table->dropColumn('internal_name');
            $table->string('name')->change();
            $table->string('description')->change();
        });
    }
}
