<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up()
    {
        Schema::table('groups', function (Blueprint $table) {
            $table->renameColumn('internal_name', 'slug');
            $table->dropColumn('type');
        });
        Schema::table('groups', function (Blueprint $table) {
            $table->enum('type', ['none', 'department'])->after('id')->index();
        });
        Schema::table('group_user', function (Blueprint $table) {
            $table->dropColumn(['authorization_level', 'is_director', 'title']);
            $table->enum('level', ['member', 'division_director', 'director', 'team_lead'])
                ->index()
                ->default('member')
                ->after('user_id');
            $table->boolean('can_manage_members')->default(false)->after('user_id');
        });
    }
};
