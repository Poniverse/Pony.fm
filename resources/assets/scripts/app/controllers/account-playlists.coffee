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

window.pfm.preloaders['account-playlists'] = [
    'playlists'
    (playlists) -> playlists.refreshOwned true
]

angular.module('ponyfm').controller "account-playlists", [
    '$scope', 'auth', '$dialog', 'playlists'
    ($scope, auth, $dialog, playlists) ->
        $scope.playlists = []

        loadPlaylists = (playlists) ->
            $scope.playlists.push playlist for playlist in playlists

        playlists.refreshOwned().done loadPlaylists

        $scope.editPlaylist = (playlist) ->
            dialog = $dialog.dialog
                templateUrl: '/templates/partials/playlist-dialog.html'
                controller: 'playlist-form'
                resolve: {
                    playlist: () -> angular.copy playlist
                }

            dialog.open()

        $scope.togglePlaylistPin = (playlist) ->
            playlist.is_pinned = !playlist.is_pinned;
            playlists.editPlaylist playlist

        $scope.deletePlaylist = (playlist) ->
            $dialog.messageBox('Delete ' + playlist.title, 'Are you sure you want to delete "' + playlist.title + '"? This cannot be undone.', [
                {result: 'ok', label: 'Yes', cssClass: 'btn-danger'},
                {result: 'cancel', label: 'No', cssClass: 'btn-primary'}
            ]).open().then (res) ->
                return if res == 'cancel'
                playlists.deletePlaylist(playlist).done ->
                    $scope.playlists.splice _.indexOf($scope.playlists, (p) -> p.id == playlist.id), 1

        $scope.$on 'playlist-updated', (e, playlist) ->
            index = _.indexOf($scope.playlists, (p) -> p.id == playlist.id)
            content = $scope.playlists[index]
            _.each playlist, (value, name) -> content[name] = value
            $scope.playlists.sort (left, right) -> left.title.localeCompare right.title
]
