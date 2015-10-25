angular.module('ponyfm').factory('dashboard', [
    '$rootScope', '$http'
    ($rootScope, $http) ->
        def = null

        self =
            refresh: (force) ->
                force = force || false
                return def if !force && def
                def = new $.Deferred()
                $http.get('/api/web/dashboard').success (dashboardContent) ->
                    def.resolve(dashboardContent)
                def.promise()

        self
])
