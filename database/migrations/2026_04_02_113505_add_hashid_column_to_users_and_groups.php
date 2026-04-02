<?php

use Hashids\Hashids;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('hashid', 16)->unique()->after('id')->nullable();
        });

        Schema::table('groups', function (Blueprint $table) {
            $table->string('hashid', 16)->unique()->after('id')->nullable();
        });

        // Populate existing users
        $userHashids = new Hashids(
            config('hashids.connections.user.salt'),
            config('hashids.connections.user.length'),
            config('hashids.connections.user.alphabet'),
        );

        DB::table('users')->orderBy('id')->each(function ($user) use ($userHashids) {
            DB::table('users')->where('id', $user->id)->update([
                'hashid' => $userHashids->encode($user->id),
            ]);
        });

        // Populate existing groups
        $groupHashids = new Hashids(
            config('hashids.connections.group.salt'),
            config('hashids.connections.group.length'),
            config('hashids.connections.group.alphabet'),
        );

        DB::table('groups')->orderBy('id')->each(function ($group) use ($groupHashids) {
            DB::table('groups')->where('id', $group->id)->update([
                'hashid' => $groupHashids->encode($group->id),
            ]);
        });

    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('hashid');
        });

        Schema::table('groups', function (Blueprint $table) {
            $table->dropColumn('hashid');
        });
    }
};
