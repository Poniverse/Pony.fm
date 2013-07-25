angular.module('ponyfm').controller "application", [
	'$scope', 'auth', '$location', 'upload', '$state', '$stateParams', 'taxonomies'
	($scope, auth, $location, upload, $state, $stateParams, taxonomies) ->
		$scope.auth = auth.data
		$scope.$state = $state
		$scope.$stateParams = $stateParams

		$scope.logout = () ->
			auth.logout().done -> location.reload()

		$scope.isActive = (loc) -> $location.path() == loc
		$scope.$on '$viewContentLoaded', () -> window.handleResize()

		# Show loading screen here?
		taxonomies.refresh()
]