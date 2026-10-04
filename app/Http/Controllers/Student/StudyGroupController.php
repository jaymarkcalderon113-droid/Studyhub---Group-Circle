<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\StoreStudyGroupRequest;
use App\Models\GroupMember;
use App\Models\StudentProfile;
use App\Models\StudyGroup;
use App\Models\Subject;
use App\Services\GroupMatcher;
use App\Services\Notifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/** Find Groups (matching), My Groups, create group, join requests. */
class StudyGroupController extends Controller
{
    /** FIND GROUPS: automatic matching. */
    public function find(Request $request, GroupMatcher $matcher): Response
    {
        $user = $request->user();
        $profile = $user->studentProfile;

        $request->validate([
            'subjects' => ['sometimes', 'array'],
            'subjects.*' => ['integer'],
            'skill_level' => ['sometimes', Rule::in(StudentProfile::SKILL_LEVELS)],
            'study_goal' => ['sometimes', Rule::in(StudentProfile::STUDY_GOALS)],
            'study_mode' => ['sometimes', Rule::in(StudentProfile::STUDY_MODES)],
        ]);

        // Use what the student typed in the form; otherwise fall back to their saved profile.
        $criteria = [
            'subjects' => array_map('intval', $request->input('subjects', $user->subjects()->pluck('subjects.id')->all())),
            'skill_level' => $request->input('skill_level', $profile?->skill_level ?? 'Beginner'),
            'study_goal' => $request->input('study_goal', $profile?->study_goal ?? 'Pass Exam'),
            'study_mode' => $request->input('study_mode', $profile?->study_mode ?? 'Online'),
        ];

        return Inertia::render('findgroups', [
            'results' => $matcher->match($user, $criteria),
            'filters' => $criteria,
            'subjectOptions' => Subject::orderBy('name')->get(['id', 'name']),
            'skillLevels' => StudentProfile::SKILL_LEVELS,
            'studyGoals' => StudentProfile::STUDY_GOALS,
            'studyModes' => StudentProfile::STUDY_MODES,
        ]);
    }

    /** MY GROUPS: groups I belong to + requests waiting for my answer. */
    public function index(Request $request): Response
    {
        $user = $request->user();

        $groups = StudyGroup::with('subject')
            ->withCount(['memberships as members_count' => fn ($q) => $q->where('status', GroupMember::APPROVED)])
            ->whereHas('memberships', fn ($q) => $q
                ->where('user_id', $user->id)
                ->where('status', GroupMember::APPROVED))
            ->latest()
            ->get()
            ->map(fn ($g) => [
                'id' => $g->id,
                'name' => $g->name,
                'subject' => $g->subject->name,
                'skill_level' => $g->skill_level,
                'study_mode' => $g->study_mode,
                'members' => $g->members_count,
                'max' => $g->max_members,
                'is_owner' => $g->owner_id === $user->id,
            ]);

        // Students who asked to join groups that I own.
        $requests = GroupMember::with(['user:id,name', 'group:id,name'])
            ->where('status', GroupMember::PENDING)
            ->whereHas('group', fn ($q) => $q->where('owner_id', $user->id))
            ->get()
            ->map(fn ($m) => [
                'id' => $m->id,
                'group_id' => $m->study_group_id,
                'group' => $m->group->name,
                'student' => $m->user->name,
            ]);

        // Groups I asked to join and am still waiting on.
        $sent = GroupMember::with('group:id,name')
            ->where('user_id', $user->id)
            ->where('status', GroupMember::PENDING)
            ->get()
            ->map(fn ($m) => ['id' => $m->id, 'group' => $m->group->name]);

        return Inertia::render('mygroups', [
            'groups' => $groups,
            'requests' => $requests,
            'sent' => $sent,
            'subjectOptions' => Subject::orderBy('name')->get(['id', 'name']),
            'skillLevels' => StudentProfile::SKILL_LEVELS,
            'studyGoals' => StudentProfile::STUDY_GOALS,
            'studyModes' => StudentProfile::STUDY_MODES,
        ]);
    }

    /** CREATE GROUP: the creator becomes the owner and first member. */
    public function store(StoreStudyGroupRequest $request): RedirectResponse
    {
        $user = $request->user();

        $group = StudyGroup::create($request->validated() + ['owner_id' => $user->id]);

        $group->memberships()->create([
            'user_id' => $user->id,
            'role' => 'owner',
            'status' => GroupMember::APPROVED,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Group created.']);

        return to_route('mygroups');
    }

    /** JOIN: creates a pending request that the owner must approve. */
    public function join(Request $request, StudyGroup $group, Notifier $notifier): RedirectResponse
    {
        $user = $request->user();

        if ($group->memberships()->where('user_id', $user->id)->exists()) {
            Inertia::flash('toast', ['type' => 'info', 'message' => 'You already joined or requested this group.']);

            return back();
        }

        if ($group->approvedCount() >= $group->max_members) {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'This group is full.']);

            return back();
        }

        $group->memberships()->create([
            'user_id' => $user->id,
            'role' => 'member',
            'status' => GroupMember::PENDING,
        ]);

        // Tell the group owner there is a request waiting.
        $notifier->toUser($group->owner, 'join_request', "{$user->name} wants to join {$group->name}", 'Open My Groups to approve or decline.', '/mygroups', $group->id);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Join request sent. The group owner will review it.']);

        return back();
    }

    /** APPROVE / DECLINE a join request (group owner only). */
    public function respond(Request $request, StudyGroup $group, GroupMember $member, Notifier $notifier): RedirectResponse
    {
        abort_unless($group->owner_id === $request->user()->id, 403);
        abort_unless($member->study_group_id === $group->id && $member->status === GroupMember::PENDING, 404);

        $data = $request->validate(['action' => ['required', 'in:approve,decline']]);

        if ($data['action'] === 'decline') {
            $member->delete();
            Inertia::flash('toast', ['type' => 'info', 'message' => 'Request declined.']);

            return back();
        }

        if ($group->approvedCount() >= $group->max_members) {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'The group is already full.']);

            return back();
        }

        $member->update(['status' => GroupMember::APPROVED]);
        $notifier->toUser($member->user, 'approved', "You were added to {$group->name}", 'You can now chat, share notes and join sessions.', "/messages?group={$group->id}", $group->id);
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Member approved.']);

        return back();
    }

    /** LEAVE a group (owners can't leave their own group). */
    public function leave(Request $request, StudyGroup $group): RedirectResponse
    {
        abort_if($group->owner_id === $request->user()->id, 403, 'Owners cannot leave their own group.');

        $group->memberships()->where('user_id', $request->user()->id)->delete();

        Inertia::flash('toast', ['type' => 'info', 'message' => 'You left the group.']);

        return back();
    }
}
