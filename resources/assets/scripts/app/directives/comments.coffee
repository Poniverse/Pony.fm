angular.module('ponyfm').directive 'pfmComments', () ->
	restrict: 'E'
	templateUrl: '/templates/directives/comments.html'
	scope:
		resource: '=resource',
		type: '@type'

	controller: [
		'$scope', 'comments', 'auth'
		($scope, comments, auth) ->

			$scope.isWorking = false
			$scope.content = ''
			$scope.auth = auth.data

			refresh = () ->
				comments.fetchList($scope.type, $scope.resource.id, true).done (comments) ->
					$scope.resource.comments.length = 0
					$scope.resource.comments.push comment for comment in comments.list
					$scope.isWorking = false

			$scope.addComment = () ->
				content = $scope.content
				$scope.content = ''
				$scope.isWorking = true
				comments.addComment($scope.type, $scope.resource.id, content).done () ->
					refresh()
	]