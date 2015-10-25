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

@extends('shared._layout')

@section('content')

    <div id="fb-root"></div>

    <script>
        window.fbAsyncInit = function() {
            FB.init({
                appId      : '186765381447538',
                status: true,
                cookie: true,
                xfbml: true
            });
        };

        (function(d, s, id){
            var js, fjs = d.getElementsByTagName(s)[0];
            if (d.getElementById(id)) {return;}
            js = d.createElement(s); js.id = id;
            js.src = "//connect.facebook.net/en_US/all.js";
            fjs.parentNode.insertBefore(js, fjs);
        }(document, 'script', 'facebook-jssdk'));
    </script>

    <header>
        <a href="/">
            <img src="/images/fm_logo_white.svg" class="logo">
        </a>
        <div class="now-playing">
            @if (Auth::check())
                <div class="user-details dropdown">
                    <a class="avatar dropdown-toggle" href="#">
                        <img src="{{Auth::user()->getAvatarUrl(\Poniverse\Ponyfm\Image::THUMBNAIL)}}" />
                        <span><i class="icon-chevron-down"></i></span>
                    </a>
                    <ul class="dropdown-menu">
                        <li><a href="{{Auth::user()->url}}">Your Profile</a></li>
                        <li><a href="#" pfm-eat-click ng-click="logout()">Logout</a></li>
                    </ul>
                </div>
            @endif
            <pfm-player></pfm-player>
        </div>
    </header>

    <div class="site-body">
        <ul class="sidebar" ng-controller="sidebar">
            @if (Auth::check())
                <li ng-class="{selected: stateIncludes('home')}"><a href="/">Dashboard</a></li>
            @else
                <li ng-class="{selected: stateIncludes('home')}"><a href="/">Home</a></li>
            @endif
            <li ng-class="{selected: (stateIncludes('content') && !isPinnedPlaylistSelected)}">
                <a href="/tracks">Discover</a>
            </li>

            @if (Auth::check())
                <li ng-class="{selected: stateIncludes('favourites')}"><a href="/account/favourites/tracks">Favourites</a></li>
                <li ng-class="{selected: stateIncludes('account')}"><a href="/account/tracks">Account</a></li>
            @endif

            <li ng-class="{selected: isActive('/about')}"><a href="/about">About</a></li>

            @if (Auth::check())
                <li class="uploader" ng-class="{selected: stateIncludes('uploader')}">
                    <a href="/account/uploader">Upload Music</a>
                </li>
                <li>
                    <h3>
                        <a href="#" ng-click="createPlaylist()" pfm-eat-click title="Create Playlist"><i class="icon-plus"></i></a>
                        Playlists
                    </h3>
                </li>
                <li class="none" ng-show="!playlists.length"><span>no pinned playlists</span></li>
                <li class="dropdown" ng-repeat="playlist in playlists" ng-cloak ng-class="{selected: stateIncludes('content.playlist') && $state.params.id == playlist.id}">
                    <a href="{{Helpers::angular('playlist.url')}}" ng-bind="playlist.title"></a>
                </li>
            @else
                <li><a href="/login" target="_self">Login</a></li>
                <li><a href="/register" target="_self">Register</a></li>
            @endif
            <li class="x-attribution">
                <a ng-click="showCredits()" href="#" title="Pony.fm project credits">
                    @if(config('ponyfm.use_powered_by_footer'))
                        <span>Powered by</span>
                        <img src="/images/fm_logo_white.svg" alt="Pony.fm logo" title="Pony.fm"/>
                        <span>We&#39;re open-source!</span>
                    @else
                        <span>A community by</span>
                        <img src="/images/poniverse.svg" alt="Poniverse logo" title="Poniverse"/>
                        <span>Now 20% more <span class="x-caps">FOSS</span>!</span>
                    @endif
                </a>
            </li>
        </ul>
        <ui-view class="site-content">
            @yield('app_content')
        </ui-view>
    </div>

@endsection

@section('styles')
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Josefin+Sans" />
    <link rel="stylesheet" href="/styles/loader.css" />
    {!! Assets::styleIncludes() !!}
@endsection

@section('scripts')
    <script>
        window.pfm = {
            token: "{!! csrf_token() !!}",
            auth: {
                @if (Auth::check())
                    isLogged: true,
                    user: {!! Auth::user()->toJson() !!}
                @else
                    isLogged: false
                @endif
            },
            environment: "{{ App::environment() }}"
        };
    </script>

    @if(config('ponyfm.google_analytics_id'))
        <script>
            {{-- Google Analytics --}}
            var _gaq = _gaq || [];
            _gaq.push(['_setAccount', '{{ config('ponyfm.google_analytics_id') }}']);
            _gaq.push(['_setDomainName', 'pony.fm']);

            (function() {
                var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
                ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
                var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
            })();
        </script>
    @endif

    {!! Assets::scriptIncludes() !!}

    @if (!Config::get("app.debug"))
        <script src="/build/scripts/templates.js"></script>
    @endif

    @yield('app_scripts')

@endsection
