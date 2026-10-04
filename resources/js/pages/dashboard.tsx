import { Head, Link, usePage } from '@inertiajs/react';
import { Page, Panel, Pill } from '@/components/studyhub/ui';

type Props = {
    stats: { groups: number; sessions: number; tasksDue: number; rating: number | null };
    upcoming: { id: number; title: string; group: string; when: string }[];
    notifications: { id: number; title: string; time: string; read: boolean }[];
    groups: { id: number; name: string }[];
};

/** Student dashboard. PHASE 2E: every number now comes from the database. Route: /dashboard (role:student) */
export default function Dashboard({ stats, upcoming, notifications, groups }: Props) {
    const { auth } = usePage().props;
    const cards: [string, string | number, string][] = [
        ['My Groups', stats.groups, '/mygroups'],
        ['Upcoming Sessions', stats.sessions, '/schedule'],
        ['Tasks Pending', stats.tasksDue, '/tasks'],
        ['Group Rating', stats.rating ?? '–', '/ratings'],
    ];

    return (
        <>
            <Head title="Dashboard" />
            <Page title={`Welcome back, ${auth.user.name.split(' ')[0]}!`} subtitle="Let's make today productive.">
                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    {cards.map(([label, value, href]) => (
                        <Link key={label} href={href}>
                            <Panel className="hover:border-primary/50 transition-colors">
                                <p className="text-3xl font-semibold text-blue-700">{value}</p>
                                <p className="text-muted-foreground text-sm">{label}</p>
                            </Panel>
                        </Link>
                    ))}
                </div>

                <div className="grid gap-4 lg:grid-cols-3">
                    <Panel className="lg:col-span-2">
                        <div className="mb-3 flex items-center justify-between">
                            <h2 className="font-semibold">Upcoming Study Sessions</h2>
                            <Link href="/schedule" className="text-primary text-xs">View schedule</Link>
                        </div>
                        {upcoming.length === 0 && <p className="text-muted-foreground text-sm">No upcoming sessions. Plan one in Schedule.</p>}
                        <ul className="divide-y">
                            {upcoming.map((s) => (
                                <li key={s.id} className="py-3">
                                    <p className="text-sm font-medium">{s.title} <span className="text-muted-foreground font-normal">· {s.group}</span></p>
                                    <p className="text-muted-foreground text-xs">{s.when}</p>
                                </li>
                            ))}
                        </ul>
                    </Panel>

                    <Panel>
                        <div className="mb-3 flex items-center justify-between">
                            <h2 className="font-semibold">Recent Notifications</h2>
                            <Link href="/notifications" className="text-primary text-xs">View all</Link>
                        </div>
                        {notifications.length === 0 && <p className="text-muted-foreground text-sm">You're all caught up.</p>}
                        <ul className="space-y-3">
                            {notifications.map((n) => (
                                <li key={n.id} className="text-sm">
                                    <p className={n.read ? '' : 'font-semibold'}>{n.title}</p>
                                    <p className="text-muted-foreground text-xs">{n.time}</p>
                                </li>
                            ))}
                        </ul>
                    </Panel>
                </div>

                <Panel>
                    <h2 className="mb-3 font-semibold">My Groups</h2>
                    {groups.length === 0 && <p className="text-muted-foreground text-sm">You're not in a group yet. <Link href="/findgroups" className="text-primary underline">Find one</Link>.</p>}
                    <div className="flex flex-wrap gap-2">
                        {groups.map((g) => <Link key={g.id} href={`/messages?group=${g.id}`}><Pill>{g.name}</Pill></Link>)}
                    </div>
                </Panel>
            </Page>
        </>
    );
}

Dashboard.layout = { breadcrumbs: [{ title: 'Dashboard', href: '/dashboard' }] };
