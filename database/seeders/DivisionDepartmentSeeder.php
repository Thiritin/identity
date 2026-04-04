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
     * Number of test accounts with known credentials.
     * Password for all test accounts: "password"
     */
    private const TEST_ACCOUNT_COUNT = 5;

    /**
     * Division and department structure.
     * Lead values use placeholder keys (e.g. "lead_a") so the same person
     * can appear as director of multiple departments, mirroring real org charts.
     */
    private const STRUCTURE = [
        'Finance & Legal' => [
            'Registration' => 'lead_a',
            'Registration Software Development' => 'lead_b',
            'Contract Management & Hotel Relations' => 'lead_c',
            'Charity' => 'lead_d',
            'Legal & Compliance' => 'lead_e',
            'Accounting' => 'lead_a',
            'Funding & Cooperations' => 'lead_f',
            'Awareness & Inclusion' => 'lead_g',
            'Security' => 'lead_e',
        ],
        'Design & Operations' => [
            'Production Management' => 'lead_h',
            'Programming' => 'lead_i',
            'Stage' => 'lead_h',
            "Dealers' Den" => 'lead_j',
            'Art Show' => 'lead_k',
            'Logistics' => 'lead_l',
            'ConOps' => 'lead_m',
            'Telecommunications' => 'lead_n',
            'IT Infrastruktur' => 'lead_o',
            'Fursuit Support' => 'lead_p',
            'Fursuit Photoshoot' => 'lead_q',
            'Summerboat' => 'lead_r',
            'Opening Ceremony' => 'lead_s',
            'Closing Ceremony' => 'lead_t',
            'Sponsor Gifts' => 'lead_u',
            'Dances' => 'lead_v',
            'Dance Competition' => 'lead_w',
            'Dance Visuals' => 'lead_x',
            'Fursuit Theater' => 'lead_y',
            'Theming & Experience' => 'lead_z',
            'Dead Dog Party' => 'lead_aa',
            'Guest of Honor' => 'lead_ab',
            'Gaming Corner' => 'lead_ac',
            'VR' => 'lead_ad',
            'EF Prime' => 'lead_ae',
            'Puppet Show' => 'lead_af',
            'Video & Screen Operations' => 'lead_ag',
        ],
        'Staff & Organization' => [
            'People & Culture' => 'lead_j',
            'Critter Operations' => 'lead_j',
            'Staff Lounge' => 'lead_ah',
            'Internal Coordination' => 'lead_ai',
            'Statistics' => 'lead_aj',
            'IT Operations' => 'lead_o',
        ],
        'Marketing & Public Relations' => [
            'Marketing & Communication' => 'lead_e',
            'Website' => 'lead_ak',
            'Local & Public Affairs' => 'lead_al',
            'Brand & Merchandising' => 'lead_am',
            'Press & Media Relations' => 'lead_an',
            'Mascot' => 'lead_ao',
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

        $testAccounts = $this->createTestAccounts($staffGroup);
        $leadUserCache = [];

        // Pre-assign test accounts to a few lead slots so they appear in the directory
        $leadKeys = collect(self::STRUCTURE)->flatMap(fn ($depts) => array_values($depts))->unique()->values();
        $testLeadKeys = $leadKeys->random(min(self::TEST_ACCOUNT_COUNT, $leadKeys->count()));
        foreach ($testLeadKeys as $i => $key) {
            if (isset($testAccounts[$i])) {
                $leadUserCache[$key] = $testAccounts[$i];
            }
        }

        foreach (self::STRUCTURE as $divisionName => $departments) {
            $division = Group::create([
                'type' => GroupTypeEnum::Division,
                'name' => $divisionName,
                'parent_id' => $root->id,
            ]);

            // Attach a division director (first department lead doubles as division director)
            $firstLeadKey = reset($departments);
            $divisionDirector = $this->resolveUser($firstLeadKey, $leadUserCache);
            $division->users()->attach($divisionDirector->id, [
                'level' => GroupUserLevel::DivisionDirector,
                'can_manage_members' => true,
            ]);
            $this->ensureInStaffGroup($staffGroup, $divisionDirector);

            foreach ($departments as $departmentName => $leadKey) {
                $department = Group::create([
                    'type' => GroupTypeEnum::Department,
                    'name' => $departmentName,
                    'parent_id' => $division->id,
                ]);

                // Attach department director
                $lead = $this->resolveUser($leadKey, $leadUserCache);
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
        $this->command->info('Test accounts (password: "password"):');
        foreach ($testAccounts as $user) {
            $this->command->line("  - {$user->name} <{$user->email}>");
        }
    }

    /**
     * @return array<int, User>
     */
    private function createTestAccounts(?Group $staffGroup): array
    {
        $users = [];
        for ($i = 0; $i < self::TEST_ACCOUNT_COUNT; $i++) {
            $name = fake()->unique()->firstName();
            $user = User::factory()->create([
                'name' => $name,
                'email' => strtolower($name) . '@eurofurence.localhost',
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
            ]);
            $users[] = $user;
            $this->ensureInStaffGroup($staffGroup, $user);
        }

        return $users;
    }

    /**
     * @param  array<string, User>  $cache
     */
    private function resolveUser(string $key, array &$cache): User
    {
        if (isset($cache[$key])) {
            return $cache[$key];
        }

        $user = User::factory()->create();
        $cache[$key] = $user;

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
