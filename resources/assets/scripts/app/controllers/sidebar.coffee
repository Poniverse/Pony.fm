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

angular.module('ponyfm').controller "sidebar", [
    '$scope', '$dialog', 'playlists'
    ($scope, $dialog, playlists) ->
        $scope.playlists = playlists.pinnedPlaylists

        $scope.createPlaylist = () ->
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

            dialog.open()

        $scope.editPlaylist = (playlist) ->
            dialog = $dialog.dialog
                templateUrl: '/templates/partials/playlist-dialog.html'
                controller: 'playlist-form'
                resolve: {
                    playlist: () -> angular.copy playlist
                }

            dialog.open()

        $scope.unpinPlaylist = (playlist) ->
            playlist.is_pinned = false;
            playlists.editPlaylist playlist

        $scope.deletePlaylist = (playlist) ->
            $dialog.messageBox('Delete ' + playlist.title, 'Are you sure you want to delete "' + playlist.title + '"?', [
                {result: 'ok', label: 'Yes', cssClass: 'btn-danger'},
                {result: 'cancel', label: 'No', cssClass: 'btn-primary'}
            ]).open().then (res) ->
                return if res == 'cancel'
                playlists.deletePlaylist playlist


        $scope.showCredits = () ->
            dialog = $dialog.dialog
                templateUrl: '/templates/partials/credits-dialog.html'
                controller: 'credits'

            dialog.open()

]
