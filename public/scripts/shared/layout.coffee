window.handleResize = () ->
	windowHeight = $(window).height()
	$siteBody = $ '.site-body'
	$siteBody.height windowHeight - $('header').height() - 1
	redo = false

	$('.stretch-to-bottom').each () ->
		$this = $ this
		newHeight = windowHeight - $this.offset().top + 1
		if newHeight <= 0
			redo = true
		else
			$this.height newHeight

	window.setTimeout(window.handleResize, 0) if redo

window.alignVertically = (element) ->
	$element = $(element)
	$parent = $element.parent()
	$element.css 'top', $parent.height() / 2 - $element.height() / 2

window.handleResize()
$(window).resize window.handleResize

$('.site-content').empty()