<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Concerns\InteractsWithStudyGroups;
use App\Http\Controllers\Controller;
use App\Models\GroupRating;
use App\Models\StudyGroup;
use App\Services\Notifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * GROUP RATINGS. Members rate the groups they belong to (1-5 stars + comment).
 * You can't rate a group you own, and you can change your rating any time.
 */
class RatingController extends Controller
{
    use InteractsWithStudyGroups;

    public function index(Request $request): Response
    {
        $user = $request->user();

        $groups = $this->memberGroups($request)->map(function (StudyGroup $g) use ($user) {
            $ratings = $g->ratings()->with('user:id,name')->latest('id')->get();
            $mine = $ratings->firstWhere('user_id', $user->id);

            return [
                'id' => $g->id,
                'name' => $g->name,
                'subject' => $g->subject->name,
                'is_owner' => $g->owner_id === $user->id,
                'average' => $ratings->count() ? round($ratings->avg('stars'), 1) : null,
                'count' => $ratings->count(),
                'my' => $mine ? ['stars' => $mine->stars, 'comment' => $mine->comment ?? ''] : null,
                'reviews' => $ratings->take(5)->map(fn ($r) => [
                    'id' => $r->id,
                    'author' => $r->user->name,
                    'stars' => $r->stars,
                    'comment' => $r->comment,
                    'date' => $r->updated_at->format('M j, Y'),
                ])->values(),
            ];
        })->values();

        return Inertia::render('ratings', ['groups' => $groups]);
    }

    public function store(Request $request, StudyGroup $group, Notifier $notifier): RedirectResponse
    {
        $user = $request->user();
        $this->authorizeMember($request, $group);
        abort_if($group->owner_id === $user->id, 403, 'You cannot rate your own group.');

        $data = $request->validate([
            'stars' => ['required', 'integer', 'between:1,5'],
            'comment' => ['nullable', 'string', 'max:300'],
        ]);

        // One rating per member per group: create it, or update it if it exists.
        $rating = GroupRating::updateOrCreate(
            ['study_group_id' => $group->id, 'user_id' => $user->id],
            ['stars' => $data['stars'], 'comment' => $data['comment'] ?? null],
        );

        if ($rating->wasRecentlyCreated) {
            $notifier->toUser($group->owner, 'rating', "{$group->name} got a new rating", "{$user->name} gave {$data['stars']} star(s).", '/ratings', $group->id);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Thanks! Your rating was saved.']);

        return back();
    }
}
