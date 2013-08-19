angular.module('ponyfm').filter 'trust', [
	'$sce'
	($sce) ->
		(input) ->
			$sce.trustAsHtml input
]