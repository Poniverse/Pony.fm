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

module.exports = angular.module('ponyfm').directive 'pfmSrcLoader', () ->
    (scope, element, attrs) ->
        size = attrs.pfmSrcSize || 'normal'
        element.css {opacity: .5}

        update = (val) ->
            element.attr 'src', '/images/icons/loading_' + size + '.png'

            image = element.clone()
            image.removeAttr 'pfm-src-loader'
            image.removeAttr 'pfm-src-size'

            # If the given value is null, don't bother trying to
            # load something - it will result in an HTTP error.
            if val
                image[0].onload = ->
                    element.attr 'src', val
                    element.css {opacity: 0}
                    element.animate {opacity: 1}, 250

                image[0].src = val

        update scope.$eval attrs.pfmSrcLoader

        scope.$watch attrs.pfmSrcLoader, update
