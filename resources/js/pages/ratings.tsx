import { Head, router } from '@inertiajs/react';
import { Star } from 'lucide-react';
import { useState } from 'react';
import { NoGroups, Page, Panel, Pill } from '@/components/studyhub/ui';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

type Group = {
    id: number;
    name: string;
    subject: string;
    is_owner: boolean;
    average: number | null;
    count: number;
    my: { stars: number; comment: string } | null;
    reviews: { id: number; author: string; stars: number; comment: string | null; date: string }[];
};

/** Star row. When `onPick` is given the stars are clickable. */
function Stars({ value, onPick }: { value: number; onPick?: (n: number) => void }) {
    return (
        <span className="inline-flex items-center gap-0.5">
            {[1, 2, 3, 4, 5].map((n) => (
                <button key={n} type="button" disabled={!onPick} aria-label={`${n} stars`} onClick={() => onPick?.(n)} className={onPick ? 'cursor-pointer' : 'cursor-default'}>
                    <Star className={`size-5 ${n <= Math.round(value) ? 'fill-amber-400 text-amber-400' : 'text-muted-foreground'}`} />
                </button>
            ))}
        </span>
    );
}

/** One group's card: average, my rating form, and the latest reviews. */
function GroupCard({ g }: { g: Group }) {
    const [stars, setStars] = useState(g.my?.stars ?? 0);
    const [comment, setComment] = useState(g.my?.comment ?? '');
    const [saving, setSaving] = useState(false);

    const submit = () => {
        setSaving(true);
        router.post(`/groups/${g.id}/ratings`, { stars, comment }, { preserveScroll: true, onFinish: () => setSaving(false) });
    };

    return (
        <Panel className="space-y-3">
            <div className="flex items-start justify-between gap-2">
                <div>
                    <h3 className="font-semibold">{g.name}</h3>
                    <Pill>{g.subject}</Pill>
                </div>
                <div className="text-right text-sm">
                    <p className="font-semibold">{g.average ?? '–'} <span className="text-muted-foreground font-normal">({g.count} {g.count === 1 ? 'review' : 'reviews'})</span></p>
                    <Stars value={g.average ?? 0} />
                </div>
            </div>

            {g.is_owner ? (
                <p className="text-muted-foreground text-xs">You own this group, so you can't rate it.</p>
            ) : (
                <div className="space-y-2 border-t pt-3">
                    <p className="text-sm font-medium">{g.my ? 'Your rating' : 'Rate this group'}</p>
                    <Stars value={stars} onPick={setStars} />
                    <Input value={comment} maxLength={300} onChange={(e) => setComment(e.target.value)} placeholder="Say something about this group (optional)" />
                    <Button size="sm" disabled={!stars || saving} onClick={submit}>{g.my ? 'Update rating' : 'Submit rating'}</Button>
                </div>
            )}

            {g.reviews.length > 0 && (
                <ul className="space-y-2 border-t pt-3 text-sm">
                    {g.reviews.map((r) => (
                        <li key={r.id}>
                            <div className="flex items-center gap-2"><span className="font-medium">{r.author}</span><Stars value={r.stars} /><span className="text-muted-foreground text-xs">{r.date}</span></div>
                            {r.comment && <p className="text-muted-foreground">{r.comment}</p>}
                        </li>
                    ))}
                </ul>
            )}
        </Panel>
    );
}

/** PHASE 2E: group ratings from the database. */
export default function Ratings({ groups }: { groups: Group[] }) {
    return (
        <>
            <Head title="Ratings" />
            <Page title="Group Ratings" subtitle="Rate the groups you study with and see how they are doing.">
                {groups.length === 0 ? (
                    <NoGroups />
                ) : (
                    <div className="grid gap-4 md:grid-cols-2">{groups.map((g) => <GroupCard key={g.id} g={g} />)}</div>
                )}
            </Page>
        </>
    );
}

Ratings.layout = { breadcrumbs: [{ title: 'Ratings', href: '/ratings' }] };
