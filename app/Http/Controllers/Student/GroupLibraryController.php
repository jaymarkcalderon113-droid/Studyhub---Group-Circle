<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Concerns\InteractsWithStudyGroups;
use App\Http\Controllers\Controller;
use App\Models\GroupFile;
use App\Models\GroupNote;
use App\Models\StudyGroup;
use App\Services\Notifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * SHARED NOTES + FILE SHARING. Members only.
 * Files are saved on the PRIVATE disk (storage/app/private), so nobody can
 * open them by guessing a URL: they must go through downloadFile() below,
 * which checks group membership first.
 */
class GroupLibraryController extends Controller
{
    use InteractsWithStudyGroups;

    public function index(Request $request): Response
    {
        $user = $request->user();
        $groups = $this->memberGroups($request);
        $group = $this->resolveGroup($request, $groups);

        // Authors may delete their own items; the group owner may delete anything.
        $canDelete = fn ($item) => $group && ($item->user_id === $user->id || $group->owner_id === $user->id);

        return Inertia::render('notes', [
            'groups' => $this->groupTabs($groups),
            'active' => $group ? ['id' => $group->id, 'name' => $group->name] : null,
            'notes' => $group
                ? $group->notes()->with('user:id,name')->latest('id')->get()->map(fn ($n) => [
                    'id' => $n->id,
                    'title' => $n->title,
                    'body' => $n->body,
                    'author' => $n->user->name,
                    'date' => $n->created_at->format('M j, Y'),
                    'can_delete' => $canDelete($n),
                ])
                : [],
            'files' => $group
                ? $group->files()->with('user:id,name')->latest('id')->get()->map(fn ($f) => [
                    'id' => $f->id,
                    'name' => $f->original_name,
                    'size' => $this->formatBytes($f->size),
                    'author' => $f->user->name,
                    'date' => $f->created_at->format('M j, Y'),
                    'can_delete' => $canDelete($f),
                ])
                : [],
        ]);
    }

    /**
     * "2.4 MB" style file size. Written by hand (instead of Laravel's Number::fileSize)
     * because that helper needs PHP's "intl" extension, which many Windows PHP installs
     * have switched off.
     */
    private function formatBytes(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes.' B';
        }

        $units = ['KB', 'MB', 'GB'];
        $size = $bytes / 1024;
        $i = 0;

        while ($size >= 1024 && $i < count($units) - 1) {
            $size /= 1024;
            $i++;
        }

        return round($size, 1).' '.$units[$i];
    }

    public function storeNote(Request $request, StudyGroup $group, Notifier $notifier): RedirectResponse
    {
        $this->authorizeMember($request, $group);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'body' => ['required', 'string', 'max:5000'],
        ]);

        $group->notes()->create($data + ['user_id' => $request->user()->id]);
        $notifier->toGroup($group, $request->user(), 'note', "New note in {$group->name}", "{$request->user()->name} added “{$data['title']}”.", "/notes?group={$group->id}");

        return back();
    }

    public function destroyNote(Request $request, StudyGroup $group, GroupNote $note): RedirectResponse
    {
        $this->authorizeMember($request, $group);
        abort_unless($note->study_group_id === $group->id, 404);
        abort_unless($note->user_id === $request->user()->id || $group->owner_id === $request->user()->id, 403);

        $note->delete();

        return back();
    }

    public function storeFile(Request $request, StudyGroup $group, Notifier $notifier): RedirectResponse
    {
        $this->authorizeMember($request, $group);

        $request->validate([
            // 5 MB max, and only common study-file types
            'file' => ['required', 'file', 'max:5120', 'mimes:pdf,doc,docx,ppt,pptx,xls,xlsx,txt,png,jpg,jpeg,zip'],
        ]);

        $uploaded = $request->file('file');
        $size = $uploaded->getSize();
        $name = $uploaded->getClientOriginalName();
        $mime = $uploaded->getClientMimeType();

        // store() gives the file a random name, so users can't overwrite each other's files.
        $path = $uploaded->store("group-files/{$group->id}", 'local');

        $group->files()->create([
            'user_id' => $request->user()->id,
            'original_name' => $name,
            'path' => $path,
            'size' => $size,
            'mime' => $mime,
        ]);
        $notifier->toGroup($group, $request->user(), 'file', "New file shared in {$group->name}", "{$request->user()->name} shared “{$name}”.", "/notes?group={$group->id}");

        return back();
    }

    public function downloadFile(Request $request, StudyGroup $group, GroupFile $file): StreamedResponse
    {
        $this->authorizeMember($request, $group);
        abort_unless($file->study_group_id === $group->id, 404);
        abort_unless(Storage::disk('local')->exists($file->path), 404);

        return Storage::disk('local')->download($file->path, $file->original_name);
    }

    public function destroyFile(Request $request, StudyGroup $group, GroupFile $file): RedirectResponse
    {
        $this->authorizeMember($request, $group);
        abort_unless($file->study_group_id === $group->id, 404);
        abort_unless($file->user_id === $request->user()->id || $group->owner_id === $request->user()->id, 403);

        Storage::disk('local')->delete($file->path); // remove the real file too
        $file->delete();

        return back();
    }
}