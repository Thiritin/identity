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
      label: 'Guides',
      collapsed: false,
      items: [
        'identity/guides/getting-started',
        'identity/guides/build-an-application',
        'identity/guides/scopes',
      ],
    },
    {
      type: 'category',
      label: 'API v1',
      collapsed: false,
      items: v1Sidebar,
    },
    {
      type: 'category',
      label: 'API v2',
      collapsed: false,
      items: v2Sidebar,
    },
  ],
};

export default sidebars;
