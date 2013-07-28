angular.module('ponyfm').directive 'pfmImageUpload',
	restrict: 'E'
	scope:
		setUploadedImage: '&'
		setGalleryImage: '&'
	controller: [
		'upload'
		(upload) -> (scope) ->
		]