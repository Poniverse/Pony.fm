angular.module('ponyfm').controller "login", [
	'$scope', 'auth'
	($scope, auth) ->

		$scope.messages = []

		$scope.login =
			remember: true

			submit: () ->
				$scope.messages = []

				auth.login(this.email, this.password, this.remember)
					.done ->
						location.reload()
					.fail (messages) ->
						$scope.messages = _.values messages
]