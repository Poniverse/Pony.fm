angular.module('ponyfm').directive 'pfmTracksList', () ->
	restrict: 'E'
	templateUrl: '/templates/directives/tracks-list.html'
	scope:
		tracks: '=tracks',
		class: '@class'

	controller: [
		'$scope'
		($scope) ->
	]