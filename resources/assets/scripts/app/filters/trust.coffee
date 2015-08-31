angular.module('ponyfm').filter 'trust', [
	'$sce'
	($sce) ->
		(input) ->
			console.log input
			$sce.trustAsHtml input
]