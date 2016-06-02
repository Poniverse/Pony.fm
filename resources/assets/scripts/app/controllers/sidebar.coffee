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
    '$scope', '$modal', 'playlists', '$rootScope', '$timeout'
    ($scope, $modal, playlists, $rootScope, $timeout) ->
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
                $scope.navStyle.transform = ''
                $scope.menuAnimated = true
                $scope.menuVisible = false
                $scope.menuActive = false
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
            $modal
                templateUrl: '/templates/partials/credits-dialog.html'
                controller: 'credits',
                show: true

        # Swipable side nav code
        startX = 0
        currentX = 0
        touchingNav = false

        onStart = (e) ->

            if !$scope.menuVisible
                return

            startX = e.touches[0].pageX
            currentX = startX
            touchingNav = true
            $scope.menuAnimated = false
            requestAnimationFrame(update)

        onMove = (e) ->
            if !touchingNav
                return

            currentX = e.touches[0].pageX
            translateX = Math.min(0, currentX - startX)

            if translateX < 0
                e.preventDefault()

        onEnd = (e) ->
            if !touchingNav
                return

            touchingNav = false
            translateX = Math.min(0, currentX - startX)
            $scope.menuAnimated = true

            if translateX < 0
                hideNav()

        hideNav = () ->
            $scope.navStyle.transform = ''
            $scope.menuAnimated = true
            $scope.menuVisible = false
            $scope.menuActive = false
            $scope.$apply()

        update = () ->
            if !touchingNav
                return

            requestAnimationFrame(update)

            translateX = 170 + Math.min(0, currentX - startX)
            $scope.navStyle.transform = 'translateX(' + translateX + 'px)'
            $scope.$apply()

        addEventListeners = () ->
            document.addEventListener('touchstart', onStart)
            document.addEventListener('touchmove', onMove)
            document.addEventListener('touchend', onEnd)

        addEventListeners()
]
