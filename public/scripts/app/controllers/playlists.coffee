angular.module('ponyfm').controller "playlists", [
	'$scope', 'playlists', '$state'
	($scope, playlists, $state) ->

		refreshPages = (list) ->
			$scope.playlists = list.playlists
			$scope.currentPage = parseInt(list.current_page)
			$scope.totalPages = parseInt(list.total_pages)

			delete $scope.nextPage
			delete $scope.prevPage
			$scope.nextPage = $scope.currentPage + 1 if $scope.currentPage < $scope.totalPages
			$scope.prevPage = $scope.currentPage - 1 if $scope.currentPage > 1
			$scope.pages = [1..$scope.totalPages]

		playlists.fetchList($state.params.page).done refreshPages
		$scope.$on 'playlists-feteched', (e, list) -> refreshPages(list)

		$scope.gotoPage = (page) ->
			$state.transitionTo 'content.playlists.list', {page: page}
]