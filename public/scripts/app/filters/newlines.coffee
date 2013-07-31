angular.module('ponyfm').filter 'newlines', () ->
	(input) -> input.replace(/\n/g, '<br/>')