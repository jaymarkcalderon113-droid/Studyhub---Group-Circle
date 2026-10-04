import { Head, router, usePage } from '@inertiajs/react';
import { AdminSearch, Pager } from '@/components/studyhub/admin-search';
import { Page, Panel, Pill } from '@/components/studyhub/ui';
import { Button } from '@/components/ui/button';

type User = { id: number; name: string; email: string; role: string; suspended: boolean; joined: string; can_manage: boolean };
type Props = {
    users: { data: User[]; prev_page_url: string | null; next_page_url: string | null; current_page: number; last_page: number; total: number };
    q: string;
};

/** PHASE 2F: real user list. Suspended users cannot log in (and are signed out at once). */
export default function AdminUsers({ users, q }: Props) {
    const { auth } = usePage().props;

    const toggle = (u: User) => {
        const msg = u.suspended ? `Reactivate ${u.name}?` : `Suspend ${u.name}? They will be signed out and cannot log in.`;
        if (confirm(msg)) router.patch(`/admin/users/${u.id}/suspend`, {}, { preserveScroll: true });
    };

    return (
        <>
            <Head title="Manage Users" />
            <Page title="Users" subtitle="Search and manage accounts.">
                <Panel>
                    <AdminSearch path="/admin/users" initial={q} placeholder="Search name or email" />
                    <div className="overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead className="text-muted-foreground text-left text-xs"><tr><th className="pb-2">Name</th><th>Email</th><th>Role</th><th>Status</th><th>Joined</th><th /></tr></thead>
                            <tbody className="divide-y">
                                {users.data.length === 0 && <tr><td colSpan={6} className="text-muted-foreground py-4">No users found.</td></tr>}
                                {users.data.map((u) => (
                                    <tr key={u.id}>
                                        <td className="py-2">{u.name}{u.id === auth.user.id && <span className="text-muted-foreground text-xs"> (you)</span>}</td>
                                        <td>{u.email}</td>
                                        <td><Pill tone={u.role === 'admin' ? 'blue' : 'gray'}>{u.role}</Pill></td>
                                        <td><Pill tone={u.suspended ? 'red' : 'green'}>{u.suspended ? 'Suspended' : 'Active'}</Pill></td>
                                        <td>{u.joined}</td>
                                        <td className="text-right">
                                            {u.can_manage && <Button size="sm" variant="outline" onClick={() => toggle(u)}>{u.suspended ? 'Reactivate' : 'Suspend'}</Button>}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                    <Pager p={users} />
                </Panel>
            </Page>
        </>
    );
}

AdminUsers.layout = { breadcrumbs: [{ title: 'Users', href: '/admin/users' }] };
