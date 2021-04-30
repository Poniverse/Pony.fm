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

module.exports = angular.module('ponyfm').directive 'uploader', ()->
    $dropzone = null

    compile: (element)->
        $dropzone = element

    scope:
        userSlug: '=uploader'

    controller: [
        '$scope', 'upload'
        ($scope, upload) ->
            $dropzone[0].addEventListener 'dragover', (e) ->
                e.preventDefault()
                $dropzone.addClass 'file-over'

            $dropzone[0].addEventListener 'dragleave', (e) ->
                e.preventDefault()
                $dropzone.removeClass 'file-over'

            $dropzone[0].addEventListener 'drop', (e) ->
                e.preventDefault()
                $dropzone.removeClass 'file-over'

                files = e.target.files || e.dataTransfer.files
                $scope.$apply -> upload.upload(files, $scope.userSlug)
    ]
