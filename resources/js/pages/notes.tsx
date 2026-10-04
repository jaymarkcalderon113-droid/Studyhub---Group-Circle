import { Head, router, useForm } from '@inertiajs/react';
import { FileText, Paperclip, Trash2 } from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';
import InputError from '@/components/input-error';
import { GroupTabs, NoGroups, Page, Panel } from '@/components/studyhub/ui';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type Props = {
    groups: { id: number; name: string }[];
    active: { id: number; name: string } | null;
    notes: { id: number; title: string; body: string; author: string; date: string; can_delete: boolean }[];
    files: { id: number; name: string; size: string; author: string; date: string; can_delete: boolean }[];
};

/**
 * PHASE 2C: shared notes + file sharing, saved in MySQL / private storage.
 * Everything here belongs to ONE group (chosen with the tabs at the top).
 */
export default function Notes({ groups, active, notes, files }: Props) {
    const [tab, setTab] = useState<'notes' | 'files'>('notes');
    const [open, setOpen] = useState(false);
    const form = useForm({ title: '', body: '' });

    const addNote = () =>
        active &&
        form.post(`/groups/${active.id}/notes`, {
            preserveScroll: true,
            onSuccess: () => {
                form.reset();
                setOpen(false);
            },
        });

    const removeNote = (id: number) => active && confirm('Delete this note?') && router.delete(`/groups/${active.id}/notes/${id}`, { preserveScroll: true });
    const removeFile = (id: number) => active && confirm('Delete this file?') && router.delete(`/groups/${active.id}/files/${id}`, { preserveScroll: true });

    // Upload straight away when a file is chosen (max 5 MB, checked again by Laravel).
    const upload = (file?: File) => {
        if (!active || !file) return;
        router.post(`/groups/${active.id}/files`, { file }, {
            forceFormData: true,
            preserveScroll: true,
            onError: (errors) => toast.error(errors.file ?? 'Upload failed'),
            onSuccess: () => toast.success('File uploaded'),
        });
    };

    return (
        <>
            <Head title="Notes & Files" />
            <Page title="Shared Notes & Files" subtitle="Everything your group shares in one place.">
                {groups.length === 0 || !active ? (
                    <NoGroups />
                ) : (
                    <>
                        <GroupTabs groups={groups} activeId={active.id} basePath="/notes" />
                        <div className="flex gap-2">
                            {(['notes', 'files'] as const).map((t) => (
                                <Button key={t} variant={tab === t ? 'default' : 'outline'} size="sm" onClick={() => setTab(t)}>
                                    {t === 'notes' ? `Notes (${notes.length})` : `Files (${files.length})`}
                                </Button>
                            ))}
                        </div>

                        {tab === 'notes' ? (
                            <Panel>
                                <Dialog open={open} onOpenChange={setOpen}>
                                    <DialogTrigger asChild><Button className="mb-4">+ New Note</Button></DialogTrigger>
                                    <DialogContent>
                                        <DialogHeader><DialogTitle>New note for {active.name}</DialogTitle></DialogHeader>
                                        <div className="grid gap-3">
                                            <Label htmlFor="title">Title</Label>
                                            <Input id="title" value={form.data.title} onChange={(e) => form.setData('title', e.target.value)} />
                                            <InputError message={form.errors.title} />
                                            <Label htmlFor="body">Note</Label>
                                            <textarea
                                                id="body"
                                                rows={6}
                                                value={form.data.body}
                                                onChange={(e) => form.setData('body', e.target.value)}
                                                className="border-input bg-background w-full rounded-md border p-3 text-sm shadow-xs"
                                            />
                                            <InputError message={form.errors.body} />
                                            <Button disabled={form.processing} onClick={addNote}>Save note</Button>
                                        </div>
                                    </DialogContent>
                                </Dialog>
                                {notes.length === 0 && <p className="text-muted-foreground text-sm">No notes yet.</p>}
                                <ul className="divide-y">
                                    {notes.map((n) => (
                                        <li key={n.id} className="flex items-start gap-3 py-3">
                                            <FileText className="text-primary mt-0.5 size-5 shrink-0" />
                                            <div className="min-w-0 flex-1">
                                                <p className="text-sm font-medium">{n.title}</p>
                                                <p className="text-muted-foreground text-xs">By {n.author} · {n.date}</p>
                                                <p className="mt-1 text-sm break-words whitespace-pre-wrap">{n.body}</p>
                                            </div>
                                            {n.can_delete && (
                                                <Button size="icon" variant="ghost" aria-label="Delete note" onClick={() => removeNote(n.id)}><Trash2 /></Button>
                                            )}
                                        </li>
                                    ))}
                                </ul>
                            </Panel>
                        ) : (
                            <Panel>
                                <label className="mb-4 inline-flex cursor-pointer items-center gap-2 rounded-md border px-3 py-2 text-sm">
                                    <Paperclip className="size-4" /> Upload file (max 5 MB)
                                    <input
                                        type="file"
                                        className="hidden"
                                        onChange={(e) => {
                                            upload(e.target.files?.[0]);
                                            e.target.value = ''; // allow choosing the same file again
                                        }}
                                    />
                                </label>
                                {files.length === 0 && <p className="text-muted-foreground text-sm">No files yet.</p>}
                                <ul className="divide-y">
                                    {files.map((f) => (
                                        <li key={f.id} className="flex items-center gap-3 py-3">
                                            <FileText className="size-5 shrink-0 text-red-500" />
                                            <div className="min-w-0 flex-1">
                                                {/* Plain <a>: it's a file download, not an Inertia page visit */}
                                                <a href={`/groups/${active.id}/files/${f.id}`} className="text-primary text-sm font-medium break-all hover:underline">{f.name}</a>
                                                <p className="text-muted-foreground text-xs">{f.size} · {f.author} · {f.date}</p>
                                            </div>
                                            {f.can_delete && (
                                                <Button size="icon" variant="ghost" aria-label="Delete file" onClick={() => removeFile(f.id)}><Trash2 /></Button>
                                            )}
                                        </li>
                                    ))}
                                </ul>
                            </Panel>
                        )}
                    </>
                )}
            </Page>
        </>
    );
}

Notes.layout = { breadcrumbs: [{ title: 'Notes & Files', href: '/notes' }] };
