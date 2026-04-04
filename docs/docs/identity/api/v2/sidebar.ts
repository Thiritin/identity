import type { SidebarsConfig } from "@docusaurus/plugin-content-docs";

const sidebar: SidebarsConfig = {
  apisidebar: [
    {
      type: "doc",
      id: "identity/api/v2/eurofurence-identity",
    },
    {
      type: "category",
      label: "Open ID Connect",
      link: {
        type: "doc",
        id: "identity/api/v2/open-id-connect",
      },
      items: [
        {
          type: "doc",
          id: "identity/api/v2/get-userinfo",
          label: "Get userinfo",
          className: "api-method get",
        },
        {
          type: "doc",
          id: "identity/api/v2/introspect-token",
          label: "Introspect token",
          className: "api-method post",
        },
      ],
    },
    {
      type: "category",
      label: "Staff",
      link: {
        type: "doc",
        id: "identity/api/v2/staff",
      },
      items: [
        {
          type: "doc",
          id: "identity/api/v2/get-staff-me",
          label: "Get own staff profile",
          className: "api-method get",
        },
        {
          type: "doc",
          id: "identity/api/v2/get-staff-list",
          label: "List all staff members",
          className: "api-method get",
        },
        {
          type: "doc",
          id: "identity/api/v2/get-staff-member",
          label: "Get staff member",
          className: "api-method get",
        },
      ],
    },
    {
      type: "category",
      label: "Groups",
      link: {
        type: "doc",
        id: "identity/api/v2/groups",
      },
      items: [
        {
          type: "doc",
          id: "identity/api/v2/get-group-tree",
          label: "Get group hierarchy tree",
          className: "api-method get",
        },
        {
          type: "doc",
          id: "identity/api/v2/get-groups",
          label: "List groups",
          className: "api-method get",
        },
        {
          type: "doc",
          id: "identity/api/v2/create-group",
          label: "Create group",
          className: "api-method post",
        },
        {
          type: "doc",
          id: "identity/api/v2/get-group",
          label: "Get group",
          className: "api-method get",
        },
        {
          type: "doc",
          id: "identity/api/v2/update-group",
          label: "Update group",
          className: "api-method put",
        },
        {
          type: "doc",
          id: "identity/api/v2/delete-group",
          label: "Delete group",
          className: "api-method delete",
        },
      ],
    },
    {
      type: "category",
      label: "Group Members",
      link: {
        type: "doc",
        id: "identity/api/v2/group-members",
      },
      items: [
        {
          type: "doc",
          id: "identity/api/v2/get-group-members",
          label: "List group members",
          className: "api-method get",
        },
        {
          type: "doc",
          id: "identity/api/v2/add-group-member",
          label: "Add member",
          className: "api-method post",
        },
        {
          type: "doc",
          id: "identity/api/v2/update-group-member",
          label: "Update member",
          className: "api-method patch",
        },
        {
          type: "doc",
          id: "identity/api/v2/remove-group-member",
          label: "Remove member",
          className: "api-method delete",
        },
      ],
    },
    {
      type: "category",
      label: "User Metadata",
      link: {
        type: "doc",
        id: "identity/api/v2/user-metadata",
      },
      items: [
        {
          type: "doc",
          id: "identity/api/v2/get-metadata",
          label: "List all metadata",
          className: "api-method get",
        },
        {
          type: "doc",
          id: "identity/api/v2/get-metadata-key",
          label: "Get metadata value",
          className: "api-method get",
        },
        {
          type: "doc",
          id: "identity/api/v2/put-metadata-key",
          label: "Create or update metadata",
          className: "api-method put",
        },
        {
          type: "doc",
          id: "identity/api/v2/delete-metadata-key",
          label: "Delete metadata",
          className: "api-method delete",
        },
      ],
    },
  ],
};

export default sidebar.apisidebar;
