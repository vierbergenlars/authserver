<?php
/*
 * Authserver, an OAuth2-based single-signon authentication provider written in PHP.
 *
 * Copyright (C) 2018 Lars Vierbergen
 *
 * This program is free software: you can redistribute it and/or modify
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */
namespace Admin;

class AdminEvents
{

    const BATCH_ACTIONS = 'app.admin.batch_actions';

    const FILTER_LIST = 'app.admin.filter_list';

    const DISPLAY_LIST = 'app.admin.display_list';

    const SIDEBAR = 'app.admin.sidebar';

    const DISPLAY = 'app.admin.display';

    private function __construct()
    {}
}