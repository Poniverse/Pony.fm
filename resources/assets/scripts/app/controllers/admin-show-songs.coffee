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

module.exports = angular.module('ponyfm').controller 'admin-show-songs', [
    '$scope', '$state', 'admin-show-songs'
    ($scope, $state, showsongs) ->

        $scope.showsongs = []

        $scope.isCreating = false
        $scope.songToCreate = ''
        $scope.hasCreationError = false
        $scope.createSongError = ''

        # Used for merging/deleting show songs
        $scope.mergeInProgress = false
        $scope.songToDelete = null

        setSongs = (showsongs) ->
            $scope.showsongs = []
            for song in showsongs
                song.isSaving = false
                song.isError = false
                $scope.showsongs.push(song)

        loadSongs = () ->
            showsongs.fetch().done setSongs

        loadSongs()


        $scope.createSong = (songName) ->
            $scope.isCreating = true
            showsongs.create(songName)
            .done (response) ->
                $scope.hasCreationError = false
                $scope.songToCreate = ''
                loadSongs()
            .fail (response) ->
                $scope.hasCreationError = true
                $scope.createSongError = response
                console.log(response)
            .always (response) ->
                $scope.isCreating = false


        # Renames the given song
        $scope.renameSong = (song) ->
            song.isSaving = true
            showsongs.rename(song.id, song.title)
            .done (response)->
                song.isError = false
            .fail (response)->
                song.errorMessage = response
                song.isError = true
            .always (response)->
                song.isSaving = false


        $scope.startMerge = (destinationSong) ->
            $scope.destinationSong = destinationSong
            $scope.mergeInProgress = true

        $scope.cancelMerge = () ->
            $scope.destinationSong = null
            $scope.mergeInProgress = false

        $scope.finishMerge = (songToDelete) ->
            showsongs.merge(songToDelete.id, $scope.destinationSong.id)
            .done (response) ->
                loadSongs()
]
