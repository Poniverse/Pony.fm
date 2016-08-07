# Pony.fm - A community for pony fan music.
# Copyright (C) 2015 Peter Deltchev
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

window.isPlaying = false

window.handleResize = () ->
	windowHeight = $(window).height()
	windowWidth = $(window).width()
	isMobile = windowWidth <= 480
	$siteBody = $ '.site-body'

	#if window.isPlaying
	#	$siteBody.height windowHeight - ($('.top-bar').height() + $('.now-playing').height())
	#else
	#	$siteBody.height windowHeight - $('.top-bar').height()

	$('.dropdown-menu').each () ->
		$this = $ this
		newMaxHeight = windowHeight - $this.parent().offset().top - $this.parent().height() - 5
		$this.css
			'max-height': newMaxHeight

	$('.site-content').each () ->
		$this = $ this
		newHeight = windowHeight - $this.offset().top
		if isMobile
			newHeight = newHeight - 5
		if window.isPlaying
			newHeight = newHeight - $('.now-playing').height()
		if newHeight > 0
			$this.height newHeight

	$('.revealable').each () ->
		$this = $ this
		return if $this.data 'real-height'
		$this.data 'real-height', $this.height()
		$this.css
			maxHeight: '15em'

		$this.find('.reveal').click (e) ->
			e.preventDefault()
			$this.css {height: 'auto'}
			$(this).fadeOut 200

	$('.blurred-background').each () ->
		$this = $ this
		$this.height $('.resource-details header').height() + 55

	$('.resource-details .single-player button').each () ->
		$this = $ this
		$this.css 'top', $('.resource-details header').height() + 5

window.alignVertically = (element) ->
	$element = $(element)
	$parent = $element.parent()
	$element.css 'top', $parent.height() / 2 - $element.height() / 2

window.handleResize()
$(window).resize window.handleResize

$('.site-content').empty()

$(document).ready () ->
		$('body').removeClass('loading');
