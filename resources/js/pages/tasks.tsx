import { Head, router, useForm } from '@inertiajs/react';
import { Trash2 } from 'lucide-react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import { Page, Panel, Pill } from '@/components/studyhub/ui';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';

type Props = {
    tasks: { id: number; title: string; due: string | null; done: boolean; overdue: boolean; group: string | null }[];
    groups: { id: number; name: string }[];
};

/** PHASE 2D: my personal checklist, saved in MySQL. */
export default function Tasks({ tasks, groups }: Props) {
    const [filter, setFilter] = useState<'All' | 'Pending' | 'Completed'>('All');
    const form = useForm({ title: '', due_date: '', study_group_id: '' });

    const shown = tasks.filter((t) => filter === 'All' || (filter === 'Completed' ? t.done : !t.done));

    const add = () =>
        form.post('/tasks', {
            preserveScroll: true,
            onSuccess: () => form.reset(),
        });

    return (
        <>
            <Head title="Tasks" />
            <Page title="Tasks & Checklist" subtitle="Keep your group work on track.">
                <div className="flex gap-2">
                    {(['All', 'Pending', 'Completed'] as const).map((f) => (
                        <Button key={f} size="sm" variant={filter === f ? 'default' : 'outline'} onClick={() => setFilter(f)}>{f}</Button>
                    ))}
                </div>

                <Panel>
                    <div className="mb-4 grid gap-2 sm:grid-cols-[1fr_auto_auto_auto]">
                        <div>
                            <Input value={form.data.title} onChange={(e) => form.setData('title', e.target.value)} onKeyDown={(e) => e.key === 'Enter' && add()} placeholder="Add a task" />
                            <InputError message={form.errors.title} />
                        </div>
                        <Input type="date" value={form.data.due_date} onChange={(e) => form.setData('due_date', e.target.value)} aria-label="Due date" />
                        <select value={form.data.study_group_id} onChange={(e) => form.setData('study_group_id', e.target.value)} className="border-input bg-background h-9 rounded-md border px-3 text-sm shadow-xs" aria-label="Group">
                            <option value="">No group</option>
                            {groups.map((g) => <option key={g.id} value={g.id}>{g.name}</option>)}
                        </select>
                        <Button disabled={form.processing} onClick={add}>+ Add Task</Button>
                    </div>

                    {shown.length === 0 && <p className="text-muted-foreground text-sm">Nothing here yet.</p>}
                    <ul className="divide-y">
                        {shown.map((t) => (
                            <li key={t.id} className="flex items-center gap-3 py-3">
                                <Checkbox checked={t.done} onCheckedChange={() => router.patch(`/tasks/${t.id}/toggle`, {}, { preserveScroll: true })} aria-label="Mark done" />
                                <div className="flex-1">
                                    <p className={`text-sm font-medium ${t.done ? 'text-muted-foreground line-through' : ''}`}>{t.title}</p>
                                    <p className="text-muted-foreground text-xs">{t.due ? `Due ${t.due}` : 'No due date'}{t.group ? ` · ${t.group}` : ''}</p>
                                </div>
                                <Pill tone={t.done ? 'green' : t.overdue ? 'red' : 'orange'}>{t.done ? 'Completed' : t.overdue ? 'Overdue' : 'Pending'}</Pill>
                                <Button size="icon" variant="ghost" aria-label="Delete task" onClick={() => router.delete(`/tasks/${t.id}`, { preserveScroll: true })}><Trash2 /></Button>
                            </li>
                        ))}
                    </ul>
                </Panel>
            </Page>
        </>
    );
}

Tasks.layout = { breadcrumbs: [{ title: 'Tasks', href: '/tasks' }] };
