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

module.exports = angular.module('ponyfm').directive 'pfmPlayer', () ->
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
            $scope.repeatText = ''
            $scope.playPause = () ->
                $scope.player.playPause()

            $scope.playNext = () ->
                $scope.player.playNext()

            $scope.playPrev = () ->
                $scope.player.playPrev()

            $scope.toggleRepeat = () ->
                $scope.player.toggleRepeat()

                if $scope.player.repeatState == 2
                    $scope.repeatText = '1'
                else
                    $scope.repeatText = ''

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
                initialY = (180 - (180 * (player.volume / 100)))
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
