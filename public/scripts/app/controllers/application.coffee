angular.module('ponyfm').controller "application", [
	'$scope', 'auth', '$location', 'upload', '$state', '$stateParams', 'taxonomies'
	($scope, auth, $location, upload, $state, $stateParams, taxonomies) ->
		$scope.auth = auth.data
		$scope.$state = $state
		$scope.$stateParams = $stateParams

		$scope.logout = () ->
			auth.logout().done -> location.reload()

		$scope.isActive = (loc) -> $location.path() == loc
		$scope.$on '$viewContentLoaded', () ->
			window.setTimeout window.handleResize, 500

		# Show loading screen here?
		taxonomies.refresh()

		$scope.mainViewAnimation = 'slide-down';

		$scope.$on '$stateChangeStart', (e, newState, newParams, oldState) ->
			oldIndex =
				if (oldState && oldState.navigation && oldState.navigation.index)
					oldState.navigation.index
				else
					0

			newIndex =
				if (newState && newState.navigation && newState.navigation.index)
					newState.navigation.index
				else
					0

			oldSubIndex =
				if (oldState && oldState.navigation && oldState.navigation.subIndex)
					oldState.navigation.subIndex
				else
					0

			newSubIndex =
				if (newState && newState.navigation && newState.navigation.subIndex)
					newState.navigation.subIndex
				else
					0

			$scope.mainViewAnimation = 'slide-down' if oldIndex > newIndex
			$scope.mainViewAnimation = 'slide-up' if oldIndex < newIndex
			$scope.mainViewAnimation = 'slide-right' if oldIndex == newIndex

			$scope.subViewAnimation = 'slide-right' if oldSubIndex > newSubIndex
			$scope.subViewAnimation = 'slide-left' if oldSubIndex < newSubIndex
			$scope.subViewAnimation = 'slide-up' if oldSubIndex == newSubIndex
]