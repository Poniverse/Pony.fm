angular.module('ponyfm').directive 'pfmComments', () ->
	restrict: 'E'
	templateUrl: '/templates/directives/comments.html'
	scope:
		resource: '=resource',
		type: '@type'

	controller: [
		'$scope', 'comments'
		($scope, comments, auth) ->

			$scope.isWorking = false
			$scope.content = ''
			$scope.auth = auth.data

			refresh = () ->
				comments.fetchList($scope.type, $scope.resource.id, true).done (comments) ->
					$scope.resource.comments.count = comments.count
					$scope.resource.comments.list.length = 0
					$scope.resource.comments.list.push comment for comment in comments.list
					$scope.isWorking = false

			$scope.addComment = () ->
				content = $scope.content
				$scope.content = ''
				$scope.isWorking = true
				comments.addComment($scope.type, $scope.resource.id, content).done () ->
					refresh()
	]