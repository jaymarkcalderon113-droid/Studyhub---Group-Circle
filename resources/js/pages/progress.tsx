import { Head, router, useForm } from '@inertiajs/react';
import { Clock, Trash2 } from 'lucide-react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import { Page, Panel } from '@/components/studyhub/ui';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type Props = {
    totalHours: number;
    change: number | null;
    chart: { day: string; hours: number }[];
    logs: { id: number; group: string; topic: string | null; date: string; minutes: number }[];
    groups: { id: number; name: string }[];
    today: string;
};

const fmt = (m: number) => (m >= 60 ? `${Math.floor(m / 60)} h${m % 60 ? ` ${m % 60} min` : ''}` : `${m} min`);

/** PHASE 2D: study session tracking. Log time, see the last 7 days. */
export default function Progress({ totalHours, change, chart, logs, groups, today }: Props) {
    const [open, setOpen] = useState(false);
    const form = useForm({ studied_on: today, minutes: 60, topic: '', study_group_id: '' });
    const max = Math.max(1, ...chart.map((d) => d.hours));

    const save = () =>
        form.post('/progress', {
            preserveScroll: true,
            onSuccess: () => {
                form.reset('topic');
                setOpen(false);
            },
        });

    return (
        <>
            <Head title="Progress" />
            <Page
                title="Study Session Tracking"
                subtitle="Log your study time and watch your week."
                action={
                    <Dialog open={open} onOpenChange={setOpen}>
                        <DialogTrigger asChild><Button>+ Log study time</Button></DialogTrigger>
                        <DialogContent>
                            <DialogHeader><DialogTitle>Log a study session</DialogTitle></DialogHeader>
                            <div className="grid gap-3">
                                <Label htmlFor="d">Date</Label>
                                <Input id="d" type="date" max={today} value={form.data.studied_on} onChange={(e) => form.setData('studied_on', e.target.value)} />
                                <InputError message={form.errors.studied_on} />
                                <Label htmlFor="m">Minutes studied (5-720)</Label>
                                <Input id="m" type="number" min={5} max={720} value={form.data.minutes} onChange={(e) => form.setData('minutes', Number(e.target.value))} />
                                <InputError message={form.errors.minutes} />
                                <Label htmlFor="t">Topic (optional)</Label>
                                <Input id="t" value={form.data.topic} onChange={(e) => form.setData('topic', e.target.value)} placeholder="e.g. Integrals" />
                                <Label>Group (optional)</Label>
                                <select value={form.data.study_group_id} onChange={(e) => form.setData('study_group_id', e.target.value)} className="border-input bg-background h-9 w-full rounded-md border px-3 text-sm shadow-xs">
                                    <option value="">Solo study</option>
                                    {groups.map((g) => <option key={g.id} value={g.id}>{g.name}</option>)}
                                </select>
                                <Button disabled={form.processing} onClick={save}>Save</Button>
                            </div>
                        </DialogContent>
                    </Dialog>
                }
            >
                <div className="grid gap-4 lg:grid-cols-3">
                    <Panel className="flex items-center gap-4">
                        <Clock className="text-primary size-12" />
                        <div>
                            <p className="text-muted-foreground text-xs">Last 7 days</p>
                            <p className="text-3xl font-semibold">{totalHours} hours</p>
                            {change !== null && <p className={`text-xs ${change >= 0 ? 'text-emerald-600' : 'text-red-600'}`}>{change >= 0 ? '+' : ''}{change}% from the week before</p>}
                        </div>
                    </Panel>
                    <Panel className="lg:col-span-2">
                        <h2 className="mb-3 font-semibold">Study time (hours)</h2>
                        <div className="flex h-40 items-end gap-3">
                            {chart.map((d, i) => (
                                <div key={i} className="flex h-full flex-1 flex-col items-center justify-end gap-1">
                                    <span className="text-muted-foreground text-[10px]">{d.hours || ''}</span>
                                    <div className="bg-primary w-full rounded-t" style={{ height: `${(d.hours / max) * 100}%`, minHeight: d.hours ? 4 : 0 }} />
                                    <span className="text-muted-foreground text-xs">{d.day}</span>
                                </div>
                            ))}
                        </div>
                    </Panel>
                </div>

                <Panel>
                    <h2 className="mb-3 font-semibold">Recent Sessions</h2>
                    {logs.length === 0 && <p className="text-muted-foreground text-sm">Nothing logged yet. Click “Log study time”.</p>}
                    <table className="w-full text-sm">
                        <thead className="text-muted-foreground text-left text-xs"><tr><th className="pb-2">Group / topic</th><th>Date</th><th>Duration</th><th /></tr></thead>
                        <tbody className="divide-y">
                            {logs.map((l) => (
                                <tr key={l.id}>
                                    <td className="py-2">{l.group}{l.topic ? ` · ${l.topic}` : ''}</td>
                                    <td>{l.date}</td>
                                    <td>{fmt(l.minutes)}</td>
                                    <td className="text-right"><Button size="icon" variant="ghost" aria-label="Delete" onClick={() => router.delete(`/progress/${l.id}`, { preserveScroll: true })}><Trash2 /></Button></td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </Panel>
            </Page>
        </>
    );
}

Progress.layout = { breadcrumbs: [{ title: 'Progress', href: '/progress' }] };
