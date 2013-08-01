angular.module('ponyfm').directive 'pfmAlbumsList', () ->
	restrict: 'E'
	templateUrl: '/templates/directives/albums-list.html'
	scope:
		albums: '=albums',
		class: '@class'

	controller: [
		'$scope'
		($scope) ->
	]