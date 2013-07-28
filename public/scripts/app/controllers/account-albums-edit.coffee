angular.module('ponyfm').controller "account-albums-edit", [
	'$scope', '$state', 'taxonomies', '$dialog', 'lightbox'
	($scope, $state, taxonomies, $dialog, lightbox) ->
		$scope.isNew = $state.params.album_id == null
		$scope.data.isEditorOpen = true
		$scope.errors = {}
		$scope.isDirty = false

		$scope.touchModel = -> $scope.isDirty = true

		if $scope.isNew
			$scope.album =
				title: ''
				description: ''

		$scope.$on '$destroy', -> $scope.data.isEditorOpen = false
]