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
--}}<html>
<head>
    <title>Account disabled :: Pony.fm</title>
    <style>
        body {
            font-family: sans-serif;
            width: 400px;
            margin: 2em auto;
        }

        button {
            font-size: 20px;
            padding: 0.4em;
        }
    </style>
</head>
<body>
<h1>Account disabled</h1>
<p>Your Pony.fm account, {{ $username }}, has been disabled.</p>
<p>If you believe this to be in error,
    contact <a href="mailto:feld0@pony.fm" target="_blank">feld0@pony.fm</a>.</p>
<p><form action="/auth/logout" method="POST">
    <button>Log out</button>
    {{ csrf_field() }}
</form></p>
</body>
</html>
