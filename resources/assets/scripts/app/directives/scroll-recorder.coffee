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

angular.module('ponyfm').directive 'pfmScrollRecorder', () ->
    (scope, element, attrs) ->
        timeout = null
        onScroll = null
        lastInView = null

        element.scroll (e) ->
            (window.clearTimeout timeout) if timeout
            timeout = window.setTimeout (-> onScroll e), 500

        onScroll = (e) -> scope.safeApply ->
            items = element.find 'li:not(.empty)'
            itemHeight = (items.eq 0).height()
            itemsArray = items.get()

            elementViewTop = element.offset().top
            elementViewBottom = elementViewTop + element.height()

            for i in [itemsArray.length - 1..0]
                listItem = $ itemsArray[i]

                listItemTop = listItem.offset().top + itemHeight
                isInView = listItemTop > elementViewTop && listItemTop < elementViewBottom
                if isInView
                    lastInView = listItem
                    break

            scope.$emit 'element-in-view', angular.element(lastInView).scope()
