import { Head, Link, router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import { Page, Panel, Pill, SelectBox } from '@/components/studyhub/ui';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type Props = {
    groups: { id: number; name: string; subject: string; skill_level: string; study_mode: string; members: number; max: number; is_owner: boolean }[];
    requests: { id: number; group_id: number; group: string; student: string }[];
    sent: { id: number; group: string }[];
    subjectOptions: { id: number; name: string }[];
    skillLevels: string[];
    studyGoals: string[];
    studyModes: string[];
};

/**
 * PHASE 2B: My Groups from the database.
 * - "Create Group" posts to /mygroups
 * - owners approve/decline join requests with PATCH /groups/{id}/members/{id}
 */
export default function MyGroups({ groups, requests, sent, subjectOptions, skillLevels, studyGoals, studyModes }: Props) {
    const [open, setOpen] = useState(false);
    const form = useForm({
        name: '',
        description: '',
        subject_id: subjectOptions[0]?.id ?? 0,
        skill_level: skillLevels[0],
        study_goal: studyGoals[0],
        study_mode: studyModes[0],
        max_members: 5,
    });

    const create = () =>
        form.post('/mygroups', {
            preserveScroll: true,
            onSuccess: () => {
                form.reset();
                setOpen(false);
            },
        });

    const answer = (r: Props['requests'][number], action: 'approve' | 'decline') =>
        router.patch(`/groups/${r.group_id}/members/${r.id}`, { action }, { preserveScroll: true });

    const leave = (id: number) => router.delete(`/groups/${id}/leave`, { preserveScroll: true });

    return (
        <>
            <Head title="My Groups" />
            <Page
                title="My Groups"
                subtitle="Groups you belong to."
                action={
                    <Dialog open={open} onOpenChange={setOpen}>
                        <DialogTrigger asChild><Button>+ Create Group</Button></DialogTrigger>
                        <DialogContent>
                            <DialogHeader><DialogTitle>Create a study group</DialogTitle></DialogHeader>
                            <div className="grid gap-3">
                                <Label htmlFor="gname">Group name</Label>
                                <Input id="gname" value={form.data.name} onChange={(e) => form.setData('name', e.target.value)} placeholder="e.g. Calculus Crew" />
                                <InputError message={form.errors.name} />

                                <Label>Subject</Label>
                                <select
                                    value={form.data.subject_id}
                                    onChange={(e) => form.setData('subject_id', Number(e.target.value))}
                                    className="border-input bg-background h-9 w-full rounded-md border px-3 text-sm shadow-xs"
                                >
                                    {subjectOptions.map((s) => <option key={s.id} value={s.id}>{s.name}</option>)}
                                </select>
                                <InputError message={form.errors.subject_id} />

                                <Label>Skill level</Label>
                                <SelectBox value={form.data.skill_level} onChange={(v) => form.setData('skill_level', v)} options={skillLevels} />
                                <Label>Study goal</Label>
                                <SelectBox value={form.data.study_goal} onChange={(v) => form.setData('study_goal', v)} options={studyGoals} />
                                <Label>Study preference</Label>
                                <SelectBox value={form.data.study_mode} onChange={(v) => form.setData('study_mode', v)} options={studyModes} />

                                <Label htmlFor="max">Max members (2-10)</Label>
                                <Input id="max" type="number" min={2} max={10} value={form.data.max_members} onChange={(e) => form.setData('max_members', Number(e.target.value))} />
                                <InputError message={form.errors.max_members} />

                                <Button disabled={form.processing} onClick={create}>Create group</Button>
                            </div>
                        </DialogContent>
                    </Dialog>
                }
            >
                {requests.length > 0 && (
                    <Panel>
                        <h2 className="mb-3 font-semibold">Join requests</h2>
                        <ul className="divide-y">
                            {requests.map((r) => (
                                <li key={r.id} className="flex items-center justify-between gap-3 py-3 text-sm">
                                    <span><b>{r.student}</b> wants to join <b>{r.group}</b></span>
                                    <span className="flex gap-2">
                                        <Button size="sm" onClick={() => answer(r, 'approve')}>Approve</Button>
                                        <Button size="sm" variant="outline" onClick={() => answer(r, 'decline')}>Decline</Button>
                                    </span>
                                </li>
                            ))}
                        </ul>
                    </Panel>
                )}

                {groups.length === 0 && (
                    <Panel><p className="text-muted-foreground text-sm">You are not in any group yet. Create one, or find a match in Find Groups.</p></Panel>
                )}

                <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    {groups.map((g) => (
                        <Panel key={g.id} className="space-y-3">
                            <div>
                                <h3 className="font-semibold">{g.name}</h3>
                                <p className="text-muted-foreground text-xs">{g.members}/{g.max} members · {g.skill_level}</p>
                            </div>
                            <div className="flex flex-wrap gap-1">
                                <Pill>{g.subject}</Pill><Pill tone="gray">{g.study_mode}</Pill>
                                {g.is_owner && <Pill tone="orange">Owner</Pill>}
                            </div>
                            <div className="flex gap-2">
                                <Button asChild size="sm" variant="outline"><Link href={`/messages?group=${g.id}`}>Open chat</Link></Button>
                                <Button asChild size="sm" variant="outline"><Link href={`/notes?group=${g.id}`}>Notes & files</Link></Button>
                                {!g.is_owner && <Button size="sm" variant="ghost" onClick={() => leave(g.id)}>Leave</Button>}
                            </div>
                        </Panel>
                    ))}
                </div>

                {sent.length > 0 && (
                    <Panel>
                        <h2 className="mb-2 font-semibold">Waiting for approval</h2>
                        <div className="flex flex-wrap gap-2">{sent.map((s) => <Pill key={s.id} tone="gray">{s.group}</Pill>)}</div>
                    </Panel>
                )}
            </Page>
        </>
    );
}

MyGroups.layout = { breadcrumbs: [{ title: 'My Groups', href: '/mygroups' }] };
