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


# Some notes on what's going on here:
#
# - Webpack resolves all of these require statements.
#
# - jQuery is loaded before Angular so it replaces jqLite.
#
# - "script!" is used with dependencies that expect to interact with the global state.
#
# - The "ponyfm" module in this file must be initialized before the controllers
#   and other Angular modules are brought in; they expect the "ponyfm" module to exist.

require 'script!../base/jquery-2.0.2'
require 'script!../base/jquery-ui'
angular = require 'angular'

require 'script!../base/angular-ui-date'
require 'angular-ui-router'
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
require 'angular-strap'
# Just ignore this, blame webpack
require '../../../../node_modules/angular-strap/dist/angular-strap.tpl'
require 'chart.js';
require 'angular-chart.js';

require '../shared/pfm-angular-marked'
require '../shared/pfm-angular-sanitize'
require '../shared/init.coffee'


ponyfm = angular.module 'ponyfm', ['mgcrea.ngStrap', 'ui.router', 'ui.date', 'ui.sortable', 'angularytics', 'ngSanitize', 'hc.marked', 'chart.js']
window.pfm.preloaders = {}

# Inspired by: https://stackoverflow.com/a/30652110/3225811
requireDirectory = (r) ->
    r.keys().forEach(r)

requireDirectory(require.context('./controllers/', false, /\.coffee$/));
requireDirectory(require.context('./directives/', false, /\.coffee$/));
requireDirectory(require.context('./filters/', false, /\.coffee$/));
requireDirectory(require.context('./services/', false, /\.coffee$/));


if window.pfm.environment == 'production'
    ponyfm.run [
        'Angularytics',
        (analytics) ->
            analytics.init()
    ]

ponyfm.run [
    '$rootScope', 'meta',
    ($rootScope, meta) ->
        $rootScope.$on '$stateChangeStart', (event, toState, toParams, fromState, fromParams) ->
            meta.reset()
]

ponyfm.config [
    '$locationProvider', '$stateProvider', 'AngularyticsProvider', '$httpProvider', '$sceDelegateProvider', 'markedProvider', '$urlMatcherFactoryProvider'
    (location, state, analytics, $httpProvider, $sceDelegateProvider, markedProvider, $urlMatcherFactoryProvider) ->

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

        $urlMatcherFactoryProvider.strictMode(false)

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

        state.state 'content.track.stats',
            url: '/stats'
            templateUrl: '/templates/tracks/stats.html'
            controller: 'track-stats'


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
            url: '^/playlists?filter&page'
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

        state.state 'pages.hwc-terms',
            url: '/hwc2016-rules'
            templateUrl: '/templates/pages/hwc-terms.html'

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

        state.state 'admin.showsongs',
            url: '/show-songs'
            controller: 'admin-show-songs'
            templateUrl: '/templates/admin/show-songs.html'

        state.state 'admin.tracks',
            url: '/tracks?filter&page'
            controller: 'admin-tracks'
            templateUrl: '/templates/admin/tracks.html'

        state.state 'admin.classifier',
            url: '/tracks/unclassified?filter&page'
            controller: 'admin-classifier'
            templateUrl: '/templates/admin/classifier.html'

        state.state 'admin.users',
            url: '/users'
            controller: 'admin-users'
            templateUrl: '/templates/admin/users.html'

        state.state 'admin.announcements',
            url: '/announcements'
            controller: 'admin-announcements'
            templateUrl: '/templates/admin/announcements.html'

        state.state 'admin.announcement',
            url: '/announcements/{id:[^\-]+}-{slug}'
            templateUrl: '/templates/admin/announcement-show.html'
            controller: 'admin-announcement-edit'

        state.state 'notifications-email-unsubscribed',
            url: '/notifications/email/unsubscribed',
            controller: 'notifications-email-unsubscribed',
            templateUrl: '/templates/home/email-unsubscribed.html'

        # Homepage
        if window.pfm.auth.isLogged
            state.state 'home',
                url: '/'
                templateUrl: '/templates/dashboard/index.html'
                controller: 'dashboard'

            state.state 'notifications',
                url: '/notifications'
                templateUrl: '/templates/notifications/index.html'
                controller: 'notifications'
        else
            state.state 'home',
                url: '/'
                templateUrl: '/templates/home/index.html'
                controller: 'home'

        # Final catch-all for artists
        state.state 'content.artist',
            url: '^/{slug}'
            templateUrl: '/templates/artists/_show_layout.html'
            abstract: true
            controller: 'artist'

        state.state 'content.artist.profile',
            url: '^/{slug}'
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


        # Account

        state.state 'content.artist.account',
            url: '/account'
            abstract: true
            templateUrl: '/templates/account/_layout.html'

        # Upload

        state.state 'content.artist.account.uploader',
            url: '/uploader'
            templateUrl: '/templates/uploader/index.html'
            controller: 'uploader'

        state.state 'content.artist.account.settings',
            url: ''
            templateUrl: '/templates/account/settings.html'
            controller: 'account-settings'

        state.state 'content.artist.account.tracks',
            url: '/tracks'
            templateUrl: '/templates/account/tracks.html'
            controller: 'account-tracks'

        state.state 'content.artist.account.tracks.edit',
            url: '/edit/:track_id'
            templateUrl: '/templates/account/track.html'
            controller: 'account-track'

        state.state 'content.artist.account.albums',
            url: '/albums'
            templateUrl: '/templates/account/albums.html'
            controller: 'account-albums'

        state.state 'content.artist.account.albums.create',
            url: '/create'
            templateUrl: '/templates/account/album.html'
            controller: 'account-albums-edit'

        state.state 'content.artist.account.albums.edit',
            url: '/edit/:album_id'
            templateUrl: '/templates/account/album.html'
            controller: 'account-albums-edit'

        state.state 'content.artist.account.playlists',
            url: '/playlists'
            templateUrl: '/templates/account/playlists.html'
            controller: 'account-playlists'


        location.html5Mode(true);

]

module.exports = ponyfm
