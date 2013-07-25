window.handleResize = () ->
	windowHeight = $(window).height()
	$siteBody = $ '.site-body'
	$siteBody.height windowHeight - $('header').height() - 1

	$('.strech-to-bottom').each () ->
		$this = $ this
		$this.height windowHeight - $this.offset().top

window.handleResize()
$(window).resize window.handleResize

$('.site-content').empty()