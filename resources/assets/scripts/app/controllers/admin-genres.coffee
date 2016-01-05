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

angular.module('ponyfm').controller 'admin-genres', [
    '$scope', '$state', 'admin-genres'
    ($scope, $state, genres) ->

        $scope.genres = []

        # Used for merging/deleting genres
        $scope.mergeInProgress = false
        $scope.genreToDelete = null

        setGenres = (genres) ->
            $scope.genres = []
            for genre in genres
                genre.isSaving = false
                genre.isError = false
                $scope.genres.push(genre)

        loadGenres = () ->
            genres.fetch().done setGenres

        loadGenres()


        # Renames the given genre
        $scope.renameGenre = (genre) ->
            genre.isSaving = true
            genres.rename(genre.id, genre.name)
            .done (response)->
                genre.isError = false
            .fail (response)->
                genre.errorMessage = response
                genre.isError = true
            .always (response)->
                genre.isSaving = false


        $scope.startMerge = (destinationGenre) ->
            $scope.destinationGenre = destinationGenre
            $scope.mergeInProgress = true

        $scope.cancelMerge = () ->
            $scope.destinationGenre = null
            $scope.mergeInProgress = false

        $scope.finishMerge = (genreToDelete) ->
            genres.merge(genreToDelete.id, $scope.destinationGenre.id)
                .done (response) ->
                    loadGenres()
]
