import { Link } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { cn } from '@/lib/utils';

/** Page wrapper: consistent padding + title + optional action button. */
export function Page({
    title,
    subtitle,
    action,
    children,
}: {
    title: string;
    subtitle?: string;
    action?: ReactNode;
    children: ReactNode;
}) {
    return (
        <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
            <div className="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h1 className="text-2xl font-semibold tracking-tight">
                        {title}
                    </h1>
                    {subtitle && (
                        <p className="text-muted-foreground text-sm">
                            {subtitle}
                        </p>
                    )}
                </div>
                {action}
            </div>
            {children}
        </div>
    );
}

/** White rounded card used all over the mockups. */
export function Panel({
    className,
    children,
}: {
    className?: string;
    children: ReactNode;
}) {
    return (
        <div
            className={cn(
                'bg-card rounded-xl border p-5 shadow-sm',
                className,
            )}
        >
            {children}
        </div>
    );
}

/** Small colored pill (subjects, status, etc.). */
export function Pill({
    tone = 'blue',
    children,
}: {
    tone?: 'blue' | 'green' | 'orange' | 'gray' | 'red';
    children: ReactNode;
}) {
    const tones = {
        blue: 'bg-blue-100 text-blue-700',
        green: 'bg-emerald-100 text-emerald-700',
        orange: 'bg-amber-100 text-amber-700',
        gray: 'bg-muted text-muted-foreground',
        red: 'bg-red-100 text-red-700',
    };

    return (
        <span
            className={cn(
                'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium',
                tones[tone],
            )}
        >
            {children}
        </span>
    );
}

/** Selectable chip (subject / preference pickers). */
export function Chip({
    active,
    onClick,
    children,
}: {
    active: boolean;
    onClick: () => void;
    children: ReactNode;
}) {
    return (
        <button
            type="button"
            onClick={onClick}
            className={cn(
                'rounded-full border px-3 py-1 text-sm transition-colors',
                active
                    ? 'border-primary bg-primary text-primary-foreground'
                    : 'hover:border-primary/60 bg-background',
            )}
        >
            {children}
        </button>
    );
}

/** Round initials avatar (mock users have no photos). */
export function Initials({
    name,
    className,
}: {
    name: string;
    className?: string;
}) {
    const text = name
        .split(' ')
        .map((w) => w[0])
        .slice(0, 2)
        .join('')
        .toUpperCase();

    return (
        <span
            className={cn(
                'flex size-9 shrink-0 items-center justify-center rounded-full bg-blue-100 text-xs font-semibold text-blue-700',
                className,
            )}
        >
            {text}
        </span>
    );
}

/** Native <select> styled like our inputs (keeps the demo code short). */
export function SelectBox({
    value,
    onChange,
    options,
}: {
    value: string;
    onChange: (v: string) => void;
    options: string[];
}) {
    return (
        <select
            value={value}
            onChange={(e) => onChange(e.target.value)}
            className="border-input bg-background h-9 w-full rounded-md border px-3 text-sm shadow-xs"
        >
            {options.map((o) => (
                <option key={o}>{o}</option>
            ))}
        </select>
    );
}

/**
 * Row of buttons to switch between my groups (used by Chat and Notes & Files).
 * Clicking one reloads the page with ?group=ID.
 */
export function GroupTabs({
    groups,
    activeId,
    basePath,
}: {
    groups: { id: number; name: string }[];
    activeId?: number;
    basePath: string;
}) {
    return (
        <div className="flex flex-wrap gap-2">
            {groups.map((g) => (
                <Link
                    key={g.id}
                    href={`${basePath}?group=${g.id}`}
                    className={cn(
                        'rounded-full border px-3 py-1 text-sm transition-colors',
                        g.id === activeId
                            ? 'border-primary bg-primary text-primary-foreground'
                            : 'hover:border-primary/60 bg-background',
                    )}
                >
                    {g.name}
                </Link>
            ))}
        </div>
    );
}

/** Shown when the student is not in any group yet. */
export function NoGroups() {
    return (
        <Panel>
            <p className="text-muted-foreground text-sm">
                You are not in a group yet.{' '}
                <Link href="/findgroups" className="text-primary underline">Find a group</Link> or{' '}
                <Link href="/mygroups" className="text-primary underline">create one</Link> first.
            </p>
        </Panel>
    );
}
