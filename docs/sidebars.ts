import type {SidebarsConfig} from '@docusaurus/plugin-content-docs';
import v1Sidebar from './docs/identity/api/v1/sidebar';
import v2Sidebar from './docs/identity/api/v2/sidebar';

const sidebars: SidebarsConfig = {
  identitySidebar: [
    {
      type: 'doc',
      id: 'identity/index',
      label: 'Introduction',
    },
    {
      type: 'category',
      label: 'Concepts',
      collapsed: false,
      items: [
        'identity/concepts/oauth-oidc-primer',
        'identity/concepts/tokens',
        'identity/concepts/scopes',
        'identity/concepts/audiences',
      ],
    },
    {
      type: 'category',
      label: 'Integration Guides',
      collapsed: false,
      items: [
        'identity/integration/getting-started',
        {
          type: 'doc',
          id: 'identity/integration/build-an-application',
          label: 'Build a User-Facing App',
        },
        'identity/integration/app-to-app',
      ],
    },
    {
      type: 'category',
      label: 'Platform Services',
      collapsed: false,
      items: [
        'identity/platform-services/conventions',
        'identity/platform-services/metadata',
        'identity/platform-services/notification-service',
      ],
    },
    {
      type: 'category',
      label: 'API v2',
      collapsed: true,
      items: v2Sidebar,
    },
    {
      type: 'category',
      label: 'API v1 (Deprecated)',
      collapsed: true,
      items: v1Sidebar,
    },
  ],
};

export default sidebars;
