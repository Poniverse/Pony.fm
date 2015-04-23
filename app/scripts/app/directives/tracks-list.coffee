angular.module('ponyfm').directive 'pfmTracksList', () ->
	restrict: 'E'
	templateUrl: '/templates/directives/tracks-list.html'
	replace: true
	scope:
		tracks: '=tracks',
		class: '@class'

	controller: [
		'$scope', 'favourites', 'player', 'auth'
		($scope, favourites, player, auth) ->
			$scope.auth = auth.data

			$scope.toggleFavourite = (track) ->
				favourites.toggle('track', track.id).done (res) ->
					track.user_data.is_favourited = res.is_favourited

			$scope.play = (track) ->
				index = _.indexOf $scope.tracks, (t) -> t.id == track.id
				player.playTracks $scope.tracks, index
	]