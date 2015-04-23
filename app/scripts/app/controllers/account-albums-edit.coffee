window.pfm.preloaders['account-albums-edit'] = [
	'account-tracks', 'account-albums', '$state'
	(tracks, albums, $state) ->
		defs = [tracks.refresh()]
		if $state.params.album_id
			defs.push albums.getEdit($state.params.album_id, true)

		$.when.all defs
]

angular.module('ponyfm').controller "account-albums-edit", [
	'$scope', '$state', '$dialog', 'account-albums'
	($scope, $state, $dialog, albums) ->
		$scope.isNew = $state.params.album_id == undefined
		$scope.data.isEditorOpen = true
		$scope.errors = {}
		$scope.isDirty = false
		$scope.album = {}
		$scope.isSaving = false
		$scope.tracks = []
		$scope.trackIds = {}

		$scope.toggleTrack = (track) ->
			if $scope.trackIds[track.id]
				delete $scope.trackIds[track.id]
				$scope.tracks.splice ($scope.tracks.indexOf track), 1
			else
				$scope.trackIds[track.id] = track
				$scope.tracks.push track

			$scope.isDirty = true

		$scope.sortTracks = () ->
			$scope.isDirty = true

		$scope.touchModel = -> $scope.isDirty = true

		$scope.setCover = (image, type) ->
			delete $scope.album.cover_id
			delete $scope.album.cover

			if image == null
				$scope.album.remove_cover = true
			else if type == 'file'
				$scope.album.cover = image
			else if type == 'gallery'
				$scope.album.cover_id = image.id

			$scope.isDirty = true

		$scope.$on '$destroy', -> $scope.data.isEditorOpen = false

		$scope.saveAlbum = ->
			return if !$scope.isNew && !$scope.isDirty

			url =
				if $scope.isNew
					'/api/web/albums/create'
				else
					'/api/web/albums/edit/' + $state.params.album_id

			xhr = new XMLHttpRequest()
			xhr.onload = -> $scope.$apply ->
				$scope.isSaving = false
				response = $.parseJSON(xhr.responseText)
				if xhr.status != 200
					$scope.errors = {}
					_.each response.errors, (value, key) -> $scope.errors[key] = value.join ', '
					return

				$scope.$emit 'album-updated'

				if $scope.isNew
					$scope.isDirty = false
					$scope.$emit 'album-created'
					$state.transitionTo 'account.albums.edit', {album_id: response.id}
				else
					$scope.isDirty = false
					$scope.data.selectedAlbum.title = $scope.album.title
					$scope.data.selectedAlbum.covers.normal = response.real_cover_url

			formData = new FormData()

			_.each $scope.album, (value, name) ->
				if name == 'cover'
					return if value == null
					if typeof(value) == 'object'
						formData.append name, value, value.name
				else
					formData.append name, value

			formData.append 'track_ids', _.map($scope.tracks, (t) -> t.id).join()

			xhr.open 'POST', url, true
			xhr.setRequestHeader 'X-Token', pfm.token
			$scope.isSaving = true
			xhr.send formData

		$scope.deleteAlbum = () ->
			$dialog.messageBox('Delete ' + $scope.album.title, 'Are you sure you want to delete "' + $scope.album.title + '"? This cannot be undone.', [
				{result: 'ok', label: 'Yes', cssClass: 'btn-danger'}, {result: 'cancel', label: 'No', cssClass: 'btn-primary'}
			]).open().then (res) ->
				return if res == 'cancel'
				$.post('/api/web/albums/delete/' + $scope.album.id, {_token: window.pfm.token})
					.then -> $scope.$apply ->
						$scope.$emit 'album-deleted'
						$state.transitionTo 'account.albums'

		$scope.setCover = (image, type) ->
			delete $scope.album.cover_id
			delete $scope.album.cover

			if image == null
				$scope.album.remove_cover = true
			else if type == 'file'
				$scope.album.cover = image
			else if type == 'gallery'
				$scope.album.cover_id = image.id

			$scope.isDirty = true

		if !$scope.isNew
			albums.getEdit($state.params.album_id).done (album) ->
				$scope.album =
					id: album.id
					title: album.title
					description: album.description
					remove_cover: false
					cover: album.cover_url

				$scope.tracks = []
				$scope.tracks.push track for track in album.tracks
				$scope.trackIds[track.id] = track for track in album.tracks

		else
			$scope.album =
				title: ''
				description: ''

		$scope.$on '$locationChangeStart', (e) ->
			return if !$scope.isDirty
			e.preventDefault() if !confirm('Are you sure you want to leave this page without saving your changes?')
]