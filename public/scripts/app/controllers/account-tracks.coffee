angular.module('ponyfm').controller "account-tracks", [
	'$scope', '$state', 'taxonomies', '$dialog', 'lightbox'
	($scope, $state, taxonomies, $dialog, lightbox) ->
		$scope.selectedTrack = null
		$scope.isDirty = false
		$scope.isSaving = false
		$scope.taxonomies = taxonomies
		$scope.selectedSongsTitle = 'None'
		$scope.selectedSongs = {}
		$scope.albums = []
		$scope.selectedAlbum = null

		tracksDb = {}
		albumsDb = {}

		$scope.selectAlbum = (album) ->
			$scope.selectedAlbum = album
			$scope.edit.album_id = if album then album.id else null
			$scope.isDirty = true

		$scope.setCover = (image, type) ->
			delete $scope.edit.cover_id
			delete $scope.edit.cover

			if image == null
				$scope.edit.remove_cover = true
			else if type == 'file'
				$scope.edit.cover = image
			else if type == 'gallery'
				$scope.edit.cover_id = image.id

			$scope.isDirty = true

		refreshAlbums = () ->
			$.getJSON('/api/web/albums/owned')
				.done (albums) -> $scope.$apply ->
					albumsDb[album.id] = album for album in albums
					$scope.albums = albums
					$scope.selectedAlbum = if $scope.edit && $scope.edit.album_id then albumsDb[$scope.edit.album_id] else null

		updateSongDisplay = () ->
			if _.size $scope.selectedSongs
				$scope.selectedSongsTitle = (_.map _.values($scope.selectedSongs), (s) -> s.title).join(', ')
			else
				$scope.selectedSongsTitle = 'None'

		$scope.toggleSong = (song) ->
			$scope.isDirty = true
			if $scope.selectedSongs[song.id]
				delete $scope.selectedSongs[song.id]
			else
				$scope.selectedSongs[song.id] = song

			updateSongDisplay()

		$scope.updateIsVocal = () ->
			delete $scope.errors.lyrics if !$scope.edit.is_vocal

		$scope.updateTrack = (track) ->
			xhr = new XMLHttpRequest()
			xhr.onload = -> $scope.$apply ->
				$scope.isSaving = false
				if xhr.status != 200
					errors =
						if xhr.getResponseHeader('content-type') == 'application/json'
							$.parseJSON(xhr.responseText).errors
						else
							['There was an unknown error!']

					$scope.errors = {}
					_.each errors, (value, key) -> $scope.errors[key] = value.join ', '
					return

				$scope.selectedTrack.is_published = true
				selectTrack $scope.selectedTrack

			formData = new FormData();
			_.each $scope.edit, (value, name) ->
				if name == 'cover'
					return if value == null
					if typeof(value) == 'object'
						formData.append name, value, value.name
				else
					formData.append name, value

			if $scope.edit.track_type_id == 2
				formData.append 'show_song_ids', _.map(_.values($scope.selectedSongs), (s) -> s.id).join()

			xhr.open 'POST', '/api/web/tracks/edit/' + $scope.edit.id, true
			xhr.setRequestHeader 'X-Token', pfm.token
			$scope.isSaving = true
			xhr.send formData

		$scope.filters =
			published: [
				{title: 'Either', query: ''},
				{title: 'Yes', query: 'published=1'},
				{title: 'No', query: 'published=0'}]

			sort: [
				{title: 'Newest to Oldest', query: 'order=created_at,desc'},
				{title: 'Oldest to Newest', query: 'order=created_at,asc'}]

			genres: {}
			trackTypes: {}

		$scope.filter =
			published: $scope.filters.published[0]
			sort: $scope.filters.sort[0]
			genres: {}
			trackTypes: {}

		$scope.titles =
			genres: 'All'
			trackTypes: 'All'

		taxonomies.refresh().done () ->
			for genre in taxonomies.genres
				$scope.filters.genres[genre.id] =
					id: genre.id
					title: genre.name
					query: 'genres[]=' + genre.id
			for type in taxonomies.trackTypes
				$scope.filters.trackTypes[type.id] =
					id: type.id
					title: type.title
					query: 'types[]=' + type.id

		$scope.updateFilter = (type, filter) ->
			$scope.filter[type] = filter
			$scope.refreshList()

		$scope.toggleFilter = (type, id) ->
			if !$scope.filter[type][id]
				$scope.filter[type][id] = $scope.filters[type][id]
			else
				delete $scope.filter[type][id]

			length = _.keys($scope.filter[type]).length
			if length == 1
				$scope.titles[type] = _.map($scope.filter[type], (f) -> f.title).join ', '
			else if length > 1
				$scope.titles[type] = length + ' selected'
			else
				$scope.titles[type] = 'All'

			$scope.refreshList()

		$scope.refreshList = () ->
			parts = [$scope.filter.sort.query, $scope.filter.published.query]
			_.each $scope.filter.genres, (g) -> parts.push g.query
			_.each $scope.filter.trackTypes, (g) -> parts.push g.query
			query = parts.join '&'
			$.getJSON('/api/web/tracks/owned?' + query).done (tracks) -> $scope.$apply -> showTracks tracks

		showTracks = (tracks) ->
			tracksDb = {}
			$scope.tracks = tracks
			tracksDb[track.id] = track for track in tracks

		selectTrack = (t) ->
			$scope.selectedTrack = t
			return if !t
			$.getJSON('/api/web/tracks/edit/' + t.id)
				.done (track) -> $scope.$apply ->
					$scope.isDirty = false
					$scope.errors = {}
					$scope.edit =
						id: track.id
						title: track.title
						description: track.description
						lyrics: track.lyrics
						is_explicit: track.is_explicit
						is_downloadable: track.is_downloadable
						is_vocal: track.is_vocal
						license_id: track.license_id
						genre_id: track.genre_id
						track_type_id: track.track_type_id
						released_at: if track.released_at then track.released_at.date else ''
						remove_cover: false
						cover: track.cover_url
						album_id: track.album_id

					trackDbItem = tracksDb[t.id]
					trackDbItem.title = track.title
					trackDbItem.is_explicit = track.is_explicit
					trackDbItem.is_vocal = track.is_vocal
					trackDbItem.genre_id = track.genre_id
					trackDbItem.is_published = track.is_published
					trackDbItem.cover_url = track.real_cover_url

					$scope.selectedAlbum = if track.album_id then albumsDb[track.album_id] else null
					$scope.selectedSongs = {}
					$scope.selectedSongs[song.id] = song for song in track.show_songs
					updateSongDisplay()

		$scope.touchModel = -> $scope.isDirty = true

		$.getJSON('/api/web/tracks/owned?order=created_at,desc').done (tracks) -> $scope.$apply ->
			showTracks tracks
			if $state.params.track_id
				selectTrack tracksDb[$state.params.track_id]

		$scope.selectTrack = (track) -> $scope.selectedTrack = track
		$scope.deleteTrack = (track) ->
			$dialog.messageBox('Delete ' + track.title, 'Are you sure you want to delete "' + track.title + '"? This cannot be undone.', [
				{result: 'ok', label: 'Yes', cssClass: 'btn-danger'}, {result: 'cancel', label: 'No', cssClass: 'btn-primary'}
			]).open().then (res) ->
				return if res == 'cancel'
				selectTrack null if track == $scope.selectedTrack
				$.post('/api/web/tracks/delete/' + track.id, {_token: window.pfm.token})
					.then ->
						$scope.refreshList()

		$scope.$on '$stateChangeSuccess', () ->
			if $state.params.track_id
				selectTrack tracksDb[$state.params.track_id]
			else
				selectTrack null

		$scope.$on '$stateChangeStart', (e) ->
			return if $scope.selectedTrack == null || !$scope.isDirty
			e.preventDefault() if !confirm('Are you sure you want to leave this page without saving your changes?')

		refreshAlbums()
]