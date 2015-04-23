angular.module('ponyfm').directive 'pfmProgressBar', () ->
	(scope, element, attrs) ->
		scope.$watch attrs.pfmProgressBar, (val) ->
			return if !val?
			$(element).css 'width', val + '%'