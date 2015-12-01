# OAuth API

To use the OAuth API, a valid OAuth access token must be presented for each request.
If password authentication is enabled for a user account, the OAuth API can also be accessed via HTTP basic authentication.

The OAuth API only grants access to a limited set of functionality. No functionality requiring admin permissions is accessible
via this API, use the [Admin API](admin_api.md) with an API key instead.

## OAuth endpoints

* `/oauth/v2/token`: Token endpoint, grants a new access token when presented a refresh token.
* `/oauth/v2/auth`: Authorization endpoint, redirect the user to here with the proper query parameters to allow them to authorize your application.

## OAuth applications

A new OAuth application can be added at `/admin/oauth/clients` by a super-admin.
An application can be set to pre-approved, which means the user will not be shown an authorization page the first time
he connects to the application.
The scopes this pre-approval is valid for can be limited by pre-approved scopes. If more scopes are requested, the
authorization page is displayed.

## Serialization formats

All data is available in json and xml.
The html format is not stable, and not meant for machine usage (but it is available anyways).
When no `Accept` header or file extension is provided, the json format is used.

The json format is used when `Accept: application/json` is sent as a request header, or when the URL ends in `.json`.
This format is the recommended format for all usages of the API.

The xml format is used when `Accept: text/xml` is sent as a request header, or when the URL ends in `.xml`.
This format is not recommended, but is available for the benefit of people who find json to mainstream.

## Pagination

See the [Admin API](./admin_api.md#pagination)

## Endpoints

### `GET /api/user`

Provides information about the logged-in user.

| Field      | Required scope     | Description |
| ---------- | ------------------ | ----------- |
| `guid`     | None               | The unique identifier of the user. Unique, never changes and cannot be reused after deletion. |
| `username` | `profile:username` | The username of the user. Unique within an installation, may be changed by an admin and may be reused for another user. |
| `name`     | `profile:realname` | The real name of the user. Is not unique within an installation and may be changed. |
| `groups`   | `profile:groups`   | The groups the user is member of (directly or indirectly). Only groups with the `exportable` flag are listed here. Use these to determine the authorization level of the user. |

    {
        "guid":"5FC0F82D-1E70-45E7-B620-781456E6CE10",
        "username":"vierbergenlars",
        "name":"Lars Vierbergen",
        "groups":["%sysops","opswiki_users"]
    }

### `GET /api/groups`

Lists the groups the user can join and leave.

Requires scope `group:join` or `group:leave`.

The returned object contains 2 arrays: `joinable` contains all the groups you can join, `leaveable` contains all the groups you can leave.

    {
        "joinable":[
            {
                "name":"kd_admin",
                "display_name":"KD Admins"
            },
            {
                "name":"ski_15",
                "display_name":"Skitrip 2015"
            }
        ],
        "leaveable":[
            {
                "name":"%sysops",
                "display_name":"Sysops"
            }
        ]
    }
            
### `PATCH /api/groups/join/{name}`

Joins a joinable group. When the user is already a member of the group, no action is taken.

Requires scope `group:join`.

    $ curl -uadmin:admin http://192.168.80.2/api/groups/join/kd_admin -XPATCH -v
    > PATCH /api/groups/join/kd_admin
    > Authorization: Basic YWRtaW46YWRtaW4=
    < HTTP/1.1 204 No Content
    < Content-Length: 0

### `PATCH /api/groups/leave/{name}`

Leaves a leaveable group. When the user is no member of the group, no action is taken.

Required scope `group:leave`.

    $ curl -uadmin:admin http://192.168.80.2/api/groups/leave/%25sysops -XPATCH -v
    > PATCH /api/groups/leave/%25sysops
    > Authorization: Basic YWRtaW46YWRtaW4=
    < HTTP/1.1 204 No Content
    < Content-Length: 0

### `GET /api/properties/{namespace}`

Lists all properties in a namespace.
This listing only contains the properties that have been set on the user previously.

Requires scope `property:read`
Requires the namespace to be globally readable, or the application to have read permissions for the namespace.

The returned object contains the keys `name`, the name of the property itself and `_links.self.href`, the URL to fetch
the contents of the property.

### `GET /api/properties/{namespace}/{name}`

Fetch the content of a property.

Requires scope `property:read`
Requires the namespace to be globally readable, or the application to have read permissions for the namespace.

The content of the property is returned as it was previously inserted. The `Content-Type` header that was used when
creating the property is used as content type.

### `PUT /api/properties/{namespace}/{name}`

Updates the content of a property. If the property does not exist, it is created.

Requires scope `property:write`
Requires the namespace to be globally writeable, or the application to have write permissions for the namespace.

The body of the request is stored as-is and will be returned verbatim on a corresponding GET request.
The `Content-Type` header used for this request is also stored and returned when the property is fetched.

### `DELETE /api/properties/{namespace}/{name}`

Removes a property.

Requires scope `property:write`
Requires the namespace to be globally writeable, or the application to have write permissions for the namespace.
