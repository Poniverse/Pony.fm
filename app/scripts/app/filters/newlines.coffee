angular.module('ponyfm').filter 'newlines', () ->
	(input) ->
		return '' if !input
		input.replace(/\n/g, '<br/>')