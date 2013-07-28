angular.module('ponyfm').controller "account-albums", [
	'$scope', '$state', 'taxonomies', '$dialog', 'lightbox'
	($scope, $state, taxonomies, $dialog, lightbox) ->
		albumsDb = {}
		lastIndex = null

		$scope.albums = []
		$scope.data =
			isEditorOpen: false
			selectedAlbum: null
			tracksDb: {}

		refreshTrackDatabase = () ->
			$.getJSON('/api/web/tracks/owned?published=true&in_album=false')
				.done (tracks) -> $scope.$apply ->
					$scope.data.tracksDb[track.id] = track for track in tracks

		refreshList = () ->
			$.getJSON('/api/web/albums/owned')
				.done (albums) -> $scope.$apply ->
					index = 0
					album.index = index++ for album in albums
					albumsDb[album.id] = album for album in albums
					$scope.albums = albums

					if $state.params.album_id != undefined
						selectAlbum albumsDb[$state.params.album_id]
					else if lastIndex != null
						if $scope.albums.length
							album = null
							if $scope.albums.length > lastIndex + 1
								album = $scope.albums[lastIndex]
							else if lastIndex > 0
								album = $scope.albums[lastIndex - 1]
							else
								album = $scope.albums[0]

						$state.transitionTo 'account-content.albums.edit', {album_id: album.id}

		selectAlbum = (album) -> $scope.data.selectedAlbum = album

		$scope.$on '$stateChangeSuccess', () ->
			if $state.params.album_id
				selectAlbum albumsDb[$state.params.album_id]
			else
				selectAlbum null

		$scope.$on 'album-created', () -> refreshList()

		$scope.$on 'album-deleted', () ->
			lastIndex = $scope.data.selectedAlbum.index
			refreshList()

		$scope.$on 'album-updated', () ->
			refreshTrackDatabase()

		refreshList()
		refreshTrackDatabase()
]