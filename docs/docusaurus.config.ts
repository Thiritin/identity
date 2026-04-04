import {themes as prismThemes} from 'prism-react-renderer';
import type {Config} from '@docusaurus/types';
import type * as Preset from '@docusaurus/preset-classic';
import type * as OpenApiPlugin from "docusaurus-plugin-openapi-docs";
import type * as Plugin from "@docusaurus/types/src/plugin";

const config: Config = {
  title: 'Eurofurence Identity',
  tagline: 'API Documentation for the Eurofurence Identity Service',
  favicon: 'img/favicon.ico',

  url: 'https://identity-docs.eurofurence.org',
  baseUrl: '/',

  organizationName: 'Thiritin',
  projectName: 'identity',

  onBrokenLinks: 'throw',

  markdown: {
    hooks: {
      onBrokenMarkdownLinks: 'warn',
    },
  },

  future: {
    v4: false,
    experimental_faster: false,
  },

  i18n: {
    defaultLocale: 'en',
    locales: ['en'],
  },

  presets: [
    [
      'classic',
      {
        docs: {
          routeBasePath: '/',
          sidebarPath: './sidebars.ts',
          docItemComponent: "@theme/ApiItem",
        },
        blog: false,
        theme: {
          customCss: './src/css/custom.css',
        },
      } satisfies Preset.Options,
    ],
  ],

  plugins: [
    function nodePolyfillPlugin() {
      return {
        name: 'node-polyfill-plugin',
        configureWebpack() {
          return {
            resolve: {
              fallback: {
                path: 'path-browserify',
                fs: false,
              },
            },
          };
        },
      };
    },
    [
      "docusaurus-plugin-openapi-docs",
      {
        id: "openapi",
        docsPluginId: "classic",
        config: {
          "identity-v1": {
            specPath: "static/contracts/identity/api/v1/identity.oas.1.0.yml",
            outputDir: "docs/identity/api/v1",
            downloadUrl:
              "../../contracts/identity/api/v1/identity.oas.1.0.yml",
            sidebarOptions: {
              groupPathsBy: "tag",
              categoryLinkSource: "tag",
            },
          } satisfies OpenApiPlugin.Options,
          "identity-v2": {
            specPath: "static/contracts/identity/api/v2/identity.oas.2.0.yml",
            outputDir: "docs/identity/api/v2",
            downloadUrl:
              "../../contracts/identity/api/v2/identity.oas.2.0.yml",
            sidebarOptions: {
              groupPathsBy: "tag",
              categoryLinkSource: "tag",
            },
          } satisfies OpenApiPlugin.Options,
        } satisfies Plugin.PluginOptions,
      },
    ],
  ],

  themes: ["docusaurus-theme-openapi-docs"],

  themeConfig: {
    navbar: {
      title: 'Eurofurence Identity',
      logo: {
        alt: 'Eurofurence e.V. Logo',
        src: 'img/ef-logo.svg',
      },
      items: [
        {
          type: 'docSidebar',
          sidebarId: 'identitySidebar',
          position: 'left',
          label: 'API Docs',
        },
        {
          href: 'https://github.com/Thiritin/identity',
          label: 'GitHub',
          position: 'right',
        },
      ],
    },
    footer: {
      style: 'dark',
      links: [
        {
          title: 'Docs',
          items: [
            {
              label: 'API Documentation',
              to: '/identity/api/v1/eurofurence-identity',
            },
          ],
        },
        {
          title: 'More',
          items: [
            {
              label: 'GitHub',
              href: 'https://github.com/Thiritin/identity',
            },
          ],
        },
      ],
      copyright: `Copyright © ${new Date().getFullYear()} Eurofurence e.V.`,
    },
    prism: {
      theme: prismThemes.github,
      darkTheme: prismThemes.dracula,
      additionalLanguages: ['bash', 'json', 'php'],
    },
  } satisfies Preset.ThemeConfig,
};

export default async function createConfig() {
  return config;
}
