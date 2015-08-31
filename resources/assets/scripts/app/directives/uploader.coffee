angular.module('ponyfm').directive 'uploader', [
	'upload'
	(upload) -> (scope, element) ->
		$dropzone = $(element)

		$dropzone[0].addEventListener 'dragover', (e) ->
			e.preventDefault()
			$dropzone.addClass 'file-over'

		$dropzone[0].addEventListener 'dragleave', (e) ->
			e.preventDefault()
			$dropzone.removeClass 'file-over'

		$dropzone[0].addEventListener 'drop', (e) ->
			e.preventDefault()
			$dropzone.removeClass 'file-over'

			files = e.target.files || e.dataTransfer.files
			scope.$apply -> upload.upload files
]