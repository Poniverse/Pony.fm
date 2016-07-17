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
    '$scope', 'artists', '$state', 'follow', '$rootScope'
    ($scope, artists, $state, follow, $rootScope) ->
        updateArtist = (force = false) ->
            artists.fetch($state.params.slug, force)
                .done (artistResponse) ->
                    $scope.artist = artistResponse.artist
                    $scope.headerStyle = {'background-image': createGradient(artistResponse.artist.avatar_colors[0], artistResponse.artist.avatar_colors[1])}

                    tempImg = document.createElement('img')
                    tempImg.setAttribute 'src', artistResponse.artist.avatars.small + '?' + new Date().getTime()
                    tempImg.setAttribute 'crossOrigin', ''
                    tempImg.crossOrigin = 'Anonymous'

                    tempImg.addEventListener 'load', ->
                        colorThief = new ColorThief();
                        palette = colorThief.getPalette(tempImg, 2)

                        $('.top-bar').css('background': selectHeaderColour(palette[0], palette[1]))
                        $scope.$apply()

        rgbArrayToCss = (array) ->
            'rgb(' + array[0] + ',' + array[1] + ',' + array[2] + ')'

        hslArrayToCss = (array) ->
            'hsl(' + array[0] + ',' + array[1] + '%,' + array[2] + '%)'

        hexToRgb = (hex) ->
            h = '0123456789ABCDEF'
            r = h.indexOf(hex[1]) * 16 + h.indexOf(hex[2])
            g = h.indexOf(hex[3]) * 16 + h.indexOf(hex[4])
            b = h.indexOf(hex[5]) * 16 + h.indexOf(hex[6])
            return [r,g,b]

        dimColor = (colour) ->
            hsl = rgbToHsl(hexToRgb(colour))

            if hsl[2] >= 50
                hsl[2] = 50
                if hsl[1] <= 20
                    hsl[1] = 30
                return hslToRgb(hsl)
            else
                return hexToRgb(colour)

        createGradient = (vib, dark) ->
            if $(window).width() <= 480
                'linear-gradient(180deg, ' + vib + ' 5%, ' + rgbArrayToCss(dimColor(dark)) + ' 95%)'
            else
                'linear-gradient(170deg, ' + vib + ' 15%, ' + dark + ' 110%)'

        findHighestSaturation = (hsl1, hsl2) ->
            if hsl1[1] > hsl2[1]
                return hsl1
            else
                return hsl2

        findBestColour = (hsl1, hsl2) ->
            maxLumin = 60
            mid = 40
            best = undefined

            if Math.abs(hsl1[2] - mid) < Math.abs(hsl2[2] - mid)
                best = hsl1
            else
                best = findHighestSaturation(hsl1, hsl2)

            if best[2] > maxLumin
                best[2] = maxLumin

            return best


        selectHeaderColour = (colour1, colour2) ->
            hsl1 = rgbToHsl(colour1)
            hsl2 = rgbToHsl(colour2)
            out = rgbArrayToCss(hslToRgb(findBestColour(hsl1, hsl2)))
            return out

        rgbToHsl = (rgbArr) ->
            r1 = rgbArr[0] / 255
            g1 = rgbArr[1] / 255
            b1 = rgbArr[2] / 255
            maxColor = Math.max(r1, g1, b1)
            minColor = Math.min(r1, g1, b1)
            L = (maxColor + minColor) / 2
            S = 0
            H = 0
            if maxColor != minColor
                if L < 0.5
                    S = (maxColor - minColor) / (maxColor + minColor)
                else
                    S = (maxColor - minColor) / (2.0 - maxColor - minColor)
                if r1 == maxColor
                    H = (g1 - b1) / (maxColor - minColor)
                else if g1 == maxColor
                    H = 2.0 + (b1 - r1) / (maxColor - minColor)
                else
                    H = 4.0 + (r1 - g1) / (maxColor - minColor)
            L = L * 100
            S = S * 100
            H = H * 60
            if H < 0
                H += 360

            round = (value, decimals) ->
                Number Math.round(value + 'e' + decimals) + 'e-' + decimals

            [round(H, 6), round(S, 6), round(L, 6)]

        toPercent = (amount, limit) ->
            amount / limit

        hueToRgb = (p, q, t) ->
            if t < 0
                t += 1
            if t > 1
                t -= 1
            if t < 1 / 6
                return p + (q - p) * 6 * t
            if t < 1 / 2
                return q
            if t < 2 / 3
                return p + (q - p) * (2 / 3 - t) * 6

            p

        hslToRgb = (bits) ->
            rgb = []
            v = undefined
            q = undefined
            p = undefined
            hsl =
                h: toPercent(parseInt(bits[0], 10) % 360, 360)
                s: toPercent(parseInt(bits[1], 10) % 101, 100)
                l: toPercent(parseInt(bits[2], 10) % 101, 100)
            if hsl.s == 0
                v = parseInt(Math.round(255 * hsl.l))
                rgb[0] = v
                rgb[1] = v
                rgb[2] = v
            else
                q = if hsl.l < 0.5 then hsl.l * (1 + hsl.s) else hsl.l + hsl.s - (hsl.l * hsl.s)
                p = 2 * hsl.l - q
                rgb[0] = parseInt((hueToRgb(p, q, hsl.h + 1 / 3) * 256).toFixed(0), 10)
                rgb[1] = parseInt((hueToRgb(p, q, hsl.h) * 256).toFixed(0), 10)
                rgb[2] = parseInt((hueToRgb(p, q, hsl.h - (1 / 3)) * 256).toFixed(0), 10)

            rgb

        $scope.toggleFollow = () ->
            follow.toggle('artist', $scope.artist.id).then (res) ->
                $scope.artist.user_data.is_following = res.is_followed

        updateArtist()

        $scope.$on 'user-updated', ->
            updateArtist(true)
]
