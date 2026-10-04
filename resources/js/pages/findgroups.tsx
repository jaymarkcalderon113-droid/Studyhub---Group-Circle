import { Head, router, useForm } from '@inertiajs/react';
import { Chip, Page, Panel, Pill, SelectBox } from '@/components/studyhub/ui';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';

type Result = {
    id: number;
    name: string;
    subject: string;
    skill_level: string;
    study_goal: string;
    study_mode: string;
    members: number;
    max: number;
    rating: number | null;
    reviews: number;
    score: number;
    requested: boolean;
};

type Props = {
    results: Result[];
    filters: { subjects: number[]; skill_level: string; study_goal: string; study_mode: string };
    subjectOptions: { id: number; name: string }[];
    skillLevels: string[];
    studyGoals: string[];
    studyModes: string[];
};

/**
 * PHASE 2B: automatic group matching from the database.
 * "Find Matches" sends the form as a GET request (/findgroups?subjects[]=1&...)
 * and Laravel's GroupMatcher returns the scored groups.
 */
export default function FindGroups({ results, filters, subjectOptions, skillLevels, studyGoals, studyModes }: Props) {
    const form = useForm(filters);

    const toggle = (id: number) =>
        form.setData('subjects', form.data.subjects.includes(id) ? form.data.subjects.filter((x) => x !== id) : [...form.data.subjects, id]);

    const search = () => form.get('/findgroups', { preserveScroll: true });

    const join = (id: number) => router.post(`/groups/${id}/join`, {}, { preserveScroll: true });

    return (
        <>
            <Head title="Find Groups" />
            <Page title="Find Study Partners" subtitle="Let us match you with students who fit your preferences.">
                <div className="grid gap-4 lg:grid-cols-3">
                    <Panel className="space-y-4">
                        <div className="grid gap-2">
                            <Label>Subjects</Label>
                            <div className="flex flex-wrap gap-2">
                                {subjectOptions.map((s) => (
                                    <Chip key={s.id} active={form.data.subjects.includes(s.id)} onClick={() => toggle(s.id)}>{s.name}</Chip>
                                ))}
                            </div>
                        </div>
                        <div className="grid gap-2"><Label>Skill level</Label><SelectBox value={form.data.skill_level} onChange={(v) => form.setData('skill_level', v)} options={skillLevels} /></div>
                        <div className="grid gap-2"><Label>Study goal</Label><SelectBox value={form.data.study_goal} onChange={(v) => form.setData('study_goal', v)} options={studyGoals} /></div>
                        <div className="grid gap-2"><Label>Study preference</Label><SelectBox value={form.data.study_mode} onChange={(v) => form.setData('study_mode', v)} options={studyModes} /></div>
                        <Button className="w-full" disabled={form.processing} onClick={search}>Find Matches</Button>
                    </Panel>

                    <Panel className="lg:col-span-2">
                        <h2 className="mb-3 font-semibold">Matching Results</h2>
                        {results.length === 0 && (
                            <p className="text-muted-foreground text-sm">No matching groups yet. Try other subjects, or create your own group in My Groups.</p>
                        )}
                        <ul className="divide-y">
                            {results.map((g) => (
                                <li key={g.id} className="flex items-center justify-between gap-3 py-3">
                                    <div>
                                        <p className="font-medium">{g.name}</p>
                                        <p className="text-muted-foreground text-xs">{g.members}/{g.max} members · {g.skill_level}{g.rating ? ` · ★ ${g.rating} (${g.reviews})` : ' · No ratings yet'}</p>
                                        <div className="mt-1 flex flex-wrap gap-1">
                                            <Pill>{g.subject}</Pill><Pill tone="green">{g.study_goal}</Pill><Pill tone="gray">{g.study_mode}</Pill>
                                        </div>
                                    </div>
                                    <div className="flex flex-col items-end gap-2">
                                        <Pill tone={g.score >= 80 ? 'green' : 'orange'}>{g.score}% match</Pill>
                                        <Button size="sm" disabled={g.requested} onClick={() => join(g.id)}>
                                            {g.requested ? 'Requested' : 'Join'}
                                        </Button>
                                    </div>
                                </li>
                            ))}
                        </ul>
                    </Panel>
                </div>
            </Page>
        </>
    );
}

FindGroups.layout = { breadcrumbs: [{ title: 'Find Groups', href: '/findgroups' }] };
