import { Head, Link, router, useForm } from '@inertiajs/react';
import { Trash2 } from 'lucide-react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import { NoGroups, Page, Panel } from '@/components/studyhub/ui';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type Session = { id: number; group_id: number; group: string; title: string; location: string | null; day: number; date: string; time: string; can_delete: boolean };

type Props = {
    groups: { id: number; name: string }[];
    sessions: Session[];
    upcoming: Session[];
    calendar: { month: string; label: string; prev: string; next: string; firstWeekday: number; daysInMonth: number; today: string };
};

const pad = (n: number) => String(n).padStart(2, '0');

/**
 * PHASE 2D: study schedule from the database.
 * The calendar shows sessions of ALL my groups for the month in the URL
 * (?month=2026-10). "Add Schedule" creates a session for one group.
 */
export default function Schedule({ groups, sessions, upcoming, calendar }: Props) {
    const todayInMonth = calendar.today.startsWith(calendar.month);
    const [selected, setSelected] = useState(todayInMonth ? Number(calendar.today.slice(8)) : 1);
    const [open, setOpen] = useState(false);

    const form = useForm({
        group_id: groups[0]?.id ?? 0,
        title: '',
        date: '',
        start_time: '10:00',
        end_time: '11:00',
        location: '',
    });

    const byDay = (d: number) => sessions.filter((s) => s.day === d);
    const cells = [...Array(calendar.firstWeekday).fill(null), ...Array.from({ length: calendar.daysInMonth }, (_, i) => i + 1)];

    const save = () => {
        // The group is part of the URL, so don't also send it in the body.
        form.transform(({ group_id: _group, ...rest }) => rest);
        form.post(`/groups/${form.data.group_id}/sessions`, {
            preserveScroll: true,
            onSuccess: () => {
                form.reset('title', 'location');
                setOpen(false);
            },
        });
    };

    const remove = (s: Session) => confirm('Delete this session?') && router.delete(`/groups/${s.group_id}/sessions/${s.id}`, { preserveScroll: true });

    return (
        <>
            <Head title="Schedule" />
            <Page
                title="Study Schedule"
                subtitle={calendar.label}
                action={
                    groups.length > 0 && (
                        <Dialog
                            open={open}
                            onOpenChange={(o) => {
                                if (o) form.setData('date', `${calendar.month}-${pad(selected)}`); // start on the day I clicked
                                setOpen(o);
                            }}
                        >
                            <DialogTrigger asChild><Button>+ Add Schedule</Button></DialogTrigger>
                            <DialogContent>
                                <DialogHeader><DialogTitle>Schedule a study session</DialogTitle></DialogHeader>
                                <div className="grid gap-3">
                                    <Label>Group</Label>
                                    <select value={form.data.group_id} onChange={(e) => form.setData('group_id', Number(e.target.value))} className="border-input bg-background h-9 w-full rounded-md border px-3 text-sm shadow-xs">
                                        {groups.map((g) => <option key={g.id} value={g.id}>{g.name}</option>)}
                                    </select>
                                    <Label htmlFor="title">Title</Label>
                                    <Input id="title" value={form.data.title} onChange={(e) => form.setData('title', e.target.value)} placeholder="e.g. Chapter 4 review" />
                                    <InputError message={form.errors.title} />
                                    <Label htmlFor="date">Date</Label>
                                    <Input id="date" type="date" value={form.data.date} onChange={(e) => form.setData('date', e.target.value)} />
                                    <InputError message={form.errors.date} />
                                    <div className="grid grid-cols-2 gap-3">
                                        <div className="grid gap-2"><Label htmlFor="st">Start</Label><Input id="st" type="time" value={form.data.start_time} onChange={(e) => form.setData('start_time', e.target.value)} /><InputError message={form.errors.start_time} /></div>
                                        <div className="grid gap-2"><Label htmlFor="et">End</Label><Input id="et" type="time" value={form.data.end_time} onChange={(e) => form.setData('end_time', e.target.value)} /><InputError message={form.errors.end_time} /></div>
                                    </div>
                                    <Label htmlFor="loc">Where (optional)</Label>
                                    <Input id="loc" value={form.data.location} onChange={(e) => form.setData('location', e.target.value)} placeholder="Online / Library room 2" />
                                    <Button disabled={form.processing} onClick={save}>Save session</Button>
                                </div>
                            </DialogContent>
                        </Dialog>
                    )
                }
            >
                {groups.length === 0 ? (
                    <NoGroups />
                ) : (
                    <div className="grid gap-4 lg:grid-cols-3">
                        <Panel className="lg:col-span-2">
                            <div className="mb-3 flex items-center justify-between">
                                <Button asChild size="sm" variant="outline"><Link href={`/schedule?month=${calendar.prev}`}>‹ Prev</Link></Button>
                                <span className="font-medium">{calendar.label}</span>
                                <Button asChild size="sm" variant="outline"><Link href={`/schedule?month=${calendar.next}`}>Next ›</Link></Button>
                            </div>
                            <div className="text-muted-foreground mb-2 grid grid-cols-7 text-center text-xs">
                                {['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'].map((d) => <span key={d}>{d}</span>)}
                            </div>
                            <div className="grid grid-cols-7 gap-1">
                                {cells.map((day, i) =>
                                    day === null ? <span key={i} /> : (
                                        <button
                                            key={i}
                                            onClick={() => setSelected(day)}
                                            className={`relative aspect-square rounded-lg text-sm ${selected === day ? 'bg-primary text-primary-foreground' : 'hover:bg-muted'} ${todayInMonth && calendar.today.endsWith(`-${pad(day)}`) && selected !== day ? 'ring-primary ring-1' : ''}`}
                                        >
                                            {day}
                                            {byDay(day).length > 0 && <span className="absolute bottom-1 left-1/2 size-1.5 -translate-x-1/2 rounded-full bg-amber-500" />}
                                        </button>
                                    ),
                                )}
                            </div>
                        </Panel>

                        <div className="space-y-4">
                            <Panel>
                                <h2 className="mb-3 font-semibold">Sessions on {calendar.label.split(' ')[0]} {selected}</h2>
                                {byDay(selected).length === 0 && <p className="text-muted-foreground text-sm">Nothing scheduled.</p>}
                                <ul className="space-y-3">
                                    {byDay(selected).map((s) => (
                                        <li key={s.id} className="flex items-start justify-between gap-2 text-sm">
                                            <div>
                                                <p className="font-medium">{s.title}</p>
                                                <p className="text-muted-foreground text-xs">{s.group} · {s.time}{s.location ? ` · ${s.location}` : ''}</p>
                                            </div>
                                            {s.can_delete && <Button size="icon" variant="ghost" aria-label="Delete session" onClick={() => remove(s)}><Trash2 /></Button>}
                                        </li>
                                    ))}
                                </ul>
                            </Panel>
                            <Panel>
                                <h2 className="mb-3 font-semibold">Upcoming</h2>
                                {upcoming.length === 0 && <p className="text-muted-foreground text-sm">No upcoming sessions.</p>}
                                <ul className="space-y-2 text-sm">
                                    {upcoming.map((s) => (
                                        <li key={s.id}><span className="font-medium">{s.title}</span><span className="text-muted-foreground block text-xs">{s.date} · {s.time} · {s.group}</span></li>
                                    ))}
                                </ul>
                            </Panel>
                        </div>
                    </div>
                )}
            </Page>
        </>
    );
}

Schedule.layout = { breadcrumbs: [{ title: 'Schedule', href: '/schedule' }] };
