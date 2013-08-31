angular.module('ponyfm').controller "uploader", [
	'$scope', 'auth', 'upload', '$state'
	($scope, auth, upload, $state) ->

		$scope.data = upload

		$scope.$on 'upload-finished', (e, upload) ->
]