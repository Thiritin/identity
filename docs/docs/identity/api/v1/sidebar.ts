import type { SidebarsConfig } from "@docusaurus/plugin-content-docs";

const sidebar: SidebarsConfig = {
  apisidebar: [
    {
      type: "doc",
      id: "identity/api/v1/eurofurence-identity",
    },
    {
      type: "category",
      label: "Open ID Connect",
      link: {
        type: "doc",
        id: "identity/api/v1/open-id-connect",
      },
      items: [
        {
          type: "doc",
          id: "identity/api/v1/get-userinfo",
          label: "Get userinfo of the user",
          className: "api-method get",
        },
        {
          type: "doc",
          id: "identity/api/v1/introspect-token",
          label: "Introspect Token",
          className: "api-method post",
        },
      ],
    },
    {
      type: "category",
      label: "Groups",
      link: {
        type: "doc",
        id: "identity/api/v1/groups",
      },
      items: [
        {
          type: "doc",
          id: "identity/api/v1/get-groups",
          label: "Get all groups",
          className: "api-method get",
        },
        {
          type: "doc",
          id: "identity/api/v1/create-group",
          label: "Create a new group",
          className: "api-method post",
        },
        {
          type: "doc",
          id: "identity/api/v1/get-group",
          label: "Get single group",
          className: "api-method get",
        },
        {
          type: "doc",
          id: "identity/api/v1/put-group",
          label: "Update single group",
          className: "api-method put",
        },
        {
          type: "doc",
          id: "identity/api/v1/patch-group",
          label: "Update single group",
          className: "api-method patch",
        },
        {
          type: "doc",
          id: "identity/api/v1/delete-group",
          label: "Deletes a group",
          className: "api-method delete",
        },
      ],
    },
    {
      type: "category",
      label: "Group Memberships",
      link: {
        type: "doc",
        id: "identity/api/v1/group-memberships",
      },
      items: [
        {
          type: "doc",
          id: "identity/api/v1/get-group-users",
          label: "List members",
          className: "api-method get",
        },
        {
          type: "doc",
          id: "identity/api/v1/create-group-user",
          label: "Add member",
          className: "api-method post",
        },
        {
          type: "doc",
          id: "identity/api/v1/delete-group-user",
          label: "Remove member",
          className: "api-method delete",
        },
      ],
    },
  ],
};

export default sidebar.apisidebar;
