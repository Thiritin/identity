export const sidebarItems = [
    { key: 'general', label: 'General', route: 'developers.general' },
    { key: 'oauth', label: 'OAuth', route: 'developers.oauth' },
    { key: 'logout', label: 'Logout', route: 'developers.logout' },
    { key: 'credentials', label: 'Credentials', route: 'developers.credentials' },
    { key: 'notification-types', label: 'Notification Types', route: 'developers.notification-types.index', requiresNotifications: true },
    { key: 'webhooks', label: 'Webhooks', route: 'developers.webhooks', firstPartyOnly: true },
    { key: 'danger', label: 'Danger', route: 'developers.danger' },
]
