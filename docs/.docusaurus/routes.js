import React from 'react';
import ComponentCreator from '@docusaurus/ComponentCreator';

export default [
  {
    path: '/__docusaurus/debug',
    component: ComponentCreator('/__docusaurus/debug', '5ff'),
    exact: true
  },
  {
    path: '/__docusaurus/debug/config',
    component: ComponentCreator('/__docusaurus/debug/config', '5ba'),
    exact: true
  },
  {
    path: '/__docusaurus/debug/content',
    component: ComponentCreator('/__docusaurus/debug/content', 'a2b'),
    exact: true
  },
  {
    path: '/__docusaurus/debug/globalData',
    component: ComponentCreator('/__docusaurus/debug/globalData', 'c3c'),
    exact: true
  },
  {
    path: '/__docusaurus/debug/metadata',
    component: ComponentCreator('/__docusaurus/debug/metadata', '156'),
    exact: true
  },
  {
    path: '/__docusaurus/debug/registry',
    component: ComponentCreator('/__docusaurus/debug/registry', '88c'),
    exact: true
  },
  {
    path: '/__docusaurus/debug/routes',
    component: ComponentCreator('/__docusaurus/debug/routes', '000'),
    exact: true
  },
  {
    path: '/',
    component: ComponentCreator('/', 'eb2'),
    routes: [
      {
        path: '/',
        component: ComponentCreator('/', 'ad0'),
        routes: [
          {
            path: '/',
            component: ComponentCreator('/', '66b'),
            routes: [
              {
                path: '/identity/api/v1/create-group',
                component: ComponentCreator('/identity/api/v1/create-group', '2ac'),
                exact: true,
                sidebar: "identitySidebar"
              },
              {
                path: '/identity/api/v1/create-group-user',
                component: ComponentCreator('/identity/api/v1/create-group-user', 'aa9'),
                exact: true,
                sidebar: "identitySidebar"
              },
              {
                path: '/identity/api/v1/delete-group',
                component: ComponentCreator('/identity/api/v1/delete-group', '302'),
                exact: true,
                sidebar: "identitySidebar"
              },
              {
                path: '/identity/api/v1/delete-group-user',
                component: ComponentCreator('/identity/api/v1/delete-group-user', '9d4'),
                exact: true,
                sidebar: "identitySidebar"
              },
              {
                path: '/identity/api/v1/eurofurence-identity',
                component: ComponentCreator('/identity/api/v1/eurofurence-identity', '969'),
                exact: true,
                sidebar: "identitySidebar"
              },
              {
                path: '/identity/api/v1/get-group',
                component: ComponentCreator('/identity/api/v1/get-group', 'c3e'),
                exact: true,
                sidebar: "identitySidebar"
              },
              {
                path: '/identity/api/v1/get-group-users',
                component: ComponentCreator('/identity/api/v1/get-group-users', 'a3f'),
                exact: true,
                sidebar: "identitySidebar"
              },
              {
                path: '/identity/api/v1/get-groups',
                component: ComponentCreator('/identity/api/v1/get-groups', 'ccc'),
                exact: true,
                sidebar: "identitySidebar"
              },
              {
                path: '/identity/api/v1/get-userinfo',
                component: ComponentCreator('/identity/api/v1/get-userinfo', 'ce4'),
                exact: true,
                sidebar: "identitySidebar"
              },
              {
                path: '/identity/api/v1/group-memberships',
                component: ComponentCreator('/identity/api/v1/group-memberships', '74e'),
                exact: true,
                sidebar: "identitySidebar"
              },
              {
                path: '/identity/api/v1/groups',
                component: ComponentCreator('/identity/api/v1/groups', '1fe'),
                exact: true,
                sidebar: "identitySidebar"
              },
              {
                path: '/identity/api/v1/introspect-token',
                component: ComponentCreator('/identity/api/v1/introspect-token', '295'),
                exact: true,
                sidebar: "identitySidebar"
              },
              {
                path: '/identity/api/v1/open-id-connect',
                component: ComponentCreator('/identity/api/v1/open-id-connect', '48c'),
                exact: true,
                sidebar: "identitySidebar"
              },
              {
                path: '/identity/api/v1/patch-group',
                component: ComponentCreator('/identity/api/v1/patch-group', '359'),
                exact: true,
                sidebar: "identitySidebar"
              },
              {
                path: '/identity/api/v1/put-group',
                component: ComponentCreator('/identity/api/v1/put-group', '6d4'),
                exact: true,
                sidebar: "identitySidebar"
              },
              {
                path: '/identity/api/v2/add-group-member',
                component: ComponentCreator('/identity/api/v2/add-group-member', '847'),
                exact: true,
                sidebar: "identitySidebar"
              },
              {
                path: '/identity/api/v2/authorize-user',
                component: ComponentCreator('/identity/api/v2/authorize-user', '9ef'),
                exact: true,
                sidebar: "identitySidebar"
              },
              {
                path: '/identity/api/v2/conventions',
                component: ComponentCreator('/identity/api/v2/conventions', 'c95'),
                exact: true,
                sidebar: "identitySidebar"
              },
              {
                path: '/identity/api/v2/create-group',
                component: ComponentCreator('/identity/api/v2/create-group', 'f31'),
                exact: true,
                sidebar: "identitySidebar"
              },
              {
                path: '/identity/api/v2/delete-group',
                component: ComponentCreator('/identity/api/v2/delete-group', '27f'),
                exact: true,
                sidebar: "identitySidebar"
              },
              {
                path: '/identity/api/v2/delete-metadata-key',
                component: ComponentCreator('/identity/api/v2/delete-metadata-key', '2fd'),
                exact: true,
                sidebar: "identitySidebar"
              },
              {
                path: '/identity/api/v2/eurofurence-identity',
                component: ComponentCreator('/identity/api/v2/eurofurence-identity', 'f0f'),
                exact: true,
                sidebar: "identitySidebar"
              },
              {
                path: '/identity/api/v2/get-conventions',
                component: ComponentCreator('/identity/api/v2/get-conventions', '17d'),
                exact: true,
                sidebar: "identitySidebar"
              },
              {
                path: '/identity/api/v2/get-current-convention',
                component: ComponentCreator('/identity/api/v2/get-current-convention', '220'),
                exact: true,
                sidebar: "identitySidebar"
              },
              {
                path: '/identity/api/v2/get-group',
                component: ComponentCreator('/identity/api/v2/get-group', '6e4'),
                exact: true,
                sidebar: "identitySidebar"
              },
              {
                path: '/identity/api/v2/get-group-members',
                component: ComponentCreator('/identity/api/v2/get-group-members', 'a84'),
                exact: true,
                sidebar: "identitySidebar"
              },
              {
                path: '/identity/api/v2/get-group-tree',
                component: ComponentCreator('/identity/api/v2/get-group-tree', '5ac'),
                exact: true,
                sidebar: "identitySidebar"
              },
              {
                path: '/identity/api/v2/get-groups',
                component: ComponentCreator('/identity/api/v2/get-groups', '0aa'),
                exact: true,
                sidebar: "identitySidebar"
              },
              {
                path: '/identity/api/v2/get-jwks',
                component: ComponentCreator('/identity/api/v2/get-jwks', '226'),
                exact: true,
                sidebar: "identitySidebar"
              },
              {
                path: '/identity/api/v2/get-metadata',
                component: ComponentCreator('/identity/api/v2/get-metadata', '4e2'),
                exact: true,
                sidebar: "identitySidebar"
              },
              {
                path: '/identity/api/v2/get-metadata-key',
                component: ComponentCreator('/identity/api/v2/get-metadata-key', 'ab9'),
                exact: true,
                sidebar: "identitySidebar"
              },
              {
                path: '/identity/api/v2/get-open-id-configuration',
                component: ComponentCreator('/identity/api/v2/get-open-id-configuration', 'aa9'),
                exact: true,
                sidebar: "identitySidebar"
              },
              {
                path: '/identity/api/v2/get-staff-list',
                component: ComponentCreator('/identity/api/v2/get-staff-list', 'c88'),
                exact: true,
                sidebar: "identitySidebar"
              },
              {
                path: '/identity/api/v2/get-staff-me',
                component: ComponentCreator('/identity/api/v2/get-staff-me', '83c'),
                exact: true,
                sidebar: "identitySidebar"
              },
              {
                path: '/identity/api/v2/get-staff-member',
                component: ComponentCreator('/identity/api/v2/get-staff-member', '430'),
                exact: true,
                sidebar: "identitySidebar"
              },
              {
                path: '/identity/api/v2/get-token',
                component: ComponentCreator('/identity/api/v2/get-token', '259'),
                exact: true,
                sidebar: "identitySidebar"
              },
              {
                path: '/identity/api/v2/get-userinfo',
                component: ComponentCreator('/identity/api/v2/get-userinfo', '68f'),
                exact: true,
                sidebar: "identitySidebar"
              },
              {
                path: '/identity/api/v2/group-members',
                component: ComponentCreator('/identity/api/v2/group-members', 'a2c'),
                exact: true,
                sidebar: "identitySidebar"
              },
              {
                path: '/identity/api/v2/groups',
                component: ComponentCreator('/identity/api/v2/groups', '976'),
                exact: true,
                sidebar: "identitySidebar"
              },
              {
                path: '/identity/api/v2/introspect-token',
                component: ComponentCreator('/identity/api/v2/introspect-token', 'f37'),
                exact: true,
                sidebar: "identitySidebar"
              },
              {
                path: '/identity/api/v2/notifications',
                component: ComponentCreator('/identity/api/v2/notifications', '216'),
                exact: true,
                sidebar: "identitySidebar"
              },
              {
                path: '/identity/api/v2/open-id-connect',
                component: ComponentCreator('/identity/api/v2/open-id-connect', '68a'),
                exact: true,
                sidebar: "identitySidebar"
              },
              {
                path: '/identity/api/v2/put-metadata-key',
                component: ComponentCreator('/identity/api/v2/put-metadata-key', '483'),
                exact: true,
                sidebar: "identitySidebar"
              },
              {
                path: '/identity/api/v2/remove-group-member',
                component: ComponentCreator('/identity/api/v2/remove-group-member', '983'),
                exact: true,
                sidebar: "identitySidebar"
              },
              {
                path: '/identity/api/v2/schemas/addmemberform',
                component: ComponentCreator('/identity/api/v2/schemas/addmemberform', '940'),
                exact: true,
                sidebar: "identitySidebar"
              },
              {
                path: '/identity/api/v2/schemas/convention',
                component: ComponentCreator('/identity/api/v2/schemas/convention', 'f49'),
                exact: true,
                sidebar: "identitySidebar"
              },
              {
                path: '/identity/api/v2/schemas/error',
                component: ComponentCreator('/identity/api/v2/schemas/error', 'bde'),
                exact: true,
                sidebar: "identitySidebar"
              },
              {
                path: '/identity/api/v2/schemas/group',
                component: ComponentCreator('/identity/api/v2/schemas/group', '64c'),
                exact: true,
                sidebar: "identitySidebar"
              },
              {
                path: '/identity/api/v2/schemas/groupform',
                component: ComponentCreator('/identity/api/v2/schemas/groupform', 'cb8'),
                exact: true,
                sidebar: "identitySidebar"
              },
              {
                path: '/identity/api/v2/schemas/groupmember',
                component: ComponentCreator('/identity/api/v2/schemas/groupmember', '09b'),
                exact: true,
                sidebar: "identitySidebar"
              },
              {
                path: '/identity/api/v2/schemas/groupsummary',
                component: ComponentCreator('/identity/api/v2/schemas/groupsummary', '898'),
                exact: true,
                sidebar: "identitySidebar"
              },
              {
                path: '/identity/api/v2/schemas/grouptreenode',
                component: ComponentCreator('/identity/api/v2/schemas/grouptreenode', '529'),
                exact: true,
                sidebar: "identitySidebar"
              },
              {
                path: '/identity/api/v2/schemas/jsonwebkeyset',
                component: ComponentCreator('/identity/api/v2/schemas/jsonwebkeyset', 'c71'),
                exact: true,
                sidebar: "identitySidebar"
              },
              {
                path: '/identity/api/v2/schemas/metadata',
                component: ComponentCreator('/identity/api/v2/schemas/metadata', '238'),
                exact: true,
                sidebar: "identitySidebar"
              },
              {
                path: '/identity/api/v2/schemas/metadatavalue',
                component: ComponentCreator('/identity/api/v2/schemas/metadatavalue', '040'),
                exact: true,
                sidebar: "identitySidebar"
              },
              {
                path: '/identity/api/v2/schemas/oautherror',
                component: ComponentCreator('/identity/api/v2/schemas/oautherror', 'e56'),
                exact: true,
                sidebar: "identitySidebar"
              },
              {
                path: '/identity/api/v2/schemas/openidconfiguration',
                component: ComponentCreator('/identity/api/v2/schemas/openidconfiguration', '054'),
                exact: true,
                sidebar: "identitySidebar"
              },
              {
                path: '/identity/api/v2/schemas/sendnotificationrequest',
                component: ComponentCreator('/identity/api/v2/schemas/sendnotificationrequest', '212'),
                exact: true,
                sidebar: "identitySidebar"
              },
              {
                path: '/identity/api/v2/schemas/staffprofile',
                component: ComponentCreator('/identity/api/v2/schemas/staffprofile', 'd42'),
                exact: true,
                sidebar: "identitySidebar"
              },
              {
                path: '/identity/api/v2/schemas/tokenintrospectionrequest',
                component: ComponentCreator('/identity/api/v2/schemas/tokenintrospectionrequest', '30d'),
                exact: true,
                sidebar: "identitySidebar"
              },
              {
                path: '/identity/api/v2/schemas/tokenintrospectionresponse',
                component: ComponentCreator('/identity/api/v2/schemas/tokenintrospectionresponse', '409'),
                exact: true,
                sidebar: "identitySidebar"
              },
              {
                path: '/identity/api/v2/schemas/tokenrequest',
                component: ComponentCreator('/identity/api/v2/schemas/tokenrequest', '1b7'),
                exact: true,
                sidebar: "identitySidebar"
              },
              {
                path: '/identity/api/v2/schemas/tokenresponse',
                component: ComponentCreator('/identity/api/v2/schemas/tokenresponse', 'f62'),
                exact: true,
                sidebar: "identitySidebar"
              },
              {
                path: '/identity/api/v2/schemas/updatememberform',
                component: ComponentCreator('/identity/api/v2/schemas/updatememberform', '519'),
                exact: true,
                sidebar: "identitySidebar"
              },
              {
                path: '/identity/api/v2/schemas/userinfo',
                component: ComponentCreator('/identity/api/v2/schemas/userinfo', '45a'),
                exact: true,
                sidebar: "identitySidebar"
              },
              {
                path: '/identity/api/v2/send-notification',
                component: ComponentCreator('/identity/api/v2/send-notification', 'b3a'),
                exact: true,
                sidebar: "identitySidebar"
              },
              {
                path: '/identity/api/v2/staff',
                component: ComponentCreator('/identity/api/v2/staff', 'e2f'),
                exact: true,
                sidebar: "identitySidebar"
              },
              {
                path: '/identity/api/v2/update-group',
                component: ComponentCreator('/identity/api/v2/update-group', '623'),
                exact: true,
                sidebar: "identitySidebar"
              },
              {
                path: '/identity/api/v2/update-group-member',
                component: ComponentCreator('/identity/api/v2/update-group-member', '2e5'),
                exact: true,
                sidebar: "identitySidebar"
              },
              {
                path: '/identity/api/v2/user-metadata',
                component: ComponentCreator('/identity/api/v2/user-metadata', '8f4'),
                exact: true,
                sidebar: "identitySidebar"
              },
              {
                path: '/identity/concepts/audiences',
                component: ComponentCreator('/identity/concepts/audiences', '4a6'),
                exact: true,
                sidebar: "identitySidebar"
              },
              {
                path: '/identity/concepts/groups',
                component: ComponentCreator('/identity/concepts/groups', 'fc6'),
                exact: true
              },
              {
                path: '/identity/concepts/oauth-oidc-primer',
                component: ComponentCreator('/identity/concepts/oauth-oidc-primer', '2b7'),
                exact: true,
                sidebar: "identitySidebar"
              },
              {
                path: '/identity/concepts/scopes',
                component: ComponentCreator('/identity/concepts/scopes', 'fb2'),
                exact: true,
                sidebar: "identitySidebar"
              },
              {
                path: '/identity/concepts/tokens',
                component: ComponentCreator('/identity/concepts/tokens', '69f'),
                exact: true,
                sidebar: "identitySidebar"
              },
              {
                path: '/identity/integration/app-to-app',
                component: ComponentCreator('/identity/integration/app-to-app', 'bde'),
                exact: true,
                sidebar: "identitySidebar"
              },
              {
                path: '/identity/integration/build-an-application',
                component: ComponentCreator('/identity/integration/build-an-application', '320'),
                exact: true,
                sidebar: "identitySidebar"
              },
              {
                path: '/identity/integration/getting-started',
                component: ComponentCreator('/identity/integration/getting-started', '4e3'),
                exact: true,
                sidebar: "identitySidebar"
              },
              {
                path: '/identity/platform-services/conventions',
                component: ComponentCreator('/identity/platform-services/conventions', 'add'),
                exact: true,
                sidebar: "identitySidebar"
              },
              {
                path: '/identity/platform-services/metadata',
                component: ComponentCreator('/identity/platform-services/metadata', 'a4e'),
                exact: true,
                sidebar: "identitySidebar"
              },
              {
                path: '/identity/platform-services/notification-service',
                component: ComponentCreator('/identity/platform-services/notification-service', 'e20'),
                exact: true,
                sidebar: "identitySidebar"
              },
              {
                path: '/',
                component: ComponentCreator('/', 'ce3'),
                exact: true,
                sidebar: "identitySidebar"
              }
            ]
          }
        ]
      }
    ]
  },
  {
    path: '*',
    component: ComponentCreator('*'),
  },
];
