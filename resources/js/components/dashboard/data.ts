export const dashboardData = {
    greeting: {
        timeOfDay: 'Good evening',
        alerts: 1,
        updates: 2,
    },
    weather: {
        location: 'Sidi Bel Abbes',
        temp: 24,
        condition: 'Partly Cloudy',
        high: 28,
        low: 15,
        humidity: 42,
    },
    inboxes: [
        {
            name: '@fortera.co',
            icon: 'briefcase',
            iconColor: 'text-primary',
            unread: 12,
            bg: 'rgba(239,68,68,.15)',
            color: '#f87171',
            border: 'rgba(239,68,68,.25)',
        },
        {
            name: '@example.com',
            icon: 'code-2',
            iconColor: 'text-chart-3',
            unread: 4,
            bg: 'rgba(245,158,11,.15)',
            color: '#fbbf24',
            border: 'rgba(245,158,11,.25)',
        },
        {
            name: 'Gmail',
            icon: 'mail',
            iconColor: 'text-destructive',
            unread: 0,
            bg: 'rgba(255,255,255,.06)',
            color: '#64748b',
            border: 'rgba(255,255,255,.08)',
        },
    ],
    pihole: {
        queries: '82.3k',
        blockedPercent: 16,
        blockedToday: '~13.2k',
    },
    services: [
        {
            id: 'media',
            name: 'media-arr',
            badge: 'Media Stack',
            theme: {
                base: 'rgba(168,85,247)',
                icon: 'film',
                color: 'text-chart-1',
            },
            status: { type: 'warn', text: '1 update' },
            containersCount: 7,
            linkText: 'Media details',
            items: [
                {
                    name: 'Jellyfin',
                    icon: 'https://raw.githubusercontent.com/walkxcode/dashboard-icons/main/png/jellyfin.png',
                    status: 'Running'
                },
                {
                    name: 'Seerr',
                    icon: 'https://raw.githubusercontent.com/walkxcode/dashboard-icons/main/png/seerr.png',
                    status: 'Running'
                },
                {
                    name: 'Radarr',
                    icon: 'https://raw.githubusercontent.com/walkxcode/dashboard-icons/main/png/radarr.png',
                    status: 'Running'
                },
                {
                    name: 'Sonarr',
                    icon: 'https://raw.githubusercontent.com/walkxcode/dashboard-icons/main/png/sonarr.png',
                    status: 'Update!'
                },
                {
                    name: 'Transmission',
                    icon: 'https://raw.githubusercontent.com/walkxcode/dashboard-icons/main/png/transmission.png',
                    status: 'Running'
                },
                {
                    name: 'Prowlarr',
                    icon: 'https://raw.githubusercontent.com/walkxcode/dashboard-icons/main/png/prowlarr.png',
                    status: 'Running'
                },
                {
                    name: 'Bazarr',
                    icon: 'https://raw.githubusercontent.com/walkxcode/dashboard-icons/main/png/bazarr.png',
                    status: 'Running'
                }
            ],
        },
    ],
    servers: [
        {
            name: 'El-Baph',
            type: 'Home',
            ip: '192.168.1.10',
            status: 'Clear',
            statusType: 'ok',
            cpu: 20,
            ram: 45,
            disk: 65,
            cpuColor: 'bg-chart-3',
            ramColor: 'bg-primary',
            diskColor: 'bg-chart-1',
        },
        {
            name: 'Fortera-Prod',
            type: 'Prod',
            ip: '104.21.5.12',
            status: 'BOT',
            statusType: 'crit',
            alert: 'Netdata: CPU >98% 2hrs · bot crawl · ISP notified',
            cpu: 98,
            ram: 82,
            disk: 50,
            cpuColor: 'bg-destructive',
            ramColor: 'bg-chart-4',
            diskColor: 'bg-chart-1',
        },
        {
            name: 'Client-VPS-01',
            type: 'Client',
            ip: '172.64.3.1',
            status: 'OK',
            statusType: 'ok',
            cpu: 5,
            ram: 15,
            disk: 30,
            cpuColor: 'bg-chart-3',
            ramColor: 'bg-primary',
            diskColor: 'bg-chart-1',
        },
    ],
};
