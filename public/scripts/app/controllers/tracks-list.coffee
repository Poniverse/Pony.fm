window.pfm.preloaders['tracks-list'] = [
	'tracks', '$state'
	(tracks, $state) ->
		tracks.loadFilters().then(->
			if !tracks.mainQuery.hasLoadedFilters
				tracks.mainQuery.fromFilterString($state.params.filter)
			if $state.params.page
				tracks.mainQuery.setPage $state.params.page

			tracks.mainQuery.fetch()
		)
]

angular.module('ponyfm').controller "tracks-list", [
	'$scope', 'tracks', '$state',
	($scope, tracks, $state) ->
		tracks.mainQuery.fetch().done (searchResults) ->
			$scope.tracks = searchResults.tracks
]