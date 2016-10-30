{{--
    Pony.fm - A community for pony fan music.
    Copyright (C) 2015 Peter Deltchev

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU Affero General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU Affero General Public License for more details.

    You should have received a copy of the GNU Affero General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.
--}}<!DOCTYPE html>
<html ng-app="ponyfm">
    <head>
        <meta charset="utf-8" />
        <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
        <title ng-bind="title">@yield('title')Pony.fm</title>
        <meta name="description" content="@yield('description')" />
        <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1" />
        <meta name="theme-color" content="#84528A" />
        <link rel="manifest" href="/manifest.json">
        <base href="/" />
        @yield('metadata')
        @yield('styles')
    </head>
    <body ng-controller="application" class="{{Auth::check() ? 'is-logged' : ''}}">
        @yield('content')
        @yield('scripts')
    </body>
</html>
