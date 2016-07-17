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

window.pfm.preloaders['artist'] = [
    'artists', '$state'
    (artists, $state) ->
        artists.fetch $state.params.slug, true
]

module.exports = angular.module('ponyfm').controller "artist", [
    '$scope', 'artists', '$state', 'follow', '$rootScope', 'color'
    ($scope, artists, $state, follow, $rootScope, color) ->
        updateArtist = (force = false) ->
            if force
                window.location.reload()
                
            artists.fetch($state.params.slug, force)
                .done (artistResponse) ->
                    $scope.artist = artistResponse.artist
                    $scope.headerStyle = {'background-image': color.createGradient(artistResponse.artist.avatar_colors[0], artistResponse.artist.avatar_colors[1])}

                    tempImg = document.createElement('img')
                    tempImg.setAttribute 'src', artistResponse.artist.avatars.small + '?' + new Date().getTime()
                    tempImg.setAttribute 'crossOrigin', ''
                    tempImg.crossOrigin = 'Anonymous'

                    tempImg.addEventListener 'load', ->
                        colorThief = new ColorThief();
                        palette = colorThief.getPalette(tempImg, 2)

                        $('.top-bar').css('background': color.selectHeaderColour(palette[0], palette[1]))
                        $scope.$apply()

        $scope.toggleFollow = () ->
            follow.toggle('artist', $scope.artist.id).then (res) ->
                $scope.artist.user_data.is_following = res.is_followed

        updateArtist()

        $scope.$on 'user-updated', ->
            updateArtist(true)
]
