import { usePage } from '@inertiajs/react';

/** Sidebar / header brand: the StudyCircle logo + app name. */
export default function AppLogo() {
    const { name } = usePage().props;

    return (
        <>
            <img
                src="/studycircle-logo.png"
                alt=""
                className="size-8 shrink-0 object-contain"
            />
            <div className="ml-1 grid flex-1 text-left text-sm">
                <span className="mb-0.5 truncate leading-tight font-semibold">
                    {name}
                </span>
            </div>
        </>
    );
}
