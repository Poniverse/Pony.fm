window.pfm.preloaders['artist-content'] = [
    'artists', '$state'
    (artists, $state) ->
        $.when.all [artists.fetch($state.params.slug), artists.fetchContent($state.params.slug, true)]
]

angular.module('ponyfm').controller "artist-content", [
    '$scope', 'artists', '$state'
    ($scope, artists, $state) ->
        artists.fetchContent($state.params.slug)
            .done (artistResponse) ->
                $scope.content = artistResponse
]
