angular.module('ponyfm').controller "sidebar", [
	'$scope', '$dialog', 'playlists'
	($scope, $dialog, playlists) ->
		$scope.playlists = playlists.pinnedPlaylists

		$scope.createPlaylist = () ->
			dialog = $dialog.dialog
				templateUrl: '/templates/partials/playlist-dialog.html'
				controller: 'playlist-form'
				resolve: {
					playlist: () ->
						is_public: true
						is_pinned: true
						name: ''
						description: ''
				}

			dialog.open()

		$scope.editPlaylist = (playlist) ->
			dialog = $dialog.dialog
				templateUrl: '/templates/partials/playlist-dialog.html'
				controller: 'playlist-form'
				resolve: {
					playlist: () -> angular.copy playlist
				}

			dialog.open()

		$scope.unpinPlaylist = (playlist) ->
			playlist.is_pinned = false;
			playlists.editPlaylist playlist

		$scope.deletePlaylist = (playlist) ->
			$dialog.messageBox('Delete ' + playlist.title, 'Are you sure you want to delete "' + playlist.title + '"? This cannot be undone.', [
				{result: 'ok', label: 'Yes', cssClass: 'btn-danger'},
				{result: 'cancel', label: 'No', cssClass: 'btn-primary'}
			]).open().then (res) ->
				return if res == 'cancel'
				playlists.deletePlaylist playlist
]