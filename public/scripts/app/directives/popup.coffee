angular.module('ponyfm').directive 'pfmPopup', () ->
	(scope, element, attrs) ->
		$popup = $ '#' + attrs.pfmPopup
		$element = $ element
		$positionParent = null
		open = false

		$popup.parents().each () ->
			$this = $ this
			$positionParent = $this if $positionParent == null && ($this.css('position') == 'relative' || $this.is 'body')

		documentClickHandler = () ->
			return if !open
			$popup.removeClass 'open'
			open = false

		calculatePosition = ->
			position = $element.offset()
			parentPosition = $positionParent.offset()

			left = position.left
			right = left + $popup.width()
			windowWidth = $(window).width() - 15
			if right > windowWidth
				left -= right - windowWidth

			height = 'auto'
			top = position.top + $element.height() + 10
			bottom = top + $popup.height()
			windowHeight = $(window).height()
			if bottom > windowHeight
				height = windowHeight - top;

			return {
				left: left - parentPosition.left - 2
				top: top - parentPosition.top,
				height: height}

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
			position = calculatePosition()
			$popup.css
				left: position.left
				top: position.top
				height: position.height

			open = true

		scope.$on '$destroy', () ->
			$(document.body).unbind 'click', documentClickHandler
			$(window).unbind 'click', windowResizeHandler
