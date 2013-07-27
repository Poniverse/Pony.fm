angular.module('ponyfm').factory('lightbox', [
	() ->
		openDataUrl: (src) ->
			$.colorbox
				html: '<img src="' + src + '" />'
				transition: 'none'
])

