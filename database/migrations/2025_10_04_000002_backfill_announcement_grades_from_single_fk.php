<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Copy existing gradelvl_id into pivot rows
        $rows = DB::table('announcements')
            ->select('id as announcement_id', 'gradelvl_id')
            ->whereNotNull('gradelvl_id')
            ->get();

        foreach ($rows as $r) {
            // Insert ignore-like behavior to avoid duplicates if re-run
            $exists = DB::table('announcement_gradelvl')
                ->where('announcement_id', $r->announcement_id)
                ->where('gradelvl_id', $r->gradelvl_id)
                ->exists();

            if (!$exists) {
                DB::table('announcement_gradelvl')->insert([
                    'announcement_id' => $r->announcement_id,
                    'gradelvl_id'     => $r->gradelvl_id,
                    'created_at'      => now(),
                    'updated_at'      => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        // Non-destructive rollback: do nothing (or you could delete all pivot rows)
        // DB::table('announcement_gradelvl')->truncate();
    }
};
