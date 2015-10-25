angular.module('ponyfm').directive 'pfmImageUpload', () ->
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

                    if file.type != 'image/png'
                        $scope.error = 'Image must be a png!'
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
