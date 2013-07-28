angular.module('ponyfm').filter 'momentFromNow', () ->
	(input) ->
		moment(input).fromNow()