angular.module('ponyfm').directive 'pfmFavouriteButton', () ->
	restrict: 'E'
	templateUrl: '/templates/directives/favourite-button.html'
	scope:
		resource: '=resource',
		class: '@class',
		type: '@type'

	controller: [
		'$scope', 'favourites', 'auth'
		($scope, favourites, auth) ->
			$scope.auth = auth.data

			$scope.isWorking = false
			$scope.toggleFavourite = () ->
				$scope.isWorking = true
				favourites.toggle($scope.type, $scope.resource.id).done (res) ->
					$scope.isWorking = false
					$scope.resource.user_data.is_favourited = res.is_favourited
	]