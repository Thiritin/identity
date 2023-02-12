<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateAppGroupPivotTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('app_group', function (Blueprint $table) {
            $table->unsignedBigInteger('app_id')->index();
            $table->foreign('app_id')->references('id')->on('apps')->onDelete('cascade');
            $table->unsignedBigInteger('group_id')->index();
            $table->foreign('group_id')->references('id')->on('groups')->onDelete('cascade');
            $table->primary(['app_id', 'group_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('app_group');
    }
}
