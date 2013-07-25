angular.module('ponyfm').directive 'uploader', [
	'upload'
	(upload) -> (scope) ->
		$body = $ 'body'
		$notice = $("<div class='file-over-notice'><p>Drop the files anywhere to begin your upload!</p></div>").appendTo($body)
		notice = $notice[0]

		window.addEventListener 'dragover', (e) ->
			e.preventDefault()
			$body.addClass 'file-over'

		notice.addEventListener 'dragleave', (e) ->
			e.preventDefault()
			$body.removeClass 'file-over'

		notice.addEventListener 'drop', (e) ->
			e.preventDefault()
			$body.removeClass 'file-over'

			files = e.target.files || e.dataTransfer.files
			scope.$apply -> upload.upload files
]