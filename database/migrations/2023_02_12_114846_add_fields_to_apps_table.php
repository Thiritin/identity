<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up()
    {
        Schema::table('apps', function (Blueprint $table) {
            $table->timestamp('starts_at')->nullable()->after('client_id');
            $table->timestamp('ends_at')->nullable()->after('starts_at');
            $table->boolean('public')->default(false)->after('ends_at');
            $table->boolean('featured')->default(false)->after('ends_at');
            $table->integer('priority')->unsigned()->default(1000)->after('public');
            $table->string('name')->nullable()->after('client_id');
            $table->string('description')->nullable()->after('name');
            $table->string('icon')->nullable()->after('description');
            $table->string('url')->nullable()->after('icon');
        });
    }

    public function down()
    {
        Schema::table('apps', function (Blueprint $table) {
            $table->dropColumn([
                'starts_at',
                'ends_at',
                'public',
                'featured',
                'priority',
                'name',
                'description',
                'icon',
                'url',
            ]);
        });
    }
};
