window.handleResize = () ->
	windowHeight = $(window).height()
	$siteBody = $ '.site-body'
	$siteBody.height windowHeight - $('header').height()

	$('.stretch-to-bottom').each () ->
		$this = $ this
		newHeight = windowHeight - $this.offset().top
		if newHeight > 0
			$this.height newHeight

	$('.revealable').each () ->
		$this = $ this
		return if $this.data 'real-height'
		$this.data 'real-height', $this.height()
		$this.css
			height: '15em'

		$this.find('.reveal').click (e) ->
			e.preventDefault()
			$this.css {height: 'auto'}
			$(this).fadeOut 200

window.alignVertically = (element) ->
	$element = $(element)
	$parent = $element.parent()
	$element.css 'top', $parent.height() / 2 - $element.height() / 2

window.handleResize()
$(window).resize window.handleResize

$('.site-content').empty()