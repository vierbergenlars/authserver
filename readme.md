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

### Automated installation

You can quickly deploy this application with [clic](https://github.com/vierbergenlars/clic),
which provides an interactive installation and asks you for each configuration parameter.

 * To install the master branch: `clic application:clone https://github.com/vierbergenlars/authserver`
 * To install a specific release (copy the download link from [the releases page](https://github.com/vierbergenlars/authserver/releases)): `clic application:extract https://github.com/vierbergenlars/authserver/archive/v0.8.1.zip authserver`

Application updates will follow the choice you made here and either pull the latest master branch or stick to released versions.

### Manual installation

#### Download

Releases can be downloaded from [the releases page](https://github.com/vierbergenlars/authserver/releases),
or by checking out a tag from this repository.

#### Configuration

Create an `app/config/parameters.yml` from the `app/config/parameters.yml.dist` template and fill in the applicable
configuration parameters.

You can allow users to register themselves by enabling the [registration module](https://github.com/vierbergenlars/authserver/blob/master/documentation/registration.md)

#### Dependencies

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
php app/console app:generate --env=prod
```

#### Creating the first user

Without any users, authserver is not really useful, so let's add our first user.
(Who will be a super-admin to manage the application.)

```bash
php app/console app:adduser --super-admin $username $password $email --env=prod
```

If you ever happen to forget your password,
you can set a new one with `php app/console app:passwd $username $new_password --env=prod`.

#### Publishing the application

Only the `web/` directory should be publicly accessible, all requests that do not match a file in the `web/` directory
should be rewritten to `web/app.php` by the webserver. How to accomplish this depends on your webserver,
but a `.htaccess` file that accomplishes this is present in the `web/` folder.

### Plugins

Plugins that extend Authserver and add functionality can be installed with the `./plugin.sh` command.

More information is available in [the plugins documentation](https://github.com/vierbergenlars/authserver/blob/master/documentation/plugins.md)

## Upgrading

Upgrading can be done easily by overwriting the old files with a fresh copy.
(If you have cloned the repository, just `git fetch` and check out the newer tag.)

Next, execute the same commands as when installing the dependencies.

### Deployment tool

There are two different automated deployment tools available: [clic](#clic) and [./deploy.sh](#deploy-sh).

#### clic

Update to the latest version with `clic application:execute authserver update`.

If you are running from the git repository, this will automatically pull down the latest `master` version from the repository
(it should be stable) and start a deployment.

If you are running from an extracted archive, this will automatically download and extract the archive for the latest tag
(this is definitely stable) and start a deployment.

#### deploy.sh

An automated deployment tool is available as `./deploy.sh`.

It automatically puts the application in maintenance mode,
pulls the latest version of the current branch and rebases local commits on top of it.

> **WARNING:** When an automatic pull and rebase fails, you will be dropped to a shell to fix the situation.
> If you have local changes, you can run the following commands to try to fix the situation.
> ```bash
> git stash
> git pull --rebase
> git stash pop
> exit
> ```
> Because this will result in untested changes, it is recommended to keep your own repository with the necessary
> changes and merge releases to this repository instead of deploying directly from the master repository.

It then handles the updating dependencies, assets and database migrations.
When all these steps have completed successfully, the maintenance mode is disabled, and the new version of the application
is deployed.

##### Advanced usage

The deployment tool has a number of flags to change its default behavior:

* `-n, --no-pull`: Does not pull a new version from the repository
* `--skip-maintenance`: Skips manipulations of the maintenance mode
* `-e <env>, --env=<env>`: Changes the symfony environment to run all commands in
* `-s, --shell`: Starts a shell instead of running any update commands
* `-l, --lock-maintenance`: Puts the application in maintenance mode. This maintenance mode will only be disabled by running the matching `--unlock-maintenance` command.
* `-u, --unlock-maintenance`: Disables the maintenance mode

