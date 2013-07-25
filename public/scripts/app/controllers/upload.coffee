angular.module('ponyfm').controller "upload", [
	'$scope', 'auth', 'upload', '$state'
	($scope, auth, upload, $state) ->
		$scope.$on 'upload-queue-started', () ->
			$scope.state = 'uploading'
			$scope.uploadDialogOpen = true
			$scope.uploads = {}
			$scope.progress = 0
			$scope.uploadedFiles = 0
			$scope.totalFiles = 0

		$scope.$on 'upload-added', (e, upload) ->
			$scope.uploads[upload.index] = upload
			$scope.totalFiles++

		$scope.$on 'upload-queue-ended', () ->
			$scope.state = 'finished'
			$scope.uploadDialogOpen = false if _.each upload.queue, (u) -> u.error == null
			$state.transitionTo 'account-content.tracks'
			$scope.uploadDialogOpen = false if !(_.size $scope.uploads)

		$scope.$on 'upload-finished', (e, upload) ->
			$scope.uploadedFiles++
			delete $scope.uploads[upload.index] if upload.success

		$scope.$on 'upload-progress', () ->
			$scope.progress = upload.totalBytesUploaded / upload.totalBytes * 100
			$scope.state = 'processing' if $scope.progress >= 100

		$scope.close = () ->
			$scope.uploadDialogOpen = false
]