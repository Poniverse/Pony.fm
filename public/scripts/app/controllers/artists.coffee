angular.module('ponyfm').controller "artists", [
	'$scope', 'artists', '$state'
	($scope, artists, $state) ->

		refreshPages = (list) ->
			$scope.artists = list.artists
			$scope.currentPage = parseInt(list.current_page)
			$scope.totalPages = parseInt(list.total_pages)

			delete $scope.nextPage
			delete $scope.prevPage
			$scope.nextPage = $scope.currentPage + 1 if $scope.currentPage < $scope.totalPages
			$scope.prevPage = $scope.currentPage - 1 if $scope.currentPage > 1
			$scope.pages = [1..$scope.totalPages]

		artists.fetchList($state.params.page).done refreshPages
		$scope.$on 'artists-feteched', (e, list) -> refreshPages(list)

		$scope.gotoPage = (page) ->
			$state.transitionTo 'artists.list', {page: page}
]