<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    /**
     * Inlined from EurofurenceEdition — this class will be deleted after migration.
     */
    private const EDITIONS = [
        1 => 1995, 2 => 1996, 3 => 1997, 4 => 1998, 5 => 1999,
        6 => 2000, 7 => 2001, 8 => 2002, 9 => 2003, 10 => 2004,
        11 => 2005, 12 => 2006, 13 => 2007, 14 => 2008, 15 => 2009,
        16 => 2010, 17 => 2011, 18 => 2012, 19 => 2013, 20 => 2014,
        21 => 2015, 22 => 2016, 23 => 2017, 24 => 2018, 25 => 2019,
        26 => 2022, 27 => 2023, 28 => 2024, 29 => 2025,
    ];

    public function up(): void
    {
        $users = DB::table('users')
            ->whereNotNull('first_eurofurence')
            ->orWhereNotNull('first_year_staff')
            ->get(['id', 'first_eurofurence', 'first_year_staff']);

        foreach ($users as $user) {
            if ($user->first_eurofurence) {
                $year = self::EDITIONS[$user->first_eurofurence]
                    ?? (2025 + ($user->first_eurofurence - 29));
                $convention = DB::table('conventions')->where('year', $year)->first();
                if ($convention) {
                    DB::table('convention_attendee')->updateOrInsert(
                        ['user_id' => $user->id, 'convention_id' => $convention->id],
                        ['is_attended' => true, 'updated_at' => now()]
                    );
                }
            }

            if ($user->first_year_staff) {
                $convention = DB::table('conventions')->where('year', $user->first_year_staff)->first();
                if ($convention) {
                    DB::table('convention_attendee')->updateOrInsert(
                        ['user_id' => $user->id, 'convention_id' => $convention->id],
                        ['is_staff' => true, 'updated_at' => now()]
                    );
                }
            }
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['first_eurofurence', 'first_year_staff']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedSmallInteger('first_eurofurence')->nullable();
            $table->unsignedSmallInteger('first_year_staff')->nullable();
        });
    }
};
