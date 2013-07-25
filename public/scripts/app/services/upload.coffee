angular.module('ponyfm').factory('upload', [
	'$rootScope'
	($rootScope) ->
		self =
			queue: []
			totalBytes: 0
			totalBytesUploaded: 0

			upload: (files) ->
				$rootScope.$broadcast 'upload-queue-started' if self.queue.length == 0

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
					self.totalBytes += file.size
					$rootScope.$broadcast 'upload-added', upload

					xhr = new XMLHttpRequest()
					xhr.upload.onprogress = (e) ->
						$rootScope.$apply ->
							upload.uploadedSize = e.loaded
							upload.progress = e.loaded / upload.size * 100
							self.totalBytesUploaded = _.reduce self.queue, ((i, u) -> i + u.uploadedSize), 0
							$rootScope.$broadcast 'upload-progress', upload

					xhr.onload = -> $rootScope.$apply ->
						upload.isUploading = false
						if xhr.status != 200
							error =
								if xhr.getResponseHeader('content-type') == 'application/json'
									$.parseJSON(xhr.responseText).message
								else
									'There was an unknown error!'

							upload.error = error
							$rootScope.$broadcast 'upload-error', [upload, error]
						else
							upload.success = true

						$rootScope.$broadcast 'upload-finished', upload

						if (_.every self.queue, (u) -> !u.isUploading)
							self.queue = []
							self.totalBytes = 0
							self.totalBytesUploaded = 0
							$rootScope.$broadcast 'upload-queue-ended'

					formData = new FormData();
					formData.append('track', file);

					xhr.open 'POST', '/api/web/tracks/upload', true
					xhr.setRequestHeader 'X-Token', pfm.token
					xhr.send formData
])