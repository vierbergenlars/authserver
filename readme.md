# Authserver

Authserver is an OAuth2-based single-signon authentication provider written in PHP.

Authserver is the central hub that stores and provides user accounts and user-specific data to other services that
require this data.

Its primary usecase is handling the authentication for all webbased applications within one organisation,
but security controls allow to expose limited access to other organisations.

## License

Authserver is licensed under the terms of the GNU Affero General Public License, either version 3 of the License,
or (at your option) any later version.

See the [LICENSE.md](https://github.com/vierbergenlars/authserver/blob/master/LICENSE.md) file for a full copy of the license.

## Installation

### Download

Releases can be downloaded from [the releases page](https://github.com/vierbergenlars/authserver/releases),
or by checking out a tag from this repository.

### Configuration

Create an `app/config/parameters.yml` from the `app/config/parameters.yml.dist` template and fill in the applicable
configuration parameters.

### Dependencies

PHP dependencies are handled by [`composer`](https://getcomposer.org/),
these can be installed with a single `SYMFONY_ENV=prod composer install --no-dev -o` inside the project root.

To compile the bootstrap stylesheets, `less` is required. Less runs on  [`node.js`](https://nodejs.org/),
so install that one first. Then run `npm install` inside the project root to install `less`.

Then run the following commands to prepare the database and assets.

```bash
php app/console assets:install --env=prod
php app/console assetic:dump --env=prod
php app/console braincrafted:bootstrap:install --env=prod
php app/console doctrine:migrations:migrate --env=prod
```

### Creating the first user

Without any users, authserver is not really useful, so let's add our first user.
(Who will be a super-admin to manage the application.)

```bash
php app/console app:adduser --super-admin $username $password $email --env=prod
```

If you ever happen to forget your password,
you can set a new one with `php app/console app:passwd $username $new_password --env=prod`.

### Publishing the application

Only the `web/` directory should be publicly accessible, all requests that do not match a file in the `web/` directory
should be rewritten to `web/app.php` by the webserver. How to accomplish this depends on your webserver,
but a `.htaccess` file that accomplishes this is present in the `web/` folder.

## Upgrading

Upgrading can be done easily by overwriting the old files with a fresh copy.
(If you have cloned the repository, just `git fetch` and check out the newer tag.)

Next, execute the same commands as when installing the dependencies.





