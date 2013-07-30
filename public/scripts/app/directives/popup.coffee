angular.module('ponyfm').directive 'pfmPopup', () ->
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


		documentClickHandler = () ->
			return if !open
			$popup.removeClass 'open'
			open = false

		calculatePosition = ->
			$popup.parents().each () ->
				$this = $ this
				$positionParent = $this if $positionParent == null && ($this.css('position') == 'relative' || $this.is 'body')

			position = $element.offset()
			parentPosition = $positionParent.offset()

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
				left: left - parentPosition.left - 5
				top: top - parentPosition.top,
				height: height - 15}

		windowResizeHandler = () ->
			return if !open
			$popup.css 'height', 'auto'
			position = calculatePosition()
			$popup.css
				left: position.left
				top: position.top
				height: position.height

		$(document.body).bind 'click', documentClickHandler
		$(window).bind 'resize', windowResizeHandler

		$(element).click (e) ->
			e.preventDefault()
			e.stopPropagation()

			if open
				open = false
				$popup.removeClass 'open'
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

		scope.$on '$destroy', () ->
			$(document.body).unbind 'click', documentClickHandler
			$(window).unbind 'click', windowResizeHandler
