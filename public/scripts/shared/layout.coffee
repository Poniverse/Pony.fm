window.handleResize = () ->
	windowHeight = $(window).height()
	$siteBody = $ '.site-body'
	$siteBody.height windowHeight - $('header').height() - 1

	$('.stretch-to-bottom').each () ->
		$this = $ this
		newHeight = windowHeight - $this.offset().top + 1
		if newHeight > 0
			$this.height newHeight

window.alignVertically = (element) ->
	$element = $(element)
	$parent = $element.parent()
	$element.css 'top', $parent.height() / 2 - $element.height() / 2

window.handleResize()
$(window).resize window.handleResize

$('.site-content').empty()