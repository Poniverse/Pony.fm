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

module.exports = angular.module('ponyfm').controller "sidebar", [
    '$scope', '$modal', 'playlists', '$rootScope', '$timeout', '$mdDialog', '$mdMedia'
    ($scope, $modal, playlists, $rootScope, $timeout, $mdDialog, $mdMedia) ->
        $scope.playlists = playlists.pinnedPlaylists
        $scope.menuVisible = false
        $scope.menuActive = false
        $scope.menuAnimated = true
        $scope.navStyle = {}

        $rootScope.$on('sidebarToggled', () ->
            $timeout(() ->
                if $scope.menuVisible
                    $scope.navStyle.transform = ''
                    $scope.menuAnimated = true

                $scope.menuVisible = !$scope.menuVisible
                $scope.menuActive = $scope.menuVisible
            )
        )

        $rootScope.$on('sidebarHide', () ->
            $timeout(() ->
                hideNav()
            )
        )

        $scope.createPlaylist = () ->
            $modal
                templateUrl: '/templates/partials/playlist-dialog.html'
                controller: 'playlist-form'
                resolve: {
                    playlist: () ->
                        is_public: true
                        is_pinned: true
                        name: ''
                        description: ''
                },
                show: true

        $scope.editPlaylist = (playlist) ->
            $modal
                templateUrl: '/templates/partials/playlist-dialog.html'
                controller: 'playlist-form'
                resolve: {
                    playlist: () -> angular.copy playlist
                },
                show: true

        $scope.unpinPlaylist = (playlist) ->
            playlist.is_pinned = false;
            playlists.editPlaylist playlist

        $scope.deletePlaylist = (playlist) ->
            $scope.playlistToDelete = playlist
            $modal({scope: $scope, templateUrl: 'templates/partials/delete-playlist-dialog.html', show: true})

        $scope.confirmDeletePlaylist = () ->
            playlists.deletePlaylist playlist

        $scope.showCredits = () ->
            useFullScreen = ($mdMedia('sm') || $mdMedia('xs'))  && $scope.customFullscreen
            $mdDialog.show
                templateUrl: '/templates/partials/credits-dialog.html'
                controller: 'credits',
                clickOutsideToClose: true,
                fullscreen: useFullScreen

            hideNav()

        hideNav = () ->
            $scope.navStyle.transform = ''
            $scope.menuAnimated = true
            $scope.menuVisible = false
            $scope.menuActive = false
]
