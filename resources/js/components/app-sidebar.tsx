import { Link, usePage } from '@inertiajs/react';
import {
    Bell,
    CalendarDays,
    CheckSquare,
    FileText,
    LayoutGrid,
    MessageSquare,
    Search,
    Shield,
    Star,
    TrendingUp,
    User as UserIcon,
    Users,
} from 'lucide-react';
import AppLogo from '@/components/app-logo';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import type { NavItem } from '@/types';

/** Menu shown to students. */
const studentNav: NavItem[] = [
    { title: 'Dashboard', href: '/dashboard', icon: LayoutGrid },
    { title: 'Find Groups', href: '/findgroups', icon: Search },
    { title: 'My Groups', href: '/mygroups', icon: Users },
    { title: 'Messages', href: '/messages', icon: MessageSquare },
    { title: 'Notes & Files', href: '/notes', icon: FileText },
    { title: 'Schedule', href: '/schedule', icon: CalendarDays },
    { title: 'Tasks', href: '/tasks', icon: CheckSquare },
    { title: 'Progress', href: '/progress', icon: TrendingUp },
    { title: 'Ratings', href: '/ratings', icon: Star },
    { title: 'Notifications', href: '/notifications', icon: Bell },
    { title: 'My Profile', href: '/my-profile', icon: UserIcon },
];

/** Menu shown to admins. */
const adminNav: NavItem[] = [
    { title: 'Admin Dashboard', href: '/admin/dashboard', icon: Shield },
    { title: 'Users', href: '/admin/users', icon: Users },
    { title: 'Groups', href: '/admin/groups', icon: LayoutGrid },
];

export function AppSidebar() {
    const { auth } = usePage().props;
    const isAdmin = auth.user?.role === 'admin';
    const items = isAdmin ? adminNav : studentNav;

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={items[0].href} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={items} />
            </SidebarContent>

            <SidebarFooter>
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
