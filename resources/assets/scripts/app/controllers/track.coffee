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

window.pfm.preloaders['track'] = [
    'tracks', '$state', 'playlists'
    (tracks, $state, playlists) ->
        $.when.all [tracks.fetch $state.params.id, playlists.refreshOwned(true)]
]

angular.module('ponyfm').controller "track", [
    '$scope', 'tracks', '$state', 'playlists', 'auth', 'favourites', '$dialog', 'download-cached', '$window', '$timeout'
    ($scope, tracks, $state, playlists, auth, favourites, $dialog, cachedTrack, $window, $timeout) ->
        track = null

        tracks.fetch($state.params.id).done (trackResponse) ->
            $scope.track = trackResponse.track
            track = trackResponse.track

        $scope.playlists = []

        if auth.data.isLogged
            playlists.refreshOwned().done (lists) ->
                $scope.playlists.push list for list in lists

        $scope.favouriteWorking = false

        $scope.toggleFavourite = (track) ->
            $scope.favouriteWorking = true
            favourites.toggle('track', track.id).done (res) ->
                track.is_favourited = res.is_favourited
                $scope.favouriteWorking = false

        $scope.share = () ->
            dialog = $dialog.dialog
                templateUrl: '/templates/partials/track-share-dialog.html',
                controller: ['$scope', ($scope) -> $scope.track = track; $scope.close = () -> dialog.close()]
            dialog.open()

        $scope.addToNewPlaylist = () ->
            dialog = $dialog.dialog
                templateUrl: '/templates/partials/playlist-dialog.html'
                controller: 'playlist-form'
                resolve: {
                    playlist: () ->
                        is_public: true
                        is_pinned: true
                        name: ''
                        description: ''
                }

            dialog.open().then (playlist) ->
                return if !playlist

                playlists.addTrackToPlaylist playlist.id, $scope.track.id
                $state.transitionTo 'playlist', {id: playlist.id}

        $scope.addToPlaylist = (playlist) ->
            return if playlist.message

            playlists.addTrackToPlaylist(playlist.id, $scope.track.id).done (res) ->
                playlist.message = res.message

        $scope.getCachedTrack = (id, format) ->
          $scope.isEncoding = true

          cachedTrack.download('tracks', id, format).then (response) ->
            trackUrl = response
            $scope.trackUrl = trackUrl
            console.log(trackUrl);
            if trackUrl == 'error'
              $scope.isEncoding = false
            else if trackUrl == 'pending'
              $timeout $scope.getCachedTrack(id, format), 5000
            else
              $scope.isEncoding = false
              $window.open trackUrl, '_blank'
]
