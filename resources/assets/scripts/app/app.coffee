# Pony.fm - A community for pony fan music.
# Copyright (C) 2015 Peter Deltchev
#
# This program is free software: you can redistribute it and/or modify
# it under the terms of the GNU Affero General Public License as published by
# the Free Software Foundation, either version 3 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU Affero General Public License for more details.
#
# You should have received a copy of the GNU Affero General Public License
# along with this program.  If not, see <http://www.gnu.org/licenses/>.


require 'script!../base/jquery-2.0.2'
require 'script!../base/jquery-ui'
angular = require 'angular'

require 'script!../base/angular-ui-date'
require '../base/angular-ui-router'
require '../base/angular-ui-sortable'
require '../base/angularytics'
require '../base/jquery.colorbox'
require '../base/jquery.cookie'
require '../base/jquery.timeago'
require '../base/jquery.viewport'
require 'script!../base/marked'
require 'script!../base/moment'
require '../base/soundmanager2-nodebug'
require 'script!../base/tumblr'
require '../base/ui-bootstrap-tpls-0.4.0'
require 'script!../base/underscore'

require '../shared/init.coffee'
require '../shared/jquery-extensions'
require '../shared/layout.coffee'
require '../shared/pfm-angular-marked'
require '../shared/pfm-angular-sanitize'
require 'script!../shared/underscore-extensions'


ponyfm = angular.module 'ponyfm', ['ui.bootstrap', 'ui.state', 'ui.date', 'ui.sortable', 'angularytics', 'ngSanitize', 'hc.marked']
window.pfm.preloaders = {}

##require './controllers/'+name+'.coffee'
#require "./controllers/account-albums-edit"
#require "./controllers/account-albums"
#require "./controllers/account-image-select"
#require "./controllers/account-playlists"
#require "./controllers/account-settings"
#require "./controllers/account-track"
#require "./controllers/account-tracks"
#require "./controllers/admin-genres"
#require "./controllers/album"
#require "./controllers/albums-list"
#require "./controllers/albums"
#require "./controllers/application"
#require "./controllers/artist-content"
#require "./controllers/artist-favourites"
#require "./controllers/artist-profile"
#require "./controllers/artist"
#require "./controllers/artists-list"
#require "./controllers/artists"
#require "./controllers/credits"
#require "./controllers/dashboard"
#require "./controllers/favourites-albums"
#require "./controllers/favourites-playlists"
#require "./controllers/favourites-tracks"
#require "./controllers/home"
#require "./controllers/login"
#require "./controllers/playlist-form"
#require "./controllers/playlist"
#require "./controllers/playlists-list"
#require "./controllers/playlists"
#require "./controllers/sidebar"
#require "./controllers/track-edit"
#require "./controllers/track-show"
#require "./controllers/track"
#require "./controllers/tracks-list"
#require "./controllers/tracks"
#require "./controllers/uploader"


requireAll = (r) ->
    r.keys().forEach(r)

requireAll(require.context('./controllers/', false, /\.coffee$/));
requireAll(require.context('./directives/', false, /\.coffee$/));
requireAll(require.context('./filters/', false, /\.coffee$/));
requireAll(require.context('./services/', false, /\.coffee$/));

#require './directives/'+name
#require './filters/'+name
#require './services/'+name



if window.pfm.environment == 'production'
    ponyfm.run [
        'Angularytics',
        (analytics) ->
            analytics.init()
    ]

ponyfm.run [
    '$rootScope',
    ($rootScope) ->
        $rootScope.$on '$stateChangeStart', (event, toState, toParams, fromState, fromParams) ->
            $rootScope.description = ''
]

ponyfm.config [
    '$locationProvider', '$stateProvider', '$dialogProvider', 'AngularyticsProvider', '$httpProvider', '$sceDelegateProvider', 'markedProvider'
    (location, state, $dialogProvider, analytics, $httpProvider, $sceDelegateProvider, markedProvider) ->

        if window.pfm.environment == 'local'
            $httpProvider.interceptors.push [
                ->
                    request: (config) ->
                        return config if !(/^\/?templates\//.test config.url)
                        config.url += '?' + Math.ceil(Math.random() * 1000000)
                        return config
            ]

        # This fixes resource loading on IE
        $sceDelegateProvider.resourceUrlWhitelist [
            'self',
            '/templates/directives/*',
            # Used for the "Tweet" button on content item pages
            'https://platform.twitter.com/widgets/tweet_button.html**'
        ]

        if window.pfm.environment == 'production'
            analytics.setEventHandlers ['Google']

        markedProvider.setOptions
            gfm: true
            tables: false
            sanitize: true
            smartLists: true
            smartypants: true
            breaks: true


        markedProvider.setRenderer
            link: (href, title, text) ->
                '<a href="' + href + '" target="_blank" rel="nofollow">' + href + '</a>'
            heading: (text, level) ->
                text
            image: (url) ->
                url
            codespan: (code) ->
                code
            code: (code, language) ->
                code
            del: (text) ->
                text


        # Errors
        state.state 'errors-404',
            url: '/errors/not-found'
            templateUrl: '/templates/errors/404.html'

        state.state 'errors-500',
            url: '/errors/server'
            templateUrl: '/templates/errors/500.html'

        state.state 'errors-403',
            url: '/errors/not-authorized'
            templateUrl: '/templates/errors/403.html'

        state.state 'errors-400',
            url: '/errors/invalid'
            templateUrl: '/templates/errors/400.html'

        # Upload

        state.state 'uploader',
            url: '/account/uploader'
            templateUrl: '/templates/uploader/index.html'
            controller: 'uploader'

        # Account

        state.state 'account',
            url: '/account'
            abstract: true
            templateUrl: '/templates/account/_layout.html'

        state.state 'account.settings',
            url: ''
            templateUrl: '/templates/account/settings.html'
            controller: 'account-settings'

        state.state 'account.tracks',
            url: '/tracks'
            templateUrl: '/templates/account/tracks.html'
            controller: 'account-tracks'

        state.state 'account.tracks.edit',
            url: '/edit/:track_id'
            templateUrl: '/templates/account/track.html'
            controller: 'account-track'

        state.state 'account.albums',
            url: '/albums'
            templateUrl: '/templates/account/albums.html'
            controller: 'account-albums'

        state.state 'account.albums.create',
            url: '/create'
            templateUrl: '/templates/account/album.html'
            controller: 'account-albums-edit'

        state.state 'account.albums.edit',
            url: '/edit/:album_id'
            templateUrl: '/templates/account/album.html'
            controller: 'account-albums-edit'

        state.state 'account.playlists',
            url: '/playlists'
            templateUrl: '/templates/account/playlists.html'
            controller: 'account-playlists'

        state.state 'favourites',
            url: '/account/favourites'
            abstract: true
            templateUrl: '/templates/favourites/_layout.html'

        state.state 'favourites.tracks',
            url: '/tracks'
            templateUrl: '/templates/favourites/tracks.html'
            controller: 'favourites-tracks'

        state.state 'favourites.playlists',
            url: '/playlists'
            templateUrl: '/templates/favourites/playlists.html'
            controller: 'favourites-playlists'

        state.state 'favourites.albums',
            url: '/albums'
            templateUrl: '/templates/favourites/albums.html'
            controller: 'favourites-albums'

        # Tracks

        state.state 'content',
            abstract: true
            template: '<ui-view/>'

        state.state 'content.tracks',
            templateUrl: '/templates/tracks/index.html'
            controller: 'tracks'
            url: '/tracks'
            abstract: true

        state.state 'content.tracks.list',
            url: '^/tracks?filter&page'
            templateUrl: '/templates/tracks/list.html'
            controller: 'tracks-list'

        state.state 'content.track',
            url: '/tracks/{id:[^\-]+}-{slug}'
            templateUrl: '/templates/tracks/frame.html'
            controller: 'track'
            abstract: true

        state.state 'content.track.show',
            url: ''
            templateUrl: '/templates/tracks/show.html'
            controller: 'track-show'

        state.state 'content.track.edit',
            url: '/edit'
            templateUrl: '/templates/tracks/edit.html'
            controller: 'track-edit'


        # Albums

        state.state 'content.albums',
            url: '/albums'
            templateUrl: '/templates/albums/index.html'
            controller: 'albums'
            abstract: true

        state.state 'content.albums.list',
            url: '?page'
            templateUrl: '/templates/albums/list.html'
            controller: 'albums-list'

        state.state 'content.album',
            url: '/albums/{id:[^\-]+}-{slug}'
            templateUrl: '/templates/albums/show.html'
            controller: 'album'

        # Playlists

        state.state 'content.playlists',
            url: '/playlists'
            templateUrl: '/templates/playlists/index.html'
            controller: 'playlists'
            abstract: true

        state.state 'content.playlists.list',
            url: '?page'
            controller: 'playlists-list'
            templateUrl: '/templates/playlists/list.html'

        state.state 'content.playlist',
            url: '/playlist/{id:[^\-]+}-{slug}'
            templateUrl: '/templates/playlists/show.html'
            controller: 'playlist'

        # Artists

        state.state 'content.artists',
            url: '/artists'
            templateUrl: '/templates/artists/index.html'
            controller: 'artists'
            abstract: true

        state.state 'content.artists.list',
            url: '?page'
            templateUrl: '/templates/artists/list.html'
            controller: 'artists-list'

        # Pages

        state.state 'pages',
            templateUrl: '/templates/pages/_layout.html'

        state.state 'pages.about',
            url: '/about'
            templateUrl: '/templates/pages/about.html'

        state.state 'pages.faq',
            url: '/faq'
            templateUrl: '/templates/pages/faq.html'

        state.state 'pages.mlpforums-advertising-program',
            url: '/mlpforums-advertising-program'
            templateUrl: '/templates/pages/mlpforums-advertising-program.html'

        # Auth

        state.state 'login',
            url: '/login'
            templateUrl: '/templates/auth/login.html'
            controller: 'login'

        state.state 'register',
            url: '/register'
            templateUrl: '/templates/auth/register.html'

        # Admin

        state.state 'admin',
            abstract: true
            url: '/admin'
            templateUrl: '/templates/admin/_layout.html'

        state.state 'admin.genres',
            url: '/genres'
            controller: 'admin-genres'
            templateUrl: '/templates/admin/genres.html'

        # Homepage

        if window.pfm.auth.isLogged
            state.state 'home',
                url: '/'
                templateUrl: '/templates/dashboard/index.html'
                controller: 'dashboard'
        else
            state.state 'home',
                url: '/'
                templateUrl: '/templates/home/index.html'
                controller: 'home'

        # Final catch-all for aritsts
        state.state 'content.artist',
            url: '^/{slug}'
            templateUrl: '/templates/artists/_show_layout.html'
            abstract: true
            controller: 'artist'

        state.state 'content.artist.profile',
            url: ''
            templateUrl: '/templates/artists/profile.html'
            controller: 'artist-profile'

        state.state 'content.artist.content',
            url: '/content'
            templateUrl: '/templates/artists/content.html'
            controller: 'artist-content'

        state.state 'content.artist.favourites',
            url: '/favourites'
            templateUrl: '/templates/artists/favourites.html'
            controller: 'artist-favourites'

        location.html5Mode(true);
        $dialogProvider.options
            dialogFade: true
            backdropClick: false

]

module.exports = ponyfm
