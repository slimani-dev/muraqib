
import { edit as editProfile } from '@/routes/profile';
import { show } from '@/routes/two-factor';
import { edit as editPassword } from '@/routes/user-password';
import { type NavItem } from '@/types';

import routeSettings from '@/routes/settings';

export const sidebarNavItems: NavItem[] = [
    {
        title: 'General',
        href: routeSettings.general.url(),
    },
    {
        title: 'Infrastructure',
        href: routeSettings.infrastructure.url(),
    },
    {
        title: 'Media',
        href: routeSettings.media.url(),
    },
    {
        title: 'Developer',
        href: routeSettings.developer.url(),
    },
    {
        title: 'Profile',
        href: editProfile(),
    },
    {
        title: 'Password',
        href: editPassword(),
    },
    {
        title: 'Two-Factor Auth',
        href: show(),
    },
];
