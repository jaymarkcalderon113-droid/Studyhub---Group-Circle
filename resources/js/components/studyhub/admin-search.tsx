import { router } from '@inertiajs/react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

/** Search box used by the admin tables. Submits as GET /path?q=... */
export function AdminSearch({ path, initial, placeholder }: { path: string; initial: string; placeholder: string }) {
    const [q, setQ] = useState(initial);
    const go = () => router.get(path, q ? { q } : {}, { preserveState: true, replace: true });

    return (
        <div className="mb-4 flex max-w-md gap-2">
            <Input value={q} onChange={(e) => setQ(e.target.value)} onKeyDown={(e) => e.key === 'Enter' && go()} placeholder={placeholder} />
            <Button variant="outline" onClick={go}>Search</Button>
        </div>
    );
}

type Paginated = { prev_page_url: string | null; next_page_url: string | null; current_page: number; last_page: number; total: number };

/** Previous / Next buttons for a Laravel paginator. */
export function Pager({ p }: { p: Paginated }) {
    return (
        <div className="mt-4 flex items-center justify-between text-sm">
            <span className="text-muted-foreground">{p.total} total · page {p.current_page} of {p.last_page}</span>
            <span className="flex gap-2">
                <Button size="sm" variant="outline" disabled={!p.prev_page_url} onClick={() => p.prev_page_url && router.get(p.prev_page_url)}>‹ Prev</Button>
                <Button size="sm" variant="outline" disabled={!p.next_page_url} onClick={() => p.next_page_url && router.get(p.next_page_url)}>Next ›</Button>
            </span>
        </div>
    );
}
