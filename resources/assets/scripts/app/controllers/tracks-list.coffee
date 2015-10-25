window.pfm.preloaders['tracks-list'] = [
    'tracks', '$state'
    (tracks, $state) ->
        tracks.loadFilters().then(->
            tracks.mainQuery.fromFilterString($state.params.filter)
            tracks.mainQuery.setPage $state.params.page || 1

            tracks.mainQuery.fetch()
        )
]

angular.module('ponyfm').controller "tracks-list", [
    '$scope', 'tracks', '$state',
    ($scope, tracks, $state) ->
        tracks.mainQuery.fetch().done (searchResults) ->
            $scope.tracks = searchResults.tracks
]
