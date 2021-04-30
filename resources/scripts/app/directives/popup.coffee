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

module.exports = angular.module('ponyfm').directive 'pfmPopup', () ->
    (scope, element, attrs) ->
        align = 'left'
        elementId = attrs.pfmPopup
        if elementId.indexOf ',' != -1
            parts = elementId.split ','
            elementId = parts[0]
            align = parts[1]

        $popup = $ '#' + attrs.pfmPopup
        $element = $ element
        $positionParent = null
        open = false
        closeOnClick = attrs.pfmPopupCloseOnClick?


        close = () ->
            $popup.removeClass 'open'
            open = false


        documentClickHandler = (event) ->
            if !open
                return

            if (closeOnClick)
                close()
                return true

            # Based on: https://stackoverflow.com/a/4660738/3225811
            else if event.target.id == elementId or $(event.target).parents("##{elementId}").size()
                return true

            else
                close()
                return true


        calculatePosition = ->
            $popup.parents().each () ->
                $this = $ this

                if $positionParent == null && ($this.css('position') == 'relative' || $this.is 'body')
                    $positionParent = $this
                    return false

            position = $element.offset()
            parentPosition = $positionParent.offset() + $positionParent.height()

            windowWidth = $(window).width() - 15
            left = position.left
            right = left + $popup.width()

            if align == 'left' && right > windowWidth
                left -= right - windowWidth
            else if align == 'right'
                left -= $popup.outerWidth() - $element.outerWidth()

            height = 'auto'
            top = position.top + $element.height() + 10
            bottom = top + $popup.height()
            windowHeight = $(window).height()
            if bottom > windowHeight
                height = windowHeight - top;

            return {
                left: left - parentPosition.left
                top: top - parentPosition.top,
                height: height - 15}


        windowResizeHandler = () ->
            return if !open
            $popup.css 'height', 'auto'
            position = calculatePosition()
            $popup.css
                left: position.left
                top: position.top
                maxHeight: position.height

        $(document.body).bind 'click', documentClickHandler
        $(window).bind 'resize', windowResizeHandler

        $(element).click (e) ->
            e.preventDefault()
            e.stopPropagation()

            if open and not $element.is(':focus')
                close
                return

            $popup.addClass 'open'

            $popup.css 'height', 'auto'
            window.setTimeout (->
                position = calculatePosition()
                $popup.css
                    left: position.left
                    top: position.top
                    height: position.height

                open = true
            ), 0


        scope.$on '$stateChangeStart', () ->
            close()


        scope.$on '$destroy', () ->
            $(document.body).unbind 'click', documentClickHandler
            $(window).unbind 'click', windowResizeHandler
