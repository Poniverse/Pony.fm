angular.module('ponyfm').controller "albums", [
	'$scope', 'albums', '$state'
	($scope, albums, $state) ->

		refreshPages = (list) ->
			$scope.albums = list.albums
			$scope.currentPage = parseInt(list.current_page)
			$scope.totalPages = parseInt(list.total_pages)

			delete $scope.nextPage
			delete $scope.prevPage
			$scope.nextPage = $scope.currentPage + 1 if $scope.currentPage < $scope.totalPages
			$scope.prevPage = $scope.currentPage - 1 if $scope.currentPage > 1
			$scope.pages = [1..$scope.totalPages]

		albums.fetchList($state.params.page).done refreshPages
		$scope.$on 'albums-feteched', (e, list) -> refreshPages(list)

		$scope.gotoPage = (page) ->
			$state.transitionTo 'albums.list', {page: page}
]