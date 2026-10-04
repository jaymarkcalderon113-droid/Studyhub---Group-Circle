import { Head, router } from '@inertiajs/react';
import { Page, Panel } from '@/components/studyhub/ui';
import { Button } from '@/components/ui/button';

type Item = { id: number; type: string; title: string; body: string | null; link: string | null; read: boolean; time: string };

/**
 * PHASE 2E: real notifications.
 * Clicking one marks it as read, then opens the page it points to.
 */
export default function Notifications({ items }: { items: Item[] }) {
    const open = (n: Item) =>
        router.patch(`/notifications/${n.id}/read`, {}, {
            preserveScroll: true,
            onSuccess: () => n.link && router.visit(n.link),
        });

    return (
        <>
            <Head title="Notifications" />
            <Page
                title="Notifications"
                action={items.some((n) => !n.read) && <Button variant="outline" size="sm" onClick={() => router.post('/notifications/read-all', {}, { preserveScroll: true })}>Mark all as read</Button>}
            >
                <Panel className="p-0">
                    {items.length === 0 && <p className="text-muted-foreground p-4 text-sm">Nothing yet. Alerts about join requests, new messages, files and sessions will show up here.</p>}
                    <ul className="divide-y">
                        {items.map((n) => (
                            <li key={n.id}>
                                <button type="button" onClick={() => open(n)} className="hover:bg-muted/50 flex w-full items-start gap-3 p-4 text-left">
                                    <span className={`mt-1.5 size-2 shrink-0 rounded-full ${n.read ? 'bg-transparent' : 'bg-primary'}`} />
                                    <span className="flex-1">
                                        <span className={`block text-sm ${n.read ? '' : 'font-semibold'}`}>{n.title}</span>
                                        {n.body && <span className="text-muted-foreground block text-xs">{n.body}</span>}
                                    </span>
                                    <span className="text-muted-foreground shrink-0 text-xs">{n.time}</span>
                                </button>
                            </li>
                        ))}
                    </ul>
                </Panel>
            </Page>
        </>
    );
}

Notifications.layout = { breadcrumbs: [{ title: 'Notifications', href: '/notifications' }] };
