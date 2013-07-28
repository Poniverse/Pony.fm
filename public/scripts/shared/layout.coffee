window.handleResize = () ->
	windowHeight = $(window).height()
	$siteBody = $ '.site-body'
	$siteBody.height windowHeight - $('header').height() - 1

	$('.stretch-to-bottom').each () ->
		$this = $ this
		$this.height windowHeight - $this.offset().top

window.alignVertically = (element) ->
	$element = $(element)
	$parent = $element.parent()
	$element.css 'top', $parent.height() / 2 - $element.height() / 2

window.handleResize()
$(window).resize window.handleResize

$('.site-content').empty()