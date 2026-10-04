import { Head, router } from '@inertiajs/react';
import { AdminSearch, Pager } from '@/components/studyhub/admin-search';
import { Page, Panel, Pill } from '@/components/studyhub/ui';
import { Button } from '@/components/ui/button';

type Group = { id: number; name: string; subject: string; owner: string; members: number; max: number; flagged: boolean; created: string };
type Props = {
    groups: { data: Group[]; prev_page_url: string | null; next_page_url: string | null; current_page: number; last_page: number; total: number };
    q: string;
};

/** PHASE 2F: moderation list of every group (flag it, or delete it for good). */
export default function AdminGroups({ groups, q }: Props) {
    const remove = (g: Group) => {
        if (confirm(`Delete “${g.name}” permanently? Its chat, notes, files and sessions will be removed too.`)) {
            router.delete(`/admin/groups/${g.id}`, { preserveScroll: true });
        }
    };

    return (
        <>
            <Head title="Manage Groups" />
            <Page title="Groups" subtitle="Review, flag or remove study groups.">
                <Panel>
                    <AdminSearch path="/admin/groups" initial={q} placeholder="Search group name" />
                    <div className="overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead className="text-muted-foreground text-left text-xs"><tr><th className="pb-2">Group</th><th>Subject</th><th>Owner</th><th>Members</th><th>Status</th><th /></tr></thead>
                            <tbody className="divide-y">
                                {groups.data.length === 0 && <tr><td colSpan={6} className="text-muted-foreground py-4">No groups found.</td></tr>}
                                {groups.data.map((g) => (
                                    <tr key={g.id}>
                                        <td className="py-2">{g.name}</td>
                                        <td>{g.subject}</td>
                                        <td>{g.owner}</td>
                                        <td>{g.members}/{g.max}</td>
                                        <td><Pill tone={g.flagged ? 'red' : 'green'}>{g.flagged ? 'Flagged' : 'Normal'}</Pill></td>
                                        <td className="space-x-2 text-right whitespace-nowrap">
                                            <Button size="sm" variant="outline" onClick={() => router.patch(`/admin/groups/${g.id}/flag`, {}, { preserveScroll: true })}>{g.flagged ? 'Unflag' : 'Flag'}</Button>
                                            <Button size="sm" variant="outline" onClick={() => remove(g)}>Delete</Button>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                    <Pager p={groups} />
                </Panel>
            </Page>
        </>
    );
}

AdminGroups.layout = { breadcrumbs: [{ title: 'Groups', href: '/admin/groups' }] };
