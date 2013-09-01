$player = $ '.player'
$favourite = $player.find '.favourite'
trackId = $player.data 'track-id'

$favourite.click (e) ->
	e.preventDefault()

	$.post('/api/web/favourites/toggle', {type: 'track', id: trackId, _token: pfm.token}).done (res) ->
		if res.is_favourited
			$player.addClass 'favourited'
		else
			$player.removeClass 'favourited'