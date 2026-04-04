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
          id: "identity/api/v2/get-open-id-configuration",
          label: "OpenID Connect Discovery",
          className: "api-method get",
        },
        {
          type: "doc",
          id: "identity/api/v2/authorize-user",
          label: "Authorization Endpoint",
          className: "api-method get",
        },
        {
          type: "doc",
          id: "identity/api/v2/get-token",
          label: "Token Endpoint",
          className: "api-method post",
        },
        {
          type: "doc",
          id: "identity/api/v2/get-jwks",
          label: "JSON Web Key Set",
          className: "api-method get",
        },
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
    {
      type: "category",
      label: "Conventions",
      link: {
        type: "doc",
        id: "identity/api/v2/conventions",
      },
      items: [
        {
          type: "doc",
          id: "identity/api/v2/get-conventions",
          label: "List conventions",
          className: "api-method get",
        },
        {
          type: "doc",
          id: "identity/api/v2/get-current-convention",
          label: "Get current convention",
          className: "api-method get",
        },
      ],
    },
    {
      type: "category",
      label: "Schemas",
      items: [
        {
          type: "doc",
          id: "identity/api/v2/schemas/userinfo",
          label: "Userinfo",
          className: "schema",
        },
        {
          type: "doc",
          id: "identity/api/v2/schemas/tokenintrospectionrequest",
          label: "TokenIntrospectionRequest",
          className: "schema",
        },
        {
          type: "doc",
          id: "identity/api/v2/schemas/tokenintrospectionresponse",
          label: "TokenIntrospectionResponse",
          className: "schema",
        },
        {
          type: "doc",
          id: "identity/api/v2/schemas/staffprofile",
          label: "StaffProfile",
          className: "schema",
        },
        {
          type: "doc",
          id: "identity/api/v2/schemas/grouptreenode",
          label: "GroupTreeNode",
          className: "schema",
        },
        {
          type: "doc",
          id: "identity/api/v2/schemas/group",
          label: "Group",
          className: "schema",
        },
        {
          type: "doc",
          id: "identity/api/v2/schemas/groupsummary",
          label: "GroupSummary",
          className: "schema",
        },
        {
          type: "doc",
          id: "identity/api/v2/schemas/groupform",
          label: "GroupForm",
          className: "schema",
        },
        {
          type: "doc",
          id: "identity/api/v2/schemas/groupmember",
          label: "GroupMember",
          className: "schema",
        },
        {
          type: "doc",
          id: "identity/api/v2/schemas/addmemberform",
          label: "AddMemberForm",
          className: "schema",
        },
        {
          type: "doc",
          id: "identity/api/v2/schemas/updatememberform",
          label: "UpdateMemberForm",
          className: "schema",
        },
        {
          type: "doc",
          id: "identity/api/v2/schemas/metadata",
          label: "Metadata",
          className: "schema",
        },
        {
          type: "doc",
          id: "identity/api/v2/schemas/metadatavalue",
          label: "MetadataValue",
          className: "schema",
        },
        {
          type: "doc",
          id: "identity/api/v2/schemas/openidconfiguration",
          label: "OpenIDConfiguration",
          className: "schema",
        },
        {
          type: "doc",
          id: "identity/api/v2/schemas/tokenrequest",
          label: "TokenRequest",
          className: "schema",
        },
        {
          type: "doc",
          id: "identity/api/v2/schemas/tokenresponse",
          label: "TokenResponse",
          className: "schema",
        },
        {
          type: "doc",
          id: "identity/api/v2/schemas/oautherror",
          label: "OAuthError",
          className: "schema",
        },
        {
          type: "doc",
          id: "identity/api/v2/schemas/jsonwebkeyset",
          label: "JSONWebKeySet",
          className: "schema",
        },
        {
          type: "doc",
          id: "identity/api/v2/schemas/convention",
          label: "Convention",
          className: "schema",
        },
        {
          type: "doc",
          id: "identity/api/v2/schemas/error",
          label: "Error",
          className: "schema",
        },
      ],
    },
  ],
};

export default sidebar.apisidebar;
