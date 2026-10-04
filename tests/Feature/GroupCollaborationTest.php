<?php

use App\Models\GroupMember;
use App\Models\StudyGroup;
use App\Models\Subject;
use App\Models\User;
use Database\Seeders\SubjectSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(fn () => $this->seed(SubjectSeeder::class));

/** A group owned by $owner, plus optional extra approved members. */
function collabGroup(User $owner, array $members = []): StudyGroup
{
    $group = StudyGroup::create([
        'owner_id' => $owner->id,
        'subject_id' => Subject::first()->id,
        'name' => 'Collab Crew',
        'skill_level' => 'Beginner',
        'study_goal' => 'Pass Exam',
        'study_mode' => 'Online',
        'max_members' => 5,
    ]);
    $group->memberships()->create(['user_id' => $owner->id, 'role' => 'owner', 'status' => GroupMember::APPROVED]);
    foreach ($members as $m) {
        $group->memberships()->create(['user_id' => $m->id, 'role' => 'member', 'status' => GroupMember::APPROVED]);
    }

    return $group;
}

test('a member can chat and sees the message', function () {
    $owner = User::factory()->create();
    $group = collabGroup($owner);

    $this->actingAs($owner)->post("/groups/{$group->id}/messages", ['body' => 'Hello team'])->assertRedirect();

    $this->actingAs($owner)->get("/messages?group={$group->id}")
        ->assertInertia(fn ($page) => $page->where('messages.0.body', 'Hello team')->where('messages.0.mine', true));
});

test('a pending or outside student cannot read or post in the chat', function () {
    $group = collabGroup(User::factory()->create());
    $outsider = User::factory()->create();
    $group->memberships()->create(['user_id' => $outsider->id, 'role' => 'member', 'status' => GroupMember::PENDING]);

    $this->actingAs($outsider)->post("/groups/{$group->id}/messages", ['body' => 'let me in'])->assertForbidden();
    $this->actingAs($outsider)->get("/messages?group={$group->id}")
        ->assertInertia(fn ($page) => $page->where('active', null)->where('messages', []));
});

test('notes: members add, only author or owner delete', function () {
    $owner = User::factory()->create();
    $author = User::factory()->create();
    $other = User::factory()->create();
    $group = collabGroup($owner, [$author, $other]);

    $this->actingAs($author)->post("/groups/{$group->id}/notes", ['title' => 'Formulas', 'body' => 'a+b'])->assertRedirect();
    $note = $group->notes()->first();

    $this->actingAs($other)->delete("/groups/{$group->id}/notes/{$note->id}")->assertForbidden();
    $this->actingAs($owner)->delete("/groups/{$group->id}/notes/{$note->id}")->assertRedirect();
    expect($group->notes()->count())->toBe(0);
});

test('files: members upload and download, outsiders are blocked', function () {
    Storage::fake('local');
    $owner = User::factory()->create();
    $group = collabGroup($owner);
    $outsider = User::factory()->create();

    $this->actingAs($owner)
        ->post("/groups/{$group->id}/files", ['file' => UploadedFile::fake()->create('notes.pdf', 100, 'application/pdf')])
        ->assertRedirect();

    $file = $group->files()->first();
    expect($file->original_name)->toBe('notes.pdf');
    Storage::disk('local')->assertExists($file->path);

    $this->actingAs($owner)->get("/groups/{$group->id}/files/{$file->id}")->assertOk();
    $this->actingAs($outsider)->get("/groups/{$group->id}/files/{$file->id}")->assertForbidden();
});

test('dangerous file types and oversized files are rejected', function () {
    Storage::fake('local');
    $owner = User::factory()->create();
    $group = collabGroup($owner);

    $this->actingAs($owner)->post("/groups/{$group->id}/files", ['file' => UploadedFile::fake()->create('virus.php', 10)])
        ->assertSessionHasErrors('file');
    $this->actingAs($owner)->post("/groups/{$group->id}/files", ['file' => UploadedFile::fake()->create('big.pdf', 6000, 'application/pdf')])
        ->assertSessionHasErrors('file');
    expect($group->files()->count())->toBe(0);
});
