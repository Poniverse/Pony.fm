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
--}}

@extends('shared._app_layout')

@section('app_content')
    <h1>404 - Not Found</h1>
    <p>We could not find what you were looking for.</p>
@endsection

@section('app_scripts')
    <script>
        window.pfm.error = 404;
    </script>
@endsection
