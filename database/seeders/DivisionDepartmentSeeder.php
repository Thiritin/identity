<?php

namespace Database\Seeders;

use App\Enums\GroupTypeEnum;
use App\Enums\GroupUserLevel;
use App\Models\Group;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DivisionDepartmentSeeder extends Seeder
{
    /**
     * Known test accounts seeded as leads in specific departments.
     * Password for all test accounts: "password"
     */
    private const TEST_ACCOUNTS = [
        ['name' => 'Loewi', 'email' => 'loewi@eurofurence.localhost'],
        ['name' => 'Dingo', 'email' => 'dingo@eurofurence.localhost'],
        ['name' => 'Pattarchus', 'email' => 'pattarchus@eurofurence.localhost'],
        ['name' => 'Gingerwolf', 'email' => 'gingerwolf@eurofurence.localhost'],
        ['name' => 'BigBlueFox', 'email' => 'bigbluefox@eurofurence.localhost'],
    ];

    private const STRUCTURE = [
        'Finance & Legal' => [
            'Registration' => 'Loewi',
            'Registration Software Development' => 'Jumpy',
            'Contract Management & Hotel Relations' => 'Nightfox',
            'Charity' => 'Sniffer',
            'Legal & Compliance' => 'Dingo',
            'Accounting' => 'Loewi',
            'Funding & Cooperations' => 'Tarian',
            'Awareness & Inclusion' => 'Windmelodie',
            'Security' => 'Dingo',
        ],
        'Design & Operations' => [
            'Production Management' => 'garra',
            'Programming' => 'Akulatraxxs',
            'Stage' => 'garra',
            "Dealers' Den" => 'Pattarchus',
            'Art Show' => 'Cairy',
            'Logistics' => 'Tanor',
            'ConOps' => 'Ericmon',
            'Telecommunications' => 'Winged Neko',
            'IT Infrastruktur' => 'Gingerwolf',
            'Fursuit Support' => 'Jake R',
            'Fursuit Photoshoot' => 'Shorty',
            'Summerboat' => 'Hunter',
            'Opening Ceremony' => 'Wolfenden',
            'Closing Ceremony' => 'Cami Roo',
            'Sponsor Gifts' => 'Wawik',
            'Dances' => 'CaidaTigre',
            'Dance Competition' => 'Koltas',
            'Dance Visuals' => 'Krauti',
            'Fursuit Theater' => 'Yeeny_Martini',
            'Theming & Experience' => 'Snow-wolf',
            'Dead Dog Party' => 'ShadeWolf',
            'Guest of Honor' => 'Arah',
            'Gaming Corner' => 'Cintas Fox',
            'VR' => 'Wikk',
            'EF Prime' => 'BigBlueFox',
            'Puppet Show' => 'Cheetah',
            'Video & Screen Operations' => 'Lykantrp',
        ],
        'Staff & Organization' => [
            'People & Culture' => 'Pattarchus',
            'Critter Operations' => 'Pattarchus',
            'Staff Lounge' => 'Mesur',
            'Internal Coordination' => 'Hai',
            'Statistics' => 'Yote',
            'IT Operations' => 'Gingerwolf',
        ],
        'Marketing & Public Relations' => [
            'Marketing & Communication' => 'Dingo',
            'Website' => 'draconigen',
            'Local & Public Affairs' => 'Mystifur',
            'Brand & Merchandising' => 'Vulnir',
            'Press & Media Relations' => 'BlueBerry',
            'Mascot' => 'Panromir',
        ],
    ];

    public function run(): void
    {
        if (app()->environment() !== 'local') {
            $this->command->warn('DivisionDepartmentSeeder only runs in local environment.');

            return;
        }

        $root = Group::firstOrCreate([
            'system_name' => 'board',
        ], [
            'type' => GroupTypeEnum::Root,
            'name' => 'Board of Directors',
            'slug' => 'board-of-directors',
        ]);

        $staffGroup = Group::where('system_name', 'staff')->first();

        $testAccountUsers = $this->createTestAccounts($staffGroup);
        $leadUserCache = [];

        foreach (self::STRUCTURE as $divisionName => $departments) {
            $division = Group::create([
                'type' => GroupTypeEnum::Division,
                'name' => $divisionName,
                'parent_id' => $root->id,
            ]);

            // Attach a division director (first department lead doubles as division director)
            $firstLeadName = reset($departments);
            $divisionDirector = $this->resolveUser($firstLeadName, $testAccountUsers, $leadUserCache);
            $division->users()->attach($divisionDirector->id, [
                'level' => GroupUserLevel::DivisionDirector,
                'can_manage_members' => true,
            ]);
            $this->ensureInStaffGroup($staffGroup, $divisionDirector);

            foreach ($departments as $departmentName => $leadName) {
                $department = Group::create([
                    'type' => GroupTypeEnum::Department,
                    'name' => $departmentName,
                    'parent_id' => $division->id,
                ]);

                // Attach department director
                $lead = $this->resolveUser($leadName, $testAccountUsers, $leadUserCache);
                $department->users()->attach($lead->id, [
                    'level' => GroupUserLevel::Director,
                    'can_manage_members' => true,
                    'title' => 'Department Lead',
                ]);
                $this->ensureInStaffGroup($staffGroup, $lead);

                // Add 2-6 random staff members
                $memberCount = fake()->numberBetween(2, 6);
                $members = User::factory()->count($memberCount)->create();

                foreach ($members as $member) {
                    $department->users()->attach($member->id, [
                        'level' => GroupUserLevel::Member,
                        'can_manage_members' => false,
                    ]);
                    $this->ensureInStaffGroup($staffGroup, $member);
                }
            }
        }

        $this->command->info('Seeded ' . count(self::STRUCTURE) . ' divisions with ' . collect(self::STRUCTURE)->flatten()->count() . ' departments.');
    }

    /**
     * @return array<string, User>
     */
    private function createTestAccounts(?Group $staffGroup): array
    {
        $users = [];
        foreach (self::TEST_ACCOUNTS as $account) {
            $user = User::firstOrCreate(
                ['email' => $account['email']],
                [
                    'name' => $account['name'],
                    'email_verified_at' => now(),
                    'password' => Hash::make('password'),
                ]
            );
            $users[$account['name']] = $user;
            $this->ensureInStaffGroup($staffGroup, $user);
        }

        return $users;
    }

    /**
     * @param  array<string, User>  $testAccounts
     * @param  array<string, User>  $cache
     */
    private function resolveUser(string $name, array $testAccounts, array &$cache): User
    {
        if (isset($testAccounts[$name])) {
            return $testAccounts[$name];
        }

        if (isset($cache[$name])) {
            return $cache[$name];
        }

        $user = User::factory()->create([
            'name' => $name,
        ]);
        $cache[$name] = $user;

        return $user;
    }

    private function ensureInStaffGroup(?Group $staffGroup, User $user): void
    {
        if (! $staffGroup) {
            return;
        }

        if (! $staffGroup->users()->where('user_id', $user->id)->exists()) {
            $staffGroup->users()->attach($user->id, [
                'level' => GroupUserLevel::Member,
                'can_manage_members' => false,
            ]);
        }
    }
}
