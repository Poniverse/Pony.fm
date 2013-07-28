angular.module('ponyfm').controller "account-playlists", [
	'$scope', 'auth', '$dialog', 'playlists'
	($scope, auth, $dialog, playlists) ->
		$scope.playlists = []

		$scope.refresh = ->
			$.get('/api/web/playlists/owned')
				.done (playlists) -> $scope.$apply ->
					$scope.playlists.push playlist for playlist in playlists

		$scope.editPlaylist = (playlist) ->
			dialog = $dialog.dialog
				templateUrl: '/templates/partials/playlist-dialog.html'
				controller: 'playlist-form'
				resolve: {
					playlist: () -> angular.copy playlist
				}

			dialog.open()

		$scope.togglePlaylistPin = (playlist) ->
			playlist.is_pinned = !playlist.is_pinned;
			playlists.editPlaylist playlist

		$scope.deletePlaylist = (playlist) ->
			$dialog.messageBox('Delete ' + playlist.title, 'Are you sure you want to delete "' + playlist.title + '"? This cannot be undone.', [
				{result: 'ok', label: 'Yes', cssClass: 'btn-danger'},
				{result: 'cancel', label: 'No', cssClass: 'btn-primary'}
			]).open().then (res) ->
				return if res == 'cancel'
				playlists.deletePlaylist(playlist).done ->
					$scope.playlists.splice _.indexOf($scope.playlists, (p) -> p.id == playlist.id), 1

		$scope.$on 'playlist-updated', (e, playlist) ->
			console.log playlist
			index = _.indexOf($scope.playlists, (p) -> p.id == playlist.id)
			content = $scope.playlists[index]
			_.each playlist, (value, name) -> content[name] = value
			$scope.playlists.sort (left, right) -> left.title.localeCompare right.title

		$scope.refresh();
]