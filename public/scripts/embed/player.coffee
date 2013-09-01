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
			else
				currentSound.play()
		else
			currentSound = soundManager.createSound
				url: '/t' + trackId + '/stream',
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