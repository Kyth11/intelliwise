<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use Illuminate\Http\Request;

class AnnouncementController extends Controller
{
    /**
     * Admin-scoped: create announcement and attach grade levels.
     * (Dashboard view stays in AdminDashboardController.)
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'title'          => 'required|string|max:255',
            'content'        => 'nullable|string',
            'date_of_event'  => 'nullable|date',
            'deadline'       => 'nullable|date|after_or_equal:date_of_event',
            'gradelvl_ids'   => 'array',
            'gradelvl_ids.*' => 'nullable|exists:gradelvls,id',
        ]);

        $first = collect($data['gradelvl_ids'] ?? [])->filter()->first();

        $announcement = Announcement::create([
            'title'         => $data['title'],
            'content'       => $data['content'] ?? null,
            'date_of_event' => $data['date_of_event'] ?? null,
            'deadline'      => $data['deadline'] ?? null,
            'gradelvl_id'   => $first ?: null,
        ]);

        $ids = collect($data['gradelvl_ids'] ?? [])->filter()->unique()->values();
        $announcement->gradelvls()->sync($ids);

        return back()->with('success', 'Announcement created.');
    }

    public function update(Request $request, $id)
    {
        $announcement = Announcement::findOrFail($id);

        $data = $request->validate([
            'title'          => 'required|string|max:255',
            'content'        => 'nullable|string',
            'date_of_event'  => 'nullable|date',
            'deadline'       => 'nullable|date|after_or_equal:date_of_event',
            'gradelvl_ids'   => 'array',
            'gradelvl_ids.*' => 'nullable|exists:gradelvls,id',
        ]);

        $first = collect($data['gradelvl_ids'] ?? [])->filter()->first();

        $announcement->update([
            'title'         => $data['title'],
            'content'       => $data['content'] ?? null,
            'date_of_event' => $data['date_of_event'] ?? null,
            'deadline'      => $data['deadline'] ?? null,
            'gradelvl_id'   => $first ?: null,
        ]);

        $ids = collect($data['gradelvl_ids'] ?? [])->filter()->unique()->values();
        $announcement->gradelvls()->sync($ids);

        return back()->with('success', 'Announcement updated.');
    }

    public function destroy($id)
    {
        Announcement::findOrFail($id)->delete();
        return back()->with('success', 'Announcement deleted.');
    }
}
