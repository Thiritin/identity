<?php

namespace App\Console\Commands;

use App\Models\Group;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixTeamMembershipsCommand extends Command
{
    protected $signature = 'fix:team-memberships {--dry-run : Show what would be done without making changes}';

    protected $description = 'Ensures all team members are also members of their parent department and the staff group';

    public function handle()
    {
        $isDryRun = $this->option('dry-run');
        
        if ($isDryRun) {
            $this->info('DRY RUN MODE - No changes will be made');
        }

        // Get staff group
        $staffGroup = Group::where('system_name', 'staff')->first();
        if (!$staffGroup) {
            $this->error('Staff group not found!');
            return 1;
        }

        $this->info("Staff group found: {$staffGroup->name} (ID: {$staffGroup->id})");

        // Get all team members that need to be processed
        $teamMemberships = DB::table('groups as teams')
            ->join('groups as departments', 'teams.parent_id', '=', 'departments.id')
            ->join('group_user as team_membership', 'team_membership.group_id', '=', 'teams.id')
            ->join('users', 'users.id', '=', 'team_membership.user_id')
            ->where('teams.type', 'team')
            ->where('departments.type', 'department')
            ->select([
                'teams.id as team_id',
                'teams.name as team_name',
                'departments.id as dept_id', 
                'departments.name as dept_name',
                'users.id as user_id',
                'users.name as user_name'
            ])
            ->get();

        $this->info("Found {$teamMemberships->count()} team memberships to process");

        $addedToDepartment = 0;
        $addedToStaff = 0;
        $currentTeam = null;

        foreach ($teamMemberships as $membership) {
            // Show team header when we switch teams
            if ($currentTeam !== $membership->team_name) {
                $this->info("Processing team: {$membership->team_name} -> Department: {$membership->dept_name}");
                $currentTeam = $membership->team_name;
            }

            // Check if user is member of department
            $isDepartmentMember = DB::table('group_user')
                ->where('group_id', $membership->dept_id)
                ->where('user_id', $membership->user_id)
                ->exists();
            
            if (!$isDepartmentMember) {
                $this->warn("  User {$membership->user_name} (ID: {$membership->user_id}) is NOT in department {$membership->dept_name}");
                
                if (!$isDryRun) {
                    // Add user to department with 'member' level
                    DB::table('group_user')->insertOrIgnore([
                        'group_id' => $membership->dept_id,
                        'user_id' => $membership->user_id,
                        'level' => 'member'
                    ]);
                    $this->info("    ✓ Added to department");
                } else {
                    $this->info("    → Would add to department");
                }
                $addedToDepartment++;
            } else {
                $this->info("  User {$membership->user_name} is already in department {$membership->dept_name}");
            }

            // Check if user is member of staff
            $isStaffMember = DB::table('group_user')
                ->where('group_id', $staffGroup->id)
                ->where('user_id', $membership->user_id)
                ->exists();
            
            if (!$isStaffMember) {
                $this->warn("  User {$membership->user_name} (ID: {$membership->user_id}) is NOT in staff group");
                
                if (!$isDryRun) {
                    // Add user to staff with 'member' level
                    DB::table('group_user')->insertOrIgnore([
                        'group_id' => $staffGroup->id,
                        'user_id' => $membership->user_id,
                        'level' => 'member'
                    ]);
                    $this->info("    ✓ Added to staff");
                } else {
                    $this->info("    → Would add to staff");
                }
                $addedToStaff++;
            } else {
                $this->info("  User {$membership->user_name} is already in staff group");
            }
        }

        $this->info('Summary:');
        if ($isDryRun) {
            $this->info("Would add {$addedToDepartment} users to their departments");
            $this->info("Would add {$addedToStaff} users to staff group");
            $this->info('Run without --dry-run to make changes');
        } else {
            $this->info("Added {$addedToDepartment} users to their departments");
            $this->info("Added {$addedToStaff} users to staff group");
        }

        return 0;
    }
}