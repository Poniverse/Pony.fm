# Pony.fm - A community for pony fan music.
# Copyright (C) 2015 Feld0
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

module.exports = angular.module('ponyfm').controller "account-albums-edit", [
    '$scope', '$state', '$modal', 'account-albums', 'auth'
    ($scope, $state, $modal, albums, auth) ->
        $scope.isNew = $state.params.album_id == undefined
        $scope.data.isEditorOpen = true
        $scope.errors = {}
        $scope.isDirty = false
        $scope.album = {}
        $scope.isSaving = false
        $scope.tracks = []
        $scope.trackIds = {}
        $scope.isAdmin = auth.data.isAdmin

        $scope.toggleTrack = (track) ->
            if $scope.trackIds[track.id]
                delete $scope.trackIds[track.id]
                $scope.tracks.splice ($scope.tracks.indexOf track), 1
            else
                $scope.trackIds[track.id] = track
                $scope.tracks.push track

            $scope.isDirty = true

        $scope.sortTracks = () ->
            $scope.isDirty = true

        $scope.touchModel = -> $scope.isDirty = true

        $scope.setCover = (image, type) ->
            delete $scope.album.cover_id
            delete $scope.album.cover

            if image == null
                $scope.album.remove_cover = true
            else if type == 'file'
                $scope.album.cover = image
            else if type == 'gallery'
                $scope.album.cover_id = image.id

            $scope.isDirty = true

        $scope.$on '$destroy', -> $scope.data.isEditorOpen = false

        $scope.saveAlbum = ->
            return if !$scope.isNew && !$scope.isDirty

            url =
                if $scope.isNew
                    '/api/web/albums/create'
                else
                    '/api/web/albums/edit/' + $state.params.album_id

            xhr = new XMLHttpRequest()
            xhr.onload = -> $scope.$apply ->
                $scope.isSaving = false
                response = $.parseJSON(xhr.responseText)
                if xhr.status != 200
                    $scope.errors = {}
                    _.each response.errors, (value, key) -> $scope.errors[key] = value.join ', '
                    return

                $scope.$emit 'album-updated'

                if $scope.isNew
                    $scope.isDirty = false
                    $scope.$emit 'album-created'
                    $state.go '^.edit', {album_id: response.id}
                else
                    $scope.isDirty = false
                    $scope.data.selectedAlbum.title = $scope.album.title
                    $scope.data.selectedAlbum.covers.normal = response.real_cover_url

            formData = new FormData()

            _.each $scope.album, (value, name) ->
                if name == 'cover'
                    return if value == null
                    if typeof(value) == 'object'
                        formData.append name, value, value.name
                else
                    formData.append name, value

            formData.append 'track_ids', _.map($scope.tracks, (t) -> t.id).join()
            formData.append 'user_id', $scope.artist.id

            xhr.open 'POST', url, true
            xhr.setRequestHeader 'X-XSRF-TOKEN', $.cookie('XSRF-TOKEN')
            $scope.isSaving = true
            xhr.send formData

        $scope.deleteAlbum = () ->
            modal = $modal({scope: $scope, templateUrl: 'templates/partials/delete-album-dialog.html', show: true});

        $scope.confirmDeleteAlbum = () ->
          $.post('/api/web/albums/delete/' + $scope.album.id)
              .then -> $scope.$apply ->
                  $scope.$emit 'album-deleted'
                  $state.go '^'

        $scope.setCover = (image, type) ->
            delete $scope.album.cover_id
            delete $scope.album.cover

            if image == null
                $scope.album.remove_cover = true
            else if type == 'file'
                $scope.album.cover = image
            else if type == 'gallery'
                $scope.album.cover_id = image.id

            $scope.isDirty = true

        if !$scope.isNew
            albums.getEdit($state.params.album_id).done (album) ->
                $scope.album =
                    id: album.id
                    user_id: album.user_id
                    username: album.username
                    title: album.title
                    description: album.description
                    remove_cover: false
                    cover: album.cover_url

                $scope.tracks = []
                $scope.tracks.push track for track in album.tracks
                $scope.trackIds[track.id] = track for track in album.tracks

        else
            $scope.album =
                title: ''
                description: ''

        $scope.$on '$locationChangeStart', (e) ->
            return if !$scope.isDirty
            e.preventDefault() if !confirm('Are you sure you want to leave this page without saving your changes?')
]
