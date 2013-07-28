angular.module('ponyfm').controller "account-settings", [
	'$scope', 'auth'
	($scope, auth) ->
		$scope.settings = {}
		$scope.errors = {}
		$scope.isDirty = false

		$scope.touchModel = () ->
			$scope.isDirty = true

		$scope.refresh = () ->
			$.getJSON('/api/web/account/settings')
				.done (res) -> $scope.$apply ->
					$scope.settings = res

		$scope.setAvatar = (image, type) ->
			delete $scope.settings.avatar_id
			delete $scope.settings.avatar

			if type == 'file'
				$scope.settings.avatar = image
			else if type == 'gallery'
				$scope.settings.avatar_id = image.id

			$scope.isDirty = true

		$scope.updateAccount = () ->
			return if !$scope.isDirty

			xhr = new XMLHttpRequest()
			xhr.onload = -> $scope.$apply ->
				$scope.isSaving = false
				response = $.parseJSON(xhr.responseText)
				if xhr.status != 200
					$scope.errors = {}
					_.each response.errors, (value, key) -> $scope.errors[key] = value.join ', '
					return

				$scope.isDirty = false
				$scope.errors = {}
				$scope.refresh()

			formData = new FormData()

			_.each $scope.settings, (value, name) ->
				if name == 'avatar'
					return if value == null
					if typeof(value) == 'object'
						formData.append name, value, value.name
				else
					formData.append name, value

			xhr.open 'POST', '/api/web/account/settings/save', true
			xhr.setRequestHeader 'X-Token', pfm.token
			$scope.isSaving = true
			xhr.send formData

		$scope.refresh()

		$scope.$on '$stateChangeStart', (e) ->
			return if $scope.selectedTrack == null || !$scope.isDirty
			e.preventDefault() if !confirm('Are you sure you want to leave this page without saving your changes?')
]