angular.module('ponyfm').directive 'pfmPlayer', () ->
    $element = null

    restrict: 'E'
    templateUrl: '/templates/directives/player.html'
    scope: {}
    replace: true

    compile: (element) ->
        $element = element

    controller: [
        '$scope', 'player', 'auth'
        ($scope, player, auth) ->
            $scope.player = player
            $scope.auth = auth.data
            $scope.playPause = () ->
                $scope.player.playPause()

            $scope.playNext = () ->
                $scope.player.playNext()

            $scope.playPrev = () ->
                $scope.player.playPrev()

            $scope.seek = (e) ->
                $transport = $ '.transport'
                percent = ((e.pageX - $transport.offset().left) / $transport.width())
                duration = parseFloat($scope.player.currentTrack.duration)

                $scope.player.seek percent * duration * 1000

            isSliding = false
            $slider = $element.find('.volume-slider')
            $knob = $element.find('.volume-slider .knob')
            $bar = $element.find('.volume-slider .bar')

            player.readyDef.done ->
                initialY = (180 - (180 * (player.volume / 100))) - 7.5
                $knob.css {top: initialY}

            moveVolumeSlider = (absoluteY) ->
                newY = absoluteY - $bar.offset().top;
                maxY = $bar.height() - ($knob.height() / 2) - 8

                newY = 0 if newY < 0
                newY = maxY if newY > maxY

                percent = 100 - ((newY / maxY) * 100)
                $scope.player.setVolume percent
                $knob.css {top: newY}

            $knob.click (e) ->
                e.preventDefault()
                e.stopPropagation()

            $slider.click (e) -> $scope.$apply -> moveVolumeSlider(e.pageY - 8)

            $(document).mousemove (e) ->
                return if !isSliding
                moveVolumeSlider(e.pageY - 8)

            $knob.mousedown (e) ->
                e.preventDefault()
                e.stopPropagation()
                isSliding = true
                $slider.parent().addClass('keep-open')

            $(document).mouseup (e) ->
                e.preventDefault()
                e.stopPropagation()
                isSliding = false
                $slider.parent().removeClass('keep-open')
    ]
