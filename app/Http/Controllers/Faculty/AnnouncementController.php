<?php

namespace App\Http\Controllers\Faculty;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\Gradelvl;
use App\Models\Schedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AnnouncementController extends Controller
{
    public function store(Request $request)
    {
        $user = Auth::user();
        $canSeeAll = ($user->username === 'faculty');

        $allowedGradelvlIds = $canSeeAll
            ? Gradelvl::pluck('id')
            : $this->myGradelvlIds($user->faculty_id);

        $validated = $request->validate([
            'title'          => 'required|string|max:255',
            'content'        => 'nullable|string',
            'date_of_event'  => 'nullable|date',
            'deadline'       => 'nullable|date',
            'gradelvl_ids'   => 'array',
            'gradelvl_ids.*' => 'integer|exists:gradelvls,id',
        ]);

        $selected = collect($validated['gradelvl_ids'] ?? []);
        if ($selected->isNotEmpty() && !$canSeeAll) {
            $selected = $selected->intersect($allowedGradelvlIds);
        }

        $a = new Announcement();
        $a->title         = $validated['title'];
        $a->content       = $validated['content'] ?? null;
        $a->date_of_event = $validated['date_of_event'] ?? null;
        $a->deadline      = $validated['deadline'] ?? null;
        $a->save();

        if ($selected->isNotEmpty()) {
            $a->gradelvls()->sync($selected->all());
        }

        return back()->with('success', 'Announcement created.');
    }

    public function update(Request $request, $id)
    {
        $user = Auth::user();
        $canSeeAll = ($user->username === 'faculty');

        $a = Announcement::with('gradelvls')->findOrFail($id);

        $allowedGradelvlIds = $canSeeAll
            ? Gradelvl::pluck('id')
            : $this->myGradelvlIds($user->faculty_id);

        $validated = $request->validate([
            'title'          => 'required|string|max:255',
            'content'        => 'nullable|string',
            'date_of_event'  => 'nullable|date',
            'deadline'       => 'nullable|date',
            'gradelvl_ids'   => 'array',
            'gradelvl_ids.*' => 'integer|exists:gradelvls,id',
        ]);

        $a->update([
            'title'         => $validated['title'],
            'content'       => $validated['content'] ?? null,
            'date_of_event' => $validated['date_of_event'] ?? null,
            'deadline'      => $validated['deadline'] ?? null,
        ]);

        $selected = collect($validated['gradelvl_ids'] ?? []);
        if ($selected->isNotEmpty() && !$canSeeAll) {
            $selected = $selected->intersect($allowedGradelvlIds);
        }

        if ($selected->isNotEmpty()) {
            $a->gradelvls()->sync($selected->all());
        } else {
            $a->gradelvls()->detach();
        }

        return back()->with('success', 'Announcement updated.');
    }

    public function destroy($id)
    {
        $user = Auth::user();
        $canSeeAll = ($user->username === 'faculty');

        $a = Announcement::with('gradelvls')->findOrFail($id);

        if (!$canSeeAll) {
            $myIds   = $this->myGradelvlIds($user->faculty_id);
            $isGlobal= $a->gradelvls->isEmpty();
            $overlap = $a->gradelvls->pluck('id')->intersect($myIds);
            if (!$isGlobal && $overlap->isEmpty()) {
                abort(403, 'You are not allowed to delete this announcement.');
            }
        }

        $a->gradelvls()->detach();
        $a->delete();

        return back()->with('success', 'Announcement deleted.');
    }

    private function myGradelvlIds($facultyId)
    {
        return Schedule::where('faculty_id', $facultyId)
            ->pluck('gradelvl_id')
            ->filter()
            ->unique()
            ->values();
    }
}
