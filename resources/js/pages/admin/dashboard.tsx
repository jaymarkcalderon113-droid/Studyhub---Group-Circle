import { Head, Link } from '@inertiajs/react';
import { Page, Panel, Pill } from '@/components/studyhub/ui';

type Props = {
    stats: { students: number; suspended: number; groups: number; flagged: number; sessionsThisWeek: number };
    newestUsers: { id: number; name: string; role: string; joined: string }[];
    flaggedGroups: { id: number; name: string; owner: string }[];
};

/** PHASE 2F: admin dashboard with real totals. Route: /admin/dashboard (role:admin only). */
export default function AdminDashboard({ stats, newestUsers, flaggedGroups }: Props) {
    const cards: [string, number, string][] = [
        ['Students', stats.students, '/admin/users'],
        ['Suspended Accounts', stats.suspended, '/admin/users'],
        ['Study Groups', stats.groups, '/admin/groups'],
        ['Flagged Groups', stats.flagged, '/admin/groups'],
        ['Sessions This Week', stats.sessionsThisWeek, '/admin/groups'],
    ];

    return (
        <>
            <Head title="Admin Dashboard" />
            <Page title="Admin Dashboard" subtitle="Platform overview.">
                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
                    {cards.map(([label, value, href]) => (
                        <Link key={label} href={href}>
                            <Panel className="hover:border-primary/50 transition-colors">
                                <p className="text-3xl font-semibold text-blue-700">{value}</p>
                                <p className="text-muted-foreground text-sm">{label}</p>
                            </Panel>
                        </Link>
                    ))}
                </div>
                <div className="grid gap-4 lg:grid-cols-2">
                    <Panel>
                        <div className="mb-3 flex justify-between"><h2 className="font-semibold">Newest users</h2><Link href="/admin/users" className="text-primary text-xs">Manage</Link></div>
                        <ul className="divide-y text-sm">
                            {newestUsers.map((u) => (
                                <li key={u.id} className="flex items-center justify-between py-2">
                                    <span>{u.name} <span className="text-muted-foreground text-xs">· {u.joined}</span></span>
                                    <Pill tone={u.role === 'admin' ? 'blue' : 'gray'}>{u.role}</Pill>
                                </li>
                            ))}
                        </ul>
                    </Panel>
                    <Panel>
                        <div className="mb-3 flex justify-between"><h2 className="font-semibold">Flagged groups</h2><Link href="/admin/groups" className="text-primary text-xs">Open</Link></div>
                        {flaggedGroups.length === 0 && <p className="text-muted-foreground text-sm">No flagged groups.</p>}
                        <ul className="divide-y text-sm">
                            {flaggedGroups.map((g) => <li key={g.id} className="flex items-center justify-between py-2"><span>{g.name} <span className="text-muted-foreground text-xs">· {g.owner}</span></span><Pill tone="red">Flagged</Pill></li>)}
                        </ul>
                    </Panel>
                </div>
            </Page>
        </>
    );
}

AdminDashboard.layout = { breadcrumbs: [{ title: 'Admin Dashboard', href: '/admin/dashboard' }] };
