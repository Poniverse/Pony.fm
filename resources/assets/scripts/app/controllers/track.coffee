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

module.exports = angular.module('ponyfm').controller "track", [
    '$scope', 'meta', 'tracks', '$state', 'playlists', 'auth', 'favourites', '$modal', 'download-cached', '$window', '$timeout', '$mdDialog'
    ($scope, meta, tracks, $state, playlists, auth, favourites, $modal, cachedTrack, $window, $timeout, $mdDialog) ->
        $scope.track
        $scope.trackId = parseInt($state.params.id)

        updateTrackData = (forceUpdate = false) ->
            tracks.fetch($scope.trackId, forceUpdate).done (trackResponse) ->
                $scope.track = trackResponse.track
                meta.setTitle("#{$scope.track.title} | #{$scope.track.user.name}")
                meta.setDescription("Listen to \"#{$scope.track.title}\" by #{$scope.track.user.name} on the largest pony music site.")

        updateTrackData()

        $scope.$on 'track-updated', () ->
            updateTrackData(true)

        $scope.$on 'track-deleted', () ->
            # This is meant to take you back to whatever state you found
            # this track from.
            $window.history.go(-2)

        $scope.playlists = []

        if auth.data.isLogged
            playlists.refreshOwned().done (playlists) ->
                for playlist in playlists
                    if $scope.trackId not in playlist.track_ids
                        $scope.playlists.push playlist

        $scope.favouriteWorking = false

        $scope.toggleFavourite = (track) ->
            $scope.favouriteWorking = true
            favourites.toggle('track', track.id).done (res) ->
                track.is_favourited = res.is_favourited
                $scope.favouriteWorking = false

        $scope.share = (ev) ->
            $mdDialog.show(
                templateUrl: '/templates/partials/track-share-dialog.html',
                scope: $scope,
                clickOutsideToClose: true,
                controller: ($scope, $mdDialog) ->
                    $scope.closeDialog = ->
                        $mdDialog.cancel()
            ).then (() ->
                return
            ), ->
                console.log $state.current
                $scope.$apply()

        $scope.addToNewPlaylist = () ->
            dialog = $modal
                templateUrl: '/templates/partials/playlist-dialog.html'
                controller: 'playlist-form',
                scope: $scope,
                resolve: {
                    playlist: () ->
                        is_public: true
                        is_pinned: true
                        name: ''
                        description: ''
                },
                show: true

        $scope.finishAddingToPlaylist = (playlist, track) ->
            return if !playlist

            playlists.addTrackToPlaylist playlist.id, track.id
            $state.transitionTo 'content.playlist', {id: playlist.id}

        $scope.addToPlaylist = (playlist) ->
            return if playlist.message

            playlists.addTrackToPlaylist(playlist.id, $scope.track.id).done (res) ->
                playlist.message = res.message

        $scope.getCachedTrack = (id, format) ->
            $scope.isInProgress = true

            $mdDialog.show(
                $mdDialog.alert()
                    .clickOutsideToClose(true)
                    .title('Preparing download...')
                    .textContent('We\'re getting your download ready! This\'ll take a few seconds.')
                    .ok('Sure thing')
            )

            cachedTrack.download('tracks', id, format).then (response) ->
                $scope.trackUrl = response
                if $scope.trackUrl == 'error'
                    $scope.isInProgress = false
                else if $scope.trackUrl == 'pending'
                    # $timeout takes a callback function
                    # https://stackoverflow.com/a/23391203/3225811
                    $timeout(
                        ()-> $scope.getCachedTrack(id, format)
                    , 5000)
                else
                    $scope.isInProgress = false
                    $window.location = $scope.trackUrl
]
