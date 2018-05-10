<?php
/**
 * Authserver, an OAuth2-based single-signon authentication provider written in PHP.
 *
 * Copyright (C) $today.date  Lars Vierbergen
 *
 * his program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace App;


final class AppEvents
{

    /**
     * Emits {@link App\Event\MenuEvent}
     */
    const MAIN_MENU = 'app.menu.main';

    /**
     * Emits {@link App\Event\MenuEvent}
     */
    const PROFILE_MENU = 'app.menu.profile';

    const LOGIN_VIEW_BODY = 'app.view.login.body';

    const LOGIN_VIEW_FOOTER = 'app.view.login.footer';

    const GENERATE_HTACCESS = 'app.generate.htaccess';

    const GENERATE_MAINTENANCE = 'app.generate.maintenance';

    /**
     * Emits {@link App\Event\UserCheckerEvent}
     */
    const SECURITY_USER_CHECK_PRE = 'app.security.user_check.pre';

    /**
     * Emits {@link App\Event\UserCheckerEvent}
     */
    const SECURITY_USER_CHECK_POST = 'app.security.user_check.post';

    private function __construct()
    {}
}
