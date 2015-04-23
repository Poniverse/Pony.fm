window.pfm.preloaders['albums-list'] = [
	'albums', '$state'
	(albums, $state) ->
		albums.fetchList($state.params.page, true)
]

angular.module('ponyfm').controller "albums-list", [
	'$scope', 'albums', '$state'
	($scope, albums, $state) ->
		albums.fetchList($state.params.page).done (list) ->
			$scope.albums = list.albums
]