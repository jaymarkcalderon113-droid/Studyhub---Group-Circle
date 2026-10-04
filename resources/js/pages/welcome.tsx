import { Head, Link, usePage } from '@inertiajs/react';
import { Button } from '@/components/ui/button';

/** Public landing page (no sidebar; see the layout switch in app.tsx). */
export default function Welcome() {
    const { auth, name } = usePage().props;
    // Signed-in users go straight to the dashboard of their role.
    const home = auth.user?.role === 'admin' ? '/admin/dashboard' : '/dashboard';

    const features = [
        ['Find Study Partners', 'Match with students who share your subjects, level and goals.'],
        ['Join or Create Groups', 'Build your own study group or join an existing one.'],
        ['Stay Productive', 'Track progress, share notes and achieve more together.'],
    ];

    return (
        <>
            <Head title="Find Your Study Circle" />
            <div className="min-h-screen bg-gradient-to-b from-blue-50 to-white">
                <header className="mx-auto flex max-w-6xl items-center justify-between px-6 py-4">
                    <div className="flex items-center gap-2 text-lg font-semibold">
                        <img src="/studycircle-logo.png" alt="" className="size-9" />
                        {name}
                    </div>
                    <nav className="flex items-center gap-2">
                        {auth.user ? (
                            <Button asChild><Link href={home}>Dashboard</Link></Button>
                        ) : (
                            <>
                                <Button asChild variant="ghost"><Link href="/login">Login</Link></Button>
                                <Button asChild><Link href="/register">Sign Up</Link></Button>
                            </>
                        )}
                    </nav>
                </header>

                <main className="mx-auto max-w-6xl px-6 py-16">
                    <div className="grid items-center gap-10 md:grid-cols-2">
                        <div>
                            <h1 className="text-5xl leading-tight font-bold tracking-tight text-[#16357a]">
                                Find Your <br /> Study Circle
                            </h1>
                            <p className="text-muted-foreground mt-4 max-w-md">
                                Connect with students who share your subjects, schedule, goals and
                                learning style. Study together, achieve more!
                            </p>
                        </div>
                        <img src="/hub.png" alt="StudyCircle" className="mx-auto w-64 md:w-80" />
                    </div>

                    <div className="mt-16 grid gap-6 md:grid-cols-3">
                        {features.map(([title, text]) => (
                            <div key={title} className="rounded-xl border bg-white p-5 shadow-sm">
                                <h3 className="font-semibold">{title}</h3>
                                <p className="text-muted-foreground mt-1 text-sm">{text}</p>
                            </div>
                        ))}
                    </div>
                </main>
            </div>
        </>
    );
}
