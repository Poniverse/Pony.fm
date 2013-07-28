angular.module('ponyfm').directive 'pfmPopup', () ->
	(scope, element, attrs) ->
		$popup = $ '#' + attrs.pfmPopup
		$element = $ element
		$popup.remove()
		open = false

		documentClickHandler = () ->
			return if !open
			$popup.removeClass 'open'
			open = false

		$(document.body).bind 'click', documentClickHandler

		$(document.body).append $popup

		$(element).click (e) ->
			e.preventDefault()
			e.stopPropagation()

			if open
				open = false
				$popup.removeClass 'open'
				return

			$popup.addClass 'open'

			position = $element.offset()
			left = position.left
			right = left + $popup.width()
			windowWidth = $(window).width() - 15
			if right > windowWidth
				left -= right - windowWidth

			$popup.css
				top: position.top + $element.height() + 10
				left: left

			open = true

		scope.$on '$destroy', () ->
			$(document.body).unbind 'click', documentClickHandler
			$popup.remove()