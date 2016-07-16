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


require 'script!../base/jquery-2.0.2'
require '../base/jquery.timeago'
require '../base/soundmanager2-nodebug'
require './favourite.coffee'

require 'script!../base/underscore'
require '../shared/layout.coffee'
require 'script!../shared/underscore-extensions'


$('.timeago').timeago()

loaderDef = new $.Deferred()

soundManager.setup
	url: '/flash/soundmanager/'
	flashVersion: 9
	onready: () ->
		loaderDef.resolve()

loaderDef.done ->
	$player = $('.player')
	$play = $('.player .play')
	$progressBar = $player.find '.progressbar'
	$loadingBar = $progressBar.find '.loader'
	$seekBar = $progressBar.find '.seeker'
	currentSound = null
	isPlaying = false
	trackId = $player.data('track-id')
	duration = $player.data('duration')

	$player.removeClass 'loading'

	setPlaying = (playing) ->
		isPlaying = playing
		if playing
			$player.addClass 'playing'
			$player.removeClass 'paused'
		else
			$player.addClass 'paused'
			$player.removeClass 'playing'

	$progressBar.click (e) ->
		return if !currentSound
		percent = ((e.pageX - $progressBar.offset().left) / $progressBar.width())
		duration = parseFloat(duration)
		progress = percent * duration
		currentSound.setPosition(progress)

	$play.click ->
		if currentSound
			if isPlaying
				currentSound.pause()
				$('.button > i').text('play_arrow')
			else
				currentSound.play()
				$('.button > i').text('pause')
		else

			currentSound = soundManager.createSound
				url: ['/t' + trackId + '/stream.mp3', '/t' + trackId + '/stream.ogg', '/t' + trackId + '/stream.m4a'],
				volume: 50

				whileloading: ->
					loadingProgress = (currentSound.bytesLoaded / currentSound.bytesTotal) * 100
					$loadingBar.css
						width: loadingProgress + '%'

				whileplaying: ->
					progress = (currentSound.position / (duration)) * 100
					$seekBar.css
						width: progress + '%'

				onfinish: ->
					setPlaying false
					currentSound = null
					$loadingBar.css {width: '0'}
					$seekBar.css {width: '0'}
					$player.removeClass 'playing'
					$player.removeClass 'paused'

				onstop: ->
					setPlaying false

				onplay: ->

				onresume: ->
					setPlaying true

				onpause: ->
					setPlaying false

			setPlaying true
			currentSound.play()
