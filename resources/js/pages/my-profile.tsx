import { Head, useForm, usePage } from '@inertiajs/react';
import InputError from '@/components/input-error';
import { Chip, Initials, Page, Panel, SelectBox } from '@/components/studyhub/ui';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type Props = {
    profile: {
        bio: string;
        skill_level: string;
        study_goal: string;
        study_mode: string;
        subjects: number[];
    };
    subjectOptions: { id: number; name: string }[];
    skillLevels: string[];
    studyGoals: string[];
    studyModes: string[];
};

/**
 * PHASE 2A: real profile saved in MySQL.
 * useForm keeps the form values and sends them with PUT /my-profile.
 * Laravel validates them and any errors appear under the fields.
 */
export default function MyProfile({ profile, subjectOptions, skillLevels, studyGoals, studyModes }: Props) {
    const { auth } = usePage().props;
    const form = useForm(profile);

    const toggle = (id: number) =>
        form.setData(
            'subjects',
            form.data.subjects.includes(id)
                ? form.data.subjects.filter((x) => x !== id)
                : [...form.data.subjects, id],
        );

    return (
        <>
            <Head title="My Profile" />
            <Page title="My Profile" subtitle="Tell us how you like to study so we can match you.">
                <div className="grid gap-4 lg:grid-cols-3">
                    <Panel className="flex flex-col items-center gap-2 text-center">
                        <Initials name={auth.user.name} className="size-20 text-xl" />
                        <p className="font-semibold">{auth.user.name}</p>
                        <p className="text-muted-foreground text-sm">{auth.user.email}</p>
                    </Panel>

                    <Panel className="space-y-5 lg:col-span-2">
                        <div className="grid gap-2">
                            <Label>Subjects</Label>
                            <div className="flex flex-wrap gap-2">
                                {subjectOptions.map((s) => (
                                    <Chip key={s.id} active={form.data.subjects.includes(s.id)} onClick={() => toggle(s.id)}>
                                        {s.name}
                                    </Chip>
                                ))}
                            </div>
                            <InputError message={form.errors.subjects} />
                        </div>

                        <div className="grid gap-4 sm:grid-cols-3">
                            <div className="grid gap-2">
                                <Label>Skill level</Label>
                                <SelectBox value={form.data.skill_level} onChange={(v) => form.setData('skill_level', v)} options={skillLevels} />
                                <InputError message={form.errors.skill_level} />
                            </div>
                            <div className="grid gap-2">
                                <Label>Study goal</Label>
                                <SelectBox value={form.data.study_goal} onChange={(v) => form.setData('study_goal', v)} options={studyGoals} />
                                <InputError message={form.errors.study_goal} />
                            </div>
                            <div className="grid gap-2">
                                <Label>Preference</Label>
                                <SelectBox value={form.data.study_mode} onChange={(v) => form.setData('study_mode', v)} options={studyModes} />
                                <InputError message={form.errors.study_mode} />
                            </div>
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="bio">About me</Label>
                            <Input id="bio" value={form.data.bio} onChange={(e) => form.setData('bio', e.target.value)} placeholder="e.g. Evening learner, love group problem-solving" />
                            <InputError message={form.errors.bio} />
                        </div>

                        <Button disabled={form.processing} onClick={() => form.put('/my-profile')}>
                            {form.processing ? 'Saving...' : 'Save profile'}
                        </Button>
                    </Panel>
                </div>
            </Page>
        </>
    );
}

MyProfile.layout = { breadcrumbs: [{ title: 'My Profile', href: '/my-profile' }] };
