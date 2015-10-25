window.pfm.preloaders['artist'] = [
    'artists', '$state'
    (artists, $state) ->
        artists.fetch $state.params.slug, true
]

angular.module('ponyfm').controller "artist", [
    '$scope', 'artists', '$state', 'follow'
    ($scope, artists, $state, follow) ->
        artists.fetch($state.params.slug)
            .done (artistResponse) ->
                $scope.artist = artistResponse.artist

        $scope.toggleFollow = () ->
            follow.toggle('artist', $scope.artist.id).then (res) ->
                $scope.artist.user_data.is_following = res.is_followed
]
