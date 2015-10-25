angular.module('ponyfm').directive 'pfmPlaylistsList', () ->
    restrict: 'E'
    replace: true
    templateUrl: '/templates/directives/playlists-list.html'
    scope:
        playlists: '=playlists',
        class: '@class'

    controller: [
        '$scope', 'auth'
        ($scope, auth) ->
            $scope.auth = auth.data
    ]
