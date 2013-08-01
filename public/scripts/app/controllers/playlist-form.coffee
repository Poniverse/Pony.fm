angular.module('ponyfm').controller "playlist-form", [
	'$scope', 'dialog', 'playlists', 'playlist'
	($scope, dialog, playlists, playlist) ->
		$scope.isLoading = false
		$scope.form = playlist
		$scope.isNew = playlist.id == undefined

		$scope.errors = {}

		$scope.createPlaylist = () ->
			$scope.isLoading = true
			def =
				if $scope.isNew
					playlists.createPlaylist($scope.form)
				else
					playlists.editPlaylist($scope.form)

			def
				.done (res) ->
					dialog.close(res)

				.fail (errors)->
					$scope.errors = errors
					$scope.isLoading = false

		$scope.close = () -> dialog.close(null)
]
