angular.module('ponyfm').filter 'noHTML', () ->
	(input) ->
		input.replace(/&/g, '&amp;')
			.replace(/>/g, '&gt;')
			.replace(/</g, '&lt;')