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
                appId: '186765381447538',
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
        <div class="top-bar">
            <div class="burger-wrapper" ng-click="menuToggle()">
                <i class="material-icons">menu</i>
            </div>
            <a href="/" class="logo"><img class="default-logo" src="/images/ponyfm-logo-white.svg"><img class="small-logo" src="/images/ponyfm-logo-white-nodisc.svg"></a>
            <pfm-search></pfm-search>
            @if (Auth::check())
                <div class="user-details">
                    <md-menu md-position-mode="target-right target">
                        <a class="avatar dropdown-toggle" ng-click="$mdOpenMenu($event)" href="#">
                            <img src="{{Auth::user()->getAvatarUrl(\Poniverse\Ponyfm\Models\Image::THUMBNAIL)}}" />
                        </a>
                        <md-menu-content width="3">
                            <md-menu-item><md-button ui-sref="content.artist.profile({slug: auth.user.slug})">Your Profile</md-button></md-menu-item>
                            <md-menu-item><md-button ui-sref="content.artist.favourites({slug: auth.user.slug})">Favourites</md-button></md-menu-item>
                            <md-menu-item><md-button ui-sref="content.artist.account.settings({slug: auth.user.slug})">Account</md-button></md-menu-item>
                            <md-menu-item><md-button href="#" pfm-eat-click ng-click="logout()">Logout</md-button></md-menu-item>
                        </ul>
                    </md-menu>
                </div>
                <div class="notification-menu">
                    <a href="#" ng-click="notifPulloutToggle()"><i class="material-icons">notifications</i></a>
                    <div class="counter" ng-class="{'show': nCount > 0}">@{{ nCountFormatted }}</div>
                </div>
            @endif
            <a href="#" ng-click="toggleSearchBar()" class="search-button"><i class="material-icons">search</i></a>
            <md-progress-linear md-mode="indeterminate" class="loader" ng-disabled="!loading"></md-progress-linear>
        </div>
        <div class="now-playing" ng-class="{'playing': isPlaying}">
            <pfm-player></pfm-player>
        </div>
    </header>

    <div class="site-body">
        <ul class="sidebar" ng-controller="sidebar" ng-class="{'active': menuActive, 'animated': menuAnimated}" ng-style="navStyle">
            <a href="/">
              <img src="/images/ponyfm-logo-white.svg" class="logo">
            </a>
            <li ng-class="{selected: stateIncludes('content.tracks') || stateIncludes('content.track')}"><a ui-sref="content.tracks.list">Tracks</a></li>
            <li ng-class="{selected: stateIncludes('content.albums') || stateIncludes('content.album')}"><a ui-sref="content.albums.list">Albums</a></li>
            <li ng-class="{selected: stateIncludes('content.playlists') || stateIncludes('content.playlist')}"><a ui-sref="content.playlists.list">Playlists</a></li>
            <li ng-class="{selected: stateIncludes('content.artists') || stateIncludes('content.artist')}"><a ui-sref="content.artists.list">Artists</a></li>


            <li ng-class="{selected: stateIncludes('pages')}"><a href="/about">About / FAQ</a></li>
            <li><a href="https://mlpforums.com/forum/62-ponyfm/" title="Pony.fm Forum" target="_blank">Forum</a></li>

            @if (Auth::check())
                <li class="uploader" ui-sref-active="selected">
                    <a ui-sref="content.artist.account.uploader({slug: auth.user.slug})">Upload Music</a>
                </li>

                @can('access-admin-area')
                    <li ui-sref-active="selected">
                        <a ui-sref="admin.users">Admin Area</a>
                    </li>
                @endcan

                <li>
                    <h3>
                        <a href="#" ng-click="createPlaylist()" pfm-eat-click title="Create Playlist"><i class="material-icons">add</i></a></a>
                        My Playlists
                    </h3>
                </li>
                <li class="none" ng-show="!playlists.length"><span>no pinned playlists</span></li>
                <li class="dropdown" ng-repeat="playlist in playlists track by playlist.id" ng-cloak ng-class="{selected: stateIncludes('content.playlist') && $state.params.id == playlist.id}">
                    <a href="{{Helpers::angular('playlist.url')}}" ng-bind="playlist.title"></a>
                </li>
            @else
                <li><a href="/login" target="_self">Login</a></li>
                <li><a href="/register" target="_self">Register</a></li>
            @endif
            <li class="x-attribution">
                <a href="#" ng-click="showCredits()" pfm-eat-click title="Pony.fm project credits">
                    @if(config('ponyfm.use_powered_by_footer'))
                        <span>Powered by</span>
                        <img src="/images/ponyfm-logo-white.svg" alt="Pony.fm logo" title="Pony.fm"/>
                        <span>We&#39;re open-source!</span>
                    @else
                        <span>A community by</span>
                        <img src="/images/poniverse.svg" alt="Poniverse logo" title="Poniverse"/>
                        <span>We&#39;re open-source!</span>
                    @endif
                </a>
            </li>
        </ul>

        <ui-view class="site-content">
            @yield('app_content')
        </ui-view>

        @if (Auth::check())
            <div class="notification-pullout" ng-class="{'active': notifActive}">
                <div class="notif-container">
                    <pfm-notification-list></pfm-notification-list>
                </div>
            </div>
        @endif
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

    {!! Assets::scriptIncludes('app') !!}

    @yield('app_scripts')

@endsection
