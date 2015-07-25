# Admin API

To use the Admin API, you need to be authenticated with an API key or be logged in as an administrator in the same session.

However, when you are logged in as an administrator and are using the API, CSRF protection is not disabled and unless you
sniff the token from the html form, you have to be a very lucky man to get your submitted data accepted.

## API keys

New api keys can be created by a super-admin at `/admin/apikeys/new`.
You can select for which scopes the key is valid. At least one scope has to be selected to be able to create the API key.

## Serialization formats

All data is available in 3 serialization formats: html, json and xml.
The html format is not stable, and not meant for machine usage (but it is available anyways).
When no `Accept` header or file extension is provided, the html format is used.

The json format is used when `Accept: application/json` is sent as a request header, or when the URL ends in `.json`.
This format is the recommended format for all usages of the API.

The xml format is used when `Accept: text/xml` is sent as a request header, or when the URL ends in `.xml`.
This format is not recommended, but is available for the benefit of people who find json to mainstream.

When you are authenticated in a browser, you can view the json or xml format simply by appending `.json` or `.xml` to the URL.
This way, you can see what your API client will see in a simple way.

## Response codes

You can expect a couple of HTTP response codes when using the API. Standard HTTP semantics apply: 2xx is success,
4xx means you fucked up, 5xx means we fucked up. The server will not send 3xx response codes.

| Code | Meaning |
| ---- | ------- |
| 200  | Great success! There is some data available in the body for you to process. Only used to respond to `GET` requests. |
| 201  | A new resource has been created. The resource URL for the new resource is available in the `Location` response header, the response body is empty. Only used to respond to `POST` requests. |
| 204  | Your request has been processed successfully, but there is no information to send to you about it. Typical response to a non-`GET` request. |
| 400  | Your application submitted a badly formatted request or invalid data. An explanatory message may be present in the response body. |
| 401  | You are not logged in. Please provide an API key via HTTP basic authentication. |
| 403  | You are not logged in, or the requested resource is outside the scope of your API key. An explanatory message may be present in the response body. |
| 404  | The thing you were looking for does not exist. |
| 405  | The method you're using is not defined for this resource. Check the `Allow` header to determine the available methods. |
| 406  | Your `Accept` header is weird, this format is not supported by the server. Try `application/json` or `text/xml`. Do not attempt to parse the response body, it's not the type you asked for. |
| 500  | Server's dun goofed. If you can parse the response's `Content-Type`, there might be an explanatory message present in the response body. |
| 503  | Server is under maintenance or over capacity. Try again later. |

The webserver itself may send other response codes, which should be handled appropriately by your API client.

## Pagination

All requests that return a list are paginated. You can use the `per_page` query parameter to change the number of items
returned per page. It has to be in the range 1 - 1000, and is set to 10 by default.

The `page` key indicates the page number you currently are on, `total` represents the total number of items that
are available across all pages.

The `items` key contains an array of the objects of that resource.

The `_links` key contains links to the previous and next pages, when they are available.
If the entire result only spans one page, and there are no links, the `_links` key may not be present at all.

    GET /admin/users.json?page=2
    
    {
        "page":2,
        "items":[
            {
                "guid":"D863DD99-F6DB-4A2A-ABF9-AF9007D81CAE",
                "username":"a20564",
                "display_name":"abc",
                "_links":{"self":{"href":"\/admin\/users\/D863DD99-F6DB-4A2A-ABF9-AF9007D81CAE"}}
            },
            [...]
        ],
        "total":"29",
        "_links":{
            "prev":{
                "href":"\/admin\/users?page=1"
            },
            "next":{
                "href":"\/admin\/users?page=3"
            }
        }
    }
    
    GET /admin/users.xml?page=2
    
    <result page="2" total="29">
        <entry guid="D863DD99-F6DB-4A2A-ABF9-AF9007D81CAE" username="a20564" display_name="abc">
            <link rel="self" href="/admin/users/D863DD99-F6DB-4A2A-ABF9-AF9007D81CAE"/>
        </entry>
        [...]
        <link rel="prev" href="/admin/users?page=1"/>
        <link rel="next" href="/admin/users?page=3"/>
    </result>
    
## Search

On some list endpoints, the resultset can be filtered before it is returned.

The `q` query string parameter controls the search terms. Each search field can be controlled individually and
are dependent on the type of resource being filtered.

Pagination links are automatically adjusted to paginate this filtered resultset.

    GET /admin/users.json?q[username]=a*
    
    {
        "page":1,
        "items":[
            {
                "guid":"FACCB60B-E852-461C-9DE8-E7BB6CA9BB6B",
                "username":"a15929",
                "display_name":"abc",
                "_links":{"self":{"href":"\/admin\/users\/FACCB60B-E852-461C-9DE8-E7BB6CA9BB6B"}}
            },
                [...]
        ],
        "total":"26",
        "_links":{
            "next":{
                "href":"\/admin\/users?q%5Bname%5D=abc&page=2"
            }
        }
    }

## Form submission errors

When a `POST` or `PUT` request is sent, the data is sent as a submitted form.

When a validation error occurs on the form, a `400 Bad Request` error will be sent back, with the error details in the response body.

An `errors` key describes the validation errors that occurred more in detail. The outermost `errors` object represents the complete
submitted form. `children` contains the child-fields/forms of the form and is an object indexed by child name.

There may be an `errors` key present at each level of fields/forms, but not immediately withing a `children` object.
This `errors` key contains an array of all validation errors that were triggered on that level.

> Note: Due to the technical implementation of some form fields, spurious `children` keys may be present within a field. (Eg: the `passwordEnabled` field in the sample below)

    {
        "code": 400,
        "message": "Validation Failed",
        "errors": {
            "errors": ["This collection should contain 1 element or more."]
            "children": {
                "username": {
                    "errors": ["This value should not be blank."]
                },
                "displayName": [],
                "password": [],
                "passwordEnabled: {
                    "errors": ["This value should not be blank."],
                    "children": [[],[],[]]
                },
                "emailAddresses": {
                    "children": [
                        {
                            "children": {
                                "email": [],
                                "verified": [],
                                "primary": []
                            }
                        }
                    ]
                },
                "enabled": []
            }
        }
    }


## Endpoints

### `GET /admin/users`

Lists all registered users.

This resource is paginated and searchable.

#### Search parameters

| Parameter | Value(s)                        | Description |
| --------- | ------------------------------- | ----------- |
| `is`      | `user`, `admin` or `superadmin` | Shows users based on their role. `user` does not include admins, `admin` includes admins and superadmins, `superadmin` only includes superadmins. |
| `is`      | `enabled`, `disabled`           | Shows enabled or disabled users only. |
| `username`| Anything                        | Shows users based on their full username. Wildcards (`*`) are allowed everywhere. |
| `name`    | Anything                        | Shows users based on their full name (`display_name`). Wildcards are allowed everywhere. |
| `email`   | Anything                        | Shows user based on one of their email addresses (both verified and unverified) |

#### Resource object

The user object returned by a listing query is limited to the following fields:

 * `guid`: The globally-unique identifier of a user. Is guaranteed unique within one installation,
 and should be unique across different installations. This value does not change after user creation.
 * `username`: The username of the user. Is guaranteed to be unique within one installation,
 but may be changed after user creation and may be reassigned to another user.
 * `display_name`: The real name of the user, which should be used to address the user. May not be unique
 within one installation and may be changed after user creation.
 * `_links.self.href`: Contains an URL that uniquely identifies the user and contains more information about it.
 

    {
        "guid":"FACCB60B-E852-461C-9DE8-E7BB6CA9BB6B",
        "username":"a15929",
        "display_name":"John Doe",
        "_links":{
            "self":{
                "href":"\/admin\/users\/FACCB60B-E852-461C-9DE8-E7BB6CA9BB6B"
            }
        }
    }

### `POST /admin/users`

Creates a new user.

| Field                                  | Required | Description |
| -------------------------------------- | -------- | ----------- |
| `app_user[username]`                   | Yes      | The username for the new user. Must be unique to this installation. |
| `app_user[displayName]`                | Yes      | The real name of the user. Will be used to address the user. |
| `app_user[password]`                   | No       | The password to set for the user. Not required, because the user can reset their password afterwards. |
| `app_user[passwordEnabled]`            | Yes      | Values: 0 (password authentication disabled), 1 (password authentication enabled) or 2 (Allow user to set an initial password). |
| `app_user[emailAddresses][0][email]`   | Yes      | The first email address for the user. Multiple addresses can be added by changing the index `[0]` to a higher number. |
| `app_user[emailAddresses][0][verified]`| No       | If present, the corresponding email address is marked as already verified. |
| `app_user[emailAddresses][0][primary]` | No       | If present, the corresponding email address is marked as the primary address for the user. |
| `app_user[enabled]`                    | No       | If present, the user is enabled immediately. |
| `app_user[role]`                       | No       | Only available if the api key has scope `Profile::write::admin`. Values: `ROLE_USER` (default), `ROLE_AUDIT`, `ROLE_ADMIN`, `ROLE_SUPER_ADMIN`. |

### `GET /admin/users/{guid}`

A more detailed overview of a user.

| Field          | Required scope         | Description |
| -------------- | ---------------------- | ----------- |
| `email`        | `Profile::read::email` | The primary email address of the user. (may not be verified) |
| `non-locked`   | `Profile::read`        | If the user account is not locked due to lack of verified primary email address. |
| `properties`   | `Profile::read`        | Extra properties that are attached to the user. Empty properties are omitted and if all properties are empty, the key is omitted too. |
| `guid`         | `Profile::read`        | The globally-unique identifier of a user. Is guaranteed unique within one installation, and should be unique across different installations. This value does not change after user creation. |
| `username`     | `Profile::read`        | The username of the user. Is guaranteed to be unique within one installation, but may be changed after user creation and may be reassigned to another user. |
| `display_name` | `Profile::read`        | The real name of the user, which should be used to address the user. May not be unique within one installation and may be changed after user creation. |
| `emails`       | `Profile::read::email` | An array of email addresses belonging to the user, with their verification and primary status. |
| `role`         | `Profile::read`        | The access level of the user in Authserver: one of `ROLE_USER`, `ROLE_AUDIT`, `ROLE_ADMIN` or `ROLE_SUPERADMIN` (subject to change). |
| `enabled`      | `Profile::read`        | If the user account is enabled. |
| `groups`       | `Profile::read`        | An array of groups the user is directly member of, regardless of their exportable attribute. |
| `_links.self.href` | `Profile::read`    | The canonical link to this user. |

    {
        "email":"15057@vbgn.be",
        "non-locked":false,
        "properties":{
            "number":"85"
        },
        "guid":"A0C9A429-D3D0-4070-B59F-6E3DDD40A9AB",
        "username":"a159d29s",
        "display_name":"abc",
        "emails":[
            {
                "addr":"15057@vbgn.be",
                "verified":false,
                "primary":true,
                "_links":{"self":{"href":"\/admin\/users\/A0C9A429-D3D0-4070-B59F-6E3DDD40A9AB\/emails\/58"}}
            }
        ],
        "role":"ROLE_USER",
        "enabled":false,
        "groups":[
            {
                "name":"ioni",
                "display_name":"ionoin",
                "_links":{"self":{"href":"\/admin\/groups\/ioni"}}
            }
        ],
        "_links":{"self":{"href":"\/admin\/users\/A0C9A429-D3D0-4070-B59F-6E3DDD40A9AB"}}
    }

### `PATCH /admin/users/{guid}/*`

| URL                                       | Required scope             | Description       |
| ----------------------------------------- | -------------------------- | ----------------- |
| `/admin/users/{guid}/username`            | `Profile::write::username` | Sets the username of the user to the contents of the request body. |
| `/admin/users/{guid}/displayname`         | `Profile::write`           | Sets the real name of the user to the contents of the request body. |
| `/admin/users/{guid}/role`                | `Profile::write::admin`    | Sets the role of the user to the contents of the request body. (Valid values: `ROLE_USER`, `ROLE_AUDIT`, `ROLE_ADMIN`, `ROLE_SUPER_ADMIN`) |
| `/admin/users/{guid}/password`            | `Profile::write::password` | Sets the password of the user to the contents of the request body. |
| `/admin/users/{guid}/password/enable`     | `Profile::write::password` | Enables password authentication for the user. |
| `/admin/users/{guid}/password/disable`    | `Profile::write::password` | Disables password authentication for the user. |
| `/admin/users/{guid}/password/settable`   | `Profile::write::password` | Allows the user to set an initial password. |
| `/admin/users/{guid}/disable`             | `Profile::write::lock`     | Disables the user |
| `/admin/users/{guid}/enable`              | `Profile::write::lock`     | Enables the user |

Validation errors that occur on these URLs are handled the same way as errors that occur on complete forms.

### `PATCH /admin/users/{guid}/property/{property}`

Sets a property of the user to the contents of the request body.

If the property with that name does not exist, a 404 error is returned.
If the data submitted for the property does not match the validation regex, a 400 error is returned.

### `DELETE /admin/users/{guid}`

Deletes a user.

### `LINK /admin/users/{guid}`

Adds the user to a group.

A `Link` header with the absolute URL to the group must be provided, together with a `rel="group"` relationship.

    $ curl -u-apikey-1:2z0in2[...]oc08 http://192.168.80.2/admin/users/D5182292-79ED-4853-9DFB-0E6AE1AED4E3 \
    -H'Accept: application/json' -XLINK -H'Link: </admin/groups/opswiki_users>;rel="group"'
    
    > LINK /admin/users/D5182292-79ED-4853-9DFB-0E6AE1AED4E3
    > Authorization: Basic LWFwaWtleS[...]A4
    > Accept: application/json
    > Link: <admin/groups/opswiki_users>;rel="group"
    
    < HTTP/1.1 204 No Content
    < Allow: GET, DELETE, PUT, LINK, UNLINK
    < Content-Length: 0
    < Content-Type: text/html

### `UNLINK /admin/users/{guid}`

Removes the user from a group, follows the same semantics as `LINK`, but has the inverse effect.

### `GET /admin/users/{guid}/emails`

Lists all email addresses of a user.

This resource is paginated.

#### Resource object

The email object returned by a listing query contains following fields:

 * `addr`
 * `verified`
 * `primary`
 * `_links.self.href`
 
    {
        "addr": "a15929@vbgn.be",
        "verified": false,
        "primary": false,
        "_links":{"self":{"href":"\/admin\/users\/FACCB60B-E852-461C-9DE8-E7BB6CA9BB6B\/emails\/35"}}
    }

### `POST /admin/users/{guid}/emails`

Adds a new, not yet verified, email address to the user.

The full request body is taken as an email address, eventual errors are returned in the same way as it would be a submitted form.

### `GET /admin/users/{guid}/emails/{email}`

A more detailed overview of an email address.

| Field        | Description |
| ------------ | ----------- |
| `addr`       | The email address. |
| `verified`   | True if the email address has been verified by the user. |
| `primary`    | True if the email address is the primary email address of the user. There is only one primary email address per user. |
| `user`       | The user the email address belongs to. |
| `_links.self.href` | The canonical link to this email address. |

    {
        "addr":"15057@vbgn.be",
        "verified":false,
        "primary":true,
        "user":{
            "guid":"A0C9A429-D3D0-4070-B59F-6E3DDD40A9AB",
            "username":"a159d29s",
            "display_name":"abc",
            "_links":{"self":{"href":"\/admin\/users\/A0C9A429-D3D0-4070-B59F-6E3DDD40A9AB"}}
        },
        "_links":{"self":{"href":"\/admin\/users\/A0C9A429-D3D0-4070-B59F-6E3DDD40A9AB\/emails\/58"}}
    }

### `PATCH /admin/users/{guid}/emails/{email}/verify`

Marks an email address as verified.

This action is irreversible.

### `POST /admin/users/{guid}/emails/{email}/verify`

Sends a verification email to the email address.

### `PATCH /admin/users/{guid}/emails/{email}/primary`

Marks an email address as the primary email address for the user.

The email address that was marked as primary before is no longer marked as primary.

This action can only be executed on a verified email address.

### `DELETE /admin/users/{guid}/emails/{email}`

Removes an email address from a user.

This action cannot be executed on the primary email address of a user.

### `GET /admin/groups`

Lists all groups.

This resource is paginated and searchable.

#### Search parameters

| Parameter | Value(s)                        | Description |
| --------- | ------------------------------- | ----------- |
| `is`      | `exportable`, `noexportable`    | Shows only exportable or non exportable groups. |
| `is`      | `nousers`, `users`              | Shows only groups that do not accept users to be a direct member of them, or only groups that do accept users. |
| `is`      | `nogroups`, `groups`            | Shows only groups that do not accept groups to be a direct member of them, or only groups that do accept groups. |
| `is`      | `userjoin`, `nouserjoin`        | Shows only groups that allow users to join the group by themselves, or only groups that do not accept users to join by themselves. |
| `is`      | `userleave`, `nouserleave`      | Shows only groups that allow users to leave the group by themselves, or only groups that do not accept users to leave by themselves. |
| `techname`| Anything                        | Shows users based on their internal, technical name (`name`). Wildcards (`*`) are allowed everywhere. |
| `name`    | Anything                        | Shows users based on their friendly name (`display_name`). Wildcards are allowed everywhere. |

#### Resource object

The group object returned by a listing query is limited to the following fields:

* `name`: The identifying name of the group. This name can not change during the life of a group and is unique,
but may be reassigned to another group after the group is deleted.
* `display_name`: The user friendly name of the group. May not be unique within an installation, and may be changed after creation.

    {
        "name":"%sysops",
        "display_name":"Sysops",
        "_links":{"self":{"href":"\/admin\/groups\/%25sysops"}}
    }
    
### `POST /admin/groups`

Creates a new group.

| Field                                  | Required | Description |
| -------------------------------------- | -------- | ----------- |
| `app_group[name]`                      | Yes      | The identifying name of the group. Must be unique to this installation. |
| `app_group[displayName]`               | Yes      | The friendly name of the group. Will be displayed to users. |
| `app_group[exportable]`                | No       | If present, the group is marked as exportable. |
| `app_group[userJoinable]`              | No       | If present, the group is marked as user joinable. |
| `app_group[userLeaveable]`             | No       | If present, the group is marked as user leaveable. |
| `app_group[noGroups]`                  | No       | If present, no other groups can be a member of this group. |
| `app_group[noUsers]`                   | No       | If present, no users can be a member of this group. |

### `GET /admin/groups/{name}`

A more detailed overview of a group.

| Field                 | Description |
| --------------------- | ----------- |
| `name`                | The identifying name of the group. This name can not change during the life of a group and is unique, but may be reassigned to another group after the group is deleted. |
| `display_name`        | The user friendly name of the group. May not be unique within an installation, and may be changed after creation. |
| `members`             | Users that are a direct or indirect member of one of the groups in this array are also an indirect member of this group. |
| `parents`             | Users that are a direct or indirect member of **this** group are also an indirect member of the groups in this array. |
| `_links.self.href`    | The canonical link to this group. |
| `_links.members.href` | Link to the URL where a paginated list of all direct members of this group is available. |

    {
        "name":"kd_admins",
        "display_name":"KD admins",
        "members":[
            {
                "name":"%sysops",
                "display_name":"Sysops",
                "members":[],
                "parents":[],
                "_links":{
                    "self":{"href":"\/admin\/groups\/%25sysops"},
                    "members":{"href":"\/admin\/groups\/%25sysops\/members"}
                }
            }
        ],
        "parents":[
            {
                "name":"kd_sellers",
                "display_name":"KD Sellers",
                "members":[],
                "parents":[],
                "_links":{
                    "self":{"href":"\/admin\/groups\/kd_sellers"},
                    "members":{"href":"\/admin\/groups\/kd_sellers\/members"}
                }
            }
        ],
        "_links":{
            "self":{"href":"\/admin\/groups\/kd_admins"},
            "members":{"href":"\/admin\/groups\/kd_admins\/members"}
        }
    }
    
> **Note:** The nested `members` and `parents` fields will always be empty, and should
> be ignored. Their presence is due to the limitations of the serialization framework. (See d52fa09)

### `GET /admin/groups/{name}/members`

Lists all users that are a direct member of a group.

The presence of a query parameter named `all` also shows all indirect members of this group.

This resource is paginated.

#### Resource object

The resource object is described at `GET /admin/users`

    {
        "guid":"5FC0F82D-1E70-45E7-B620-781456E6CE10",
        "username":"admin",
        "display_name":"admin",
        "_links":{"self":{"href":"\/admin\/users\/5FC0F82D-1E70-45E7-B620-781456E6CE10"}}
    }

### `PATCH /admin/groups/{name}/displayname`

Changes the display name of the group.

The full request body is taken as the display name. Validation errors are handled in the same way as with forms.

### `PATCH /admin/groups/{name}/flags`

Change the behavioral flags of the group.

| Field           | Description |
| --------------- | ----------- |
| `exportable`    | Marks the group as exported. (visible as a group to OAuth applications) |
| `userJoinable`  | Marks the group as joinable by a user himself. (through the profile and via the OAuth API) |
| `userLeaveable` | Marks the group as leaveable by a user himself. (through the profile and via the OAuth API) |
| `noUsers`       | Marks the group as not able to have direct user members. |
| `noGroups`      | Marks the group as not containing any groups as member. |

The flags can be toggled on and off by setting them to `1` and `0` respectively.

### `DELETE /admin/groups/{name}`

Deletes a group.

### `LINK /admin/groups/{name}`

Adds 'parents' to the group. Users that are member of **this** group will also be an indirect member of the parent groups.

A `Link` header with the absolute URL to the group must be provided, together with a `rel="group"` relationship.

### `UNLINK /admin/groups/{name}`

Removes a parent from a group, follows the same semantics as `LINK`, but has the inverse effect.

## Example: creating a new user and deleting it again

    $ curl -u-apikey-1:2z0in2[...]oc08 http://192.168.80.2/admin/users -H'Accept: application/json' \
    -dapp_user[username]=abc -dapp_user[displayName]=Abc -dapp_user[password]=abc -dapp_user[passwordEnabled]=1 \
    -dapp_user[enabled] -dapp_user[emailAddresses][0][email]=abc@vbgn.be -v
    
    > POST /admin/users HTTP/1.1
    > Authorization: Basic LWFwaWtleS[...]A4
    > Accept: application/json
    > Content-Length: 164
    > Content-Type: application/x-www-form-urlencoded
    
    < HTTP/1.1 201 Created
    < Cache-Control: no-cache
    < Location: http://192.168.80.2/admin/users/D5182292-79ED-4853-9DFB-0E6AE1AED4E3
    < Allow: GET, POST
    < Content-Length: 0
    < Content-Type: application/json
    
    
    $ curl -u-apikey-1:2z0in2[...]oc08 -XDELETE http://192.168.80.2/admin/users/D5182292-79ED-4853-9DFB-0E6AE1AED4E3 \
    -H'Accept: application/json' -v
    
    > DELETE /admin/users/D5182292-79ED-4853-9DFB-0E6AE1AED4E3 HTTP/1.1
    > Authorization: Basic LWFwaWtleS[...]A4
    > Accept: application/json
    
    < HTTP/1.1 204 No Content
    < Allow: GET, DELETE, PUT, LINK, UNLINK
    < Content-Length: 0
    < Content-Type: application/json
    
    
    
    
    



