angular.module('ponyfm').controller "credits", [
	'$scope', 'dialog',
	($scope, dialog) ->
		$scope.close = () -> dialog.close(null)
]
