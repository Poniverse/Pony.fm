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

module.exports = angular.module('ponyfm').directive 'pfmImageUpload', () ->
    $image = null
    $uploader = null

    restrict: 'E'
    templateUrl: '/templates/directives/image-upload.html'
    scope:
        setImage: '=setImage'
        image: '=image'

    compile: (element) ->
        $image = element.find 'img'
        $uploader = element.find 'input'

    controller: [
        'images', '$scope', 'lightbox'
        (images, $scope, lightbox) ->
            $scope.imageObject = null
            $scope.imageFile = null
            $scope.imageUrl = null
            $scope.isImageLoaded = false
            $scope.error = null

            $scope.$watch 'image', (val) ->
                $scope.imageObject = $scope.imageFile = $scope.imageUrl = null
                $scope.isImageLoaded = false
                return if !val

                $scope.imageUrl = val
                $image.attr 'src', val
                $scope.isImageLoaded = true

            $image.load () -> $scope.$apply ->
                $scope.isImageLoaded = true
                window.setTimeout (() -> window.alignVertically($image)), 0

            images.refresh().done (images) -> $scope.images = images

            $scope.previewImage = () ->
                return if !$scope.isImageLoaded

                if $scope.imageObject
                    lightbox.openImageUrl $scope.imageObject.urls.normal
                else if $scope.imageFile
                    lightbox.openDataUrl $image.attr 'src'
                else if $scope.imageUrl
                    lightbox.openImageUrl $scope.imageUrl

            $scope.uploadImage = () ->
                $uploader.trigger 'click'
                return

            $scope.clearImage = () ->
                $scope.imageObject = $scope.imageFile = $scope.imageUrl = null
                $scope.isImageLoaded = false
                $scope.setImage null

            $scope.selectGalleryImage = (image) ->
                $scope.imageObject = image
                $scope.imageFile = null
                $scope.imageUrl = image.urls.small
                $image.attr 'src', image.urls.small
                $scope.isImageLoaded = true
                $scope.setImage image, 'gallery'

            $scope.setImageFile = (input) ->
                $scope.$apply ->
                    file = input.files[0]
                    $scope.imageObject = null
                    $scope.imageFile = file

                    if file.type not in ['image/png', 'image/jpeg']
                        $scope.error = 'Image must be a PNG or JPEG!'
                        $scope.isImageLoaded = false
                        $scope.imageObject = $scope.imageFile = $scope.imageUrl = null
                        return

                    $scope.error = null
                    $scope.setImage file, 'file'

                    reader = new FileReader()
                    reader.onload = (e) -> $scope.$apply ->
                        $image[0].src = e.target.result
                        $scope.isImageLoaded = true

                    reader.readAsDataURL file
        ]
