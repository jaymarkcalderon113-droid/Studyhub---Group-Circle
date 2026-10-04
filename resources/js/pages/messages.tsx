import { Head, router, useForm } from '@inertiajs/react';
import { Send } from 'lucide-react';
import { useEffect, useRef } from 'react';
import { GroupTabs, Initials, NoGroups, Page, Panel, Pill } from '@/components/studyhub/ui';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

type Props = {
    groups: { id: number; name: string }[];
    active: { id: number; name: string; subject: string; skill_level: string; max: number } | null;
    messages: { id: number; body: string; author: string; mine: boolean; time: string }[];
    members: string[];
};

/**
 * PHASE 2C: real group chat.
 * - Sending posts to /groups/{id}/messages.
 * - Every 5 seconds we ask Laravel only for fresh messages (simple "polling",
 *   no websockets needed).
 */
export default function Messages({ groups, active, messages, members }: Props) {
    const form = useForm({ body: '' });
    const box = useRef<HTMLDivElement>(null);

    // Keep the newest message in view.
    useEffect(() => {
        if (box.current) box.current.scrollTop = box.current.scrollHeight;
    }, [messages.length, active?.id]);

    // Poll for new messages while the tab is visible.
    useEffect(() => {
        if (!active) return;
        const timer = setInterval(() => {
            if (!document.hidden) router.reload({ only: ['messages', 'members'] });
        }, 5000);
        return () => clearInterval(timer);
    }, [active?.id]);

    const send = () => {
        if (!active || !form.data.body.trim()) return;
        form.post(`/groups/${active.id}/messages`, { preserveScroll: true, onSuccess: () => form.reset() });
    };

    return (
        <>
            <Head title="Group Chat" />
            <Page title={active ? active.name : 'Group Chat'} subtitle={active ? `${members.length}/${active.max} members · ${active.skill_level}` : undefined}>
                {groups.length === 0 || !active ? (
                    <NoGroups />
                ) : (
                    <>
                        <GroupTabs groups={groups} activeId={active.id} basePath="/messages" />
                        <div className="grid gap-4 lg:grid-cols-4">
                            <Panel className="flex h-[28rem] flex-col lg:col-span-3">
                                <div ref={box} className="flex-1 space-y-3 overflow-y-auto">
                                    {messages.length === 0 && <p className="text-muted-foreground text-sm">No messages yet. Say hi!</p>}
                                    {messages.map((m) => (
                                        <div key={m.id} className={`flex items-end gap-2 ${m.mine ? 'flex-row-reverse' : ''}`}>
                                            <Initials name={m.author} className="size-8" />
                                            <div className={`max-w-[70%] rounded-2xl px-3 py-2 text-sm ${m.mine ? 'bg-primary text-primary-foreground' : 'bg-muted'}`}>
                                                <p className="text-xs opacity-70">{m.author} · {m.time}</p>
                                                <p className="break-words whitespace-pre-wrap">{m.body}</p>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                                <div className="mt-3 flex gap-2">
                                    <Input
                                        value={form.data.body}
                                        maxLength={1000}
                                        onChange={(e) => form.setData('body', e.target.value)}
                                        onKeyDown={(e) => e.key === 'Enter' && send()}
                                        placeholder="Type a message..."
                                    />
                                    <Button onClick={send} disabled={form.processing} size="icon" aria-label="Send"><Send /></Button>
                                </div>
                            </Panel>
                            <Panel>
                                <h2 className="mb-3 font-semibold">Members</h2>
                                <ul className="space-y-2">
                                    {members.map((m) => (
                                        <li key={m} className="flex items-center gap-2 text-sm"><Initials name={m} className="size-8" />{m}</li>
                                    ))}
                                </ul>
                                <div className="mt-4 flex gap-1"><Pill>{active.subject}</Pill></div>
                            </Panel>
                        </div>
                    </>
                )}
            </Page>
        </>
    );
}

Messages.layout = { breadcrumbs: [{ title: 'Messages', href: '/messages' }] };
