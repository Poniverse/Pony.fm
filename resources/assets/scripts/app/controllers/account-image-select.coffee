angular.module('ponyfm').controller "account-image-select", [
    '$scope'
    ($scope) ->
        $scope.images = []
        $scope.isLoading = true

        $.getJSON('/api/web/images/owned').done (images) -> $scope.$apply ->
            $scope.images = images
            $scope.isLoading = false
]
