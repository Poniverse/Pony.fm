angular.module('ponyfm').factory('upload', [
	'$rootScope'
	($rootScope) ->
		self =
			queue: []

			upload: (files) ->
				_.each files, (file) ->
					upload =
						name: file.name
						progress: 0
						uploadedSize: 0
						size: file.size
						index: self.queue.length
						isUploading: true
						success: false
						error: null

					self.queue.push upload
					$rootScope.$broadcast 'upload-added', upload

					xhr = new XMLHttpRequest()
					xhr.upload.onprogress = (e) ->
						$rootScope.$apply ->
							upload.uploadedSize = e.loaded
							upload.progress = e.loaded / upload.size * 100
							$rootScope.$broadcast 'upload-progress', upload

					xhr.onload = -> $rootScope.$apply ->
						upload.isUploading = false
						if xhr.status != 200
							error =
								if xhr.getResponseHeader('content-type') == 'application/json'
									$.parseJSON(xhr.responseText).errors.track.join ', '
								else
									'There was an unknown error!'

							upload.error = error
							$rootScope.$broadcast 'upload-error', [upload, error]
						else
							upload.success = true
							upload.trackId = $.parseJSON(xhr.responseText).id

						$rootScope.$broadcast 'upload-finished', upload
					formData = new FormData();
					formData.append('track', file);

					xhr.open 'POST', '/api/web/tracks/upload', true
					xhr.setRequestHeader 'X-Token', pfm.token
					xhr.send formData
])