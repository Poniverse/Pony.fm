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

window.pfm.preloaders['account-tracks'] = [
    'account-tracks', 'account-albums', 'taxonomies'
    (tracks, albums, taxonomies) ->
        $.when.all [tracks.refresh(null, true), albums.refresh(true), taxonomies.refresh()]
]

module.exports = angular.module('ponyfm').controller "account-tracks", [
    '$scope', '$state', 'taxonomies', '$modal', 'lightbox', 'account-albums', 'account-tracks'
    ($scope, $state, taxonomies, $modal, lightbox, albums, tracks) ->
        $scope.data =
            selectedTrack: null

        $scope.tracks = []
        tracksDb = {}

        setTracks = (tracks) ->
            $scope.tracks.length = 0
            tracksDb = {}
            for track in tracks
                tracksDb[track.id] = track
                $scope.tracks.push track

            if $state.params.track_id
                $scope.data.selectedTrack = tracksDb[$state.params.track_id]

        $scope.selectTrack = (track) ->
            $scope.data.selectedTrack = track

        tracks.refresh('created_at,desc', false, $state.params.slug).done setTracks

        $scope.$on '$stateChangeSuccess', ->
            if $state.params.track_id
                $scope.selectTrack tracksDb[$state.params.track_id]
            else
                $scope.selectTrack null

        $scope.$on 'track-deleted', ->
            $state.transitionTo 'content.artist.account.tracks', slug: $state.params.slug
            tracks.clearCache()
            tracks.refresh(null, true, $state.params.slug).done setTracks

        $scope.$on 'track-updated', ->
            tracks.clearCache()
            tracks.refresh(null, true, $state.params.slug).done setTracks
]
