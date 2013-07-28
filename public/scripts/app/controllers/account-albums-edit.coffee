angular.module('ponyfm').controller "account-albums-edit", [
	'$scope', '$state', 'taxonomies', '$dialog', 'lightbox'
	($scope, $state, taxonomies, $dialog, lightbox) ->
		$scope.isNew = $state.params.album_id == undefined
		$scope.data.isEditorOpen = true
		$scope.errors = {}
		$scope.isDirty = false
		$scope.album = {}
		$scope.isSaving = false

		$scope.touchModel = -> $scope.isDirty = true

		$scope.refresh = () ->
			return if $scope.isNew
				$.getJSON('/api/web/albums/edit/' + $scope.data.selectedAlbum.id)
					.done (album) -> $scope.$apply ->
						$scope.isDirty = false
						$scope.errors = {}
						$scope.album =
							id: album.id
							title: album.title
							description: album.description
							remove_cover: false
							cover: album.cover_url

		if $scope.isNew
			$scope.album =
				title: ''
				description: ''
		else
			$scope.refresh();

		$scope.$on '$destroy', -> $scope.data.isEditorOpen = false

		$scope.saveAlbum = ->
			url =
				if $scope.isNew
					'/api/web/albums/create'
				else
					'/api/web/albums/edit' + $scope.album.id

			xhr = new XMLHttpRequest()
			xhr.onload = -> $scope.$apply ->
				$scope.isSaving = false
				response = $.parseJSON(xhr.responseText).errors
				if xhr.status != 200
					$scope.errors = {}
					_.each response.errors, (value, key) -> $scope.errors[key] = value.join ', '
					return

				$scope.$emit 'album-updated'

				if $scope.isNew
					$state.transitionTo 'account-content.albums.edit', {album_id: response.id}
				else
					$scope.refresh()

			formData = new FormData()

			_.each $scope.album, (value, name) ->
				if name == 'cover'
					return if value == null
					if typeof(value) == 'object'
						formData.append name, value, value.name
				else
					formData.append name, value

			xhr.open 'POST', url, true
			xhr.setRequestHeader 'X-Token', pfm.token
			$scope.isSaving = true
			xhr.send formData

		$scope.deleteAlbum = ->

		$scope.setCover = (image, type) ->
			delete $scope.album.cover_id
			delete $scope.album.cover

			if image == null
				$scope.album.remove_cover = true
			else if type == 'file'
				$scope.album.cover = image
			else if type == 'gallery'
				$scope.album.cover_id = image.id

			$scope.isDirty = true
]