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

angular.module('ponyfm').directive 'pfmHrefLoader', () ->
    (scope, element, attrs) ->
        size = attrs.pfmSrcSize || 'normal'

        update = (val) ->
            element.attr 'href', '/images/icons/loading_' + size + '.png'
			
            image = element.clone()
            image.removeAttr 'pfm-href-loader'
            image.removeAttr 'pfm-src-size'

            element.attr 'href', val
            element.css {opacity: 0}
            element.animate {opacity: 1}, 250

            image[0].href = val

        update scope.$eval attrs.pfmHrefLoader

        scope.$watch attrs.pfmHrefLoader, update
