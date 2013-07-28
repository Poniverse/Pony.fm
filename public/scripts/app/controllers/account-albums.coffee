angular.module('ponyfm').controller "account-albums", [
	'$scope', '$state', 'taxonomies', '$dialog', 'lightbox'
	($scope, $state, taxonomies, $dialog, lightbox) ->
		albumsDb = {}

		$scope.albums = []
		$scope.data =
			isEditorOpen: false
			selectedAlbum: null

		refreshList = () ->
			$.getJSON('/api/web/albums/owned')
				.done (albums) -> $scope.$apply ->
					albumsDb[album.id] = album for album in albums
					$scope.albums = albums

					selectAlbum albumsDb[$state.params.album_id] if $state.params.album_id != undefined

		selectAlbum = (album) -> $scope.data.selectedAlbum = album

		$scope.$on '$stateChangeSuccess', () ->
			if $state.params.album_id
				selectAlbum albumsDb[$state.params.album_id]
			else
				selectAlbum null

		refreshList()
]