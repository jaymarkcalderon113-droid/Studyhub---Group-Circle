<?php

namespace App\Services;

use App\Models\StudyGroup;
use App\Models\User;
use App\Models\UserNotification;

/**
 * One place that creates notifications, so controllers stay short:
 *   $notifier->toUser($owner, 'join_request', 'New join request', ...);
 *   $notifier->toGroup($group, $me, 'file', 'New file shared', ...);   // everyone except me
 */
class Notifier
{
    public function toUser(User $user, string $type, string $title, ?string $body = null, ?string $link = null, ?int $groupId = null): void
    {
        UserNotification::create([
            'user_id' => $user->id,
            'study_group_id' => $groupId,
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'link' => $link,
        ]);
    }

    /** Notify every approved member of the group except the person who did the action. */
    public function toGroup(StudyGroup $group, User $except, string $type, string $title, ?string $body = null, ?string $link = null): void
    {
        $group->members()->where('users.id', '!=', $except->id)->get(['users.id'])
            ->each(fn ($member) => UserNotification::create([
                'user_id' => $member->id,
                'study_group_id' => $group->id,
                'type' => $type,
                'title' => $title,
                'body' => $body,
                'link' => $link,
            ]));
    }

    /**
     * Chat would create one alert per message, which is far too noisy.
     * So: only create a "new message" alert if that member has no UNREAD one for this group.
     */
    public function newMessage(StudyGroup $group, User $sender, string $text): void
    {
        $group->members()->where('users.id', '!=', $sender->id)->get(['users.id'])
            ->each(function ($member) use ($group, $sender, $text) {
                $alreadyAlerted = UserNotification::where('user_id', $member->id)
                    ->where('study_group_id', $group->id)
                    ->where('type', 'message')
                    ->whereNull('read_at')
                    ->exists();

                if (! $alreadyAlerted) {
                    UserNotification::create([
                        'user_id' => $member->id,
                        'study_group_id' => $group->id,
                        'type' => 'message',
                        'title' => "New message in {$group->name}",
                        'body' => "{$sender->name}: ".str($text)->limit(80),
                        'link' => "/messages?group={$group->id}",
                    ]);
                }
            });
    }
}
