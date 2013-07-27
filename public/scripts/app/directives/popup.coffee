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

			position = $element.offset()
			$popup.addClass 'open'
			$popup.css
				top: position.top + $element.height() + 10
				left: position.left

			open = true

		scope.$on '$destroy', () ->
			$(document.body).unbind 'click', documentClickHandler
			$popup.remove()