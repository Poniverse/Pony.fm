angular.module('ponyfm').controller "account-albums", [
	'$scope', '$state', 'taxonomies', '$dialog', 'lightbox'
	($scope, $state, taxonomies, $dialog, lightbox) ->
		refreshList = () ->
			$.getJSON('/api/web/albums/owned')
				.done (albums) ->
					$scope.albums = albums

		refreshList()
		$scope.data =
			isEditorOpen: false
			selectedAlbum: null

		$scope.$on '$stateChangeSuccess', () ->
			if $state.params.album_id
				selectAlbum albumsDb[$state.params.album_id]
			else
				selectAlbum null
]