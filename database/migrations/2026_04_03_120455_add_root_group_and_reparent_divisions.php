<?php

use App\Models\Group;
use Illuminate\Database\Migrations\Migration;

return new class() extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Group::where('type', 'root')->exists()) {
            return;
        }

        $root = Group::create([
            'name' => 'Board of Directors',
            'type' => 'root',
            'system_name' => 'board',
        ]);

        Group::where('type', 'division')
            ->whereNull('parent_id')
            ->update(['parent_id' => $root->id]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $root = Group::where('system_name', 'board')->first();
        if ($root) {
            Group::where('parent_id', $root->id)
                ->where('type', 'division')
                ->update(['parent_id' => null]);
            $root->delete();
        }
    }
};
