window.pfm.preloaders['favourites-albums'] = [
    'favourites'
    (favourites) ->
        favourites.fetchAlbums(true)
]

angular.module('ponyfm').controller "favourites-albums", [
    '$scope', 'favourites'
    ($scope, favourites) ->
        favourites.fetchAlbums().done (res) ->
            $scope.albums = res.albums
]
