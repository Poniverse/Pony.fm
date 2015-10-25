angular.module('ponyfm').factory('auth', [
    '$rootScope'
    ($rootScope) ->
        data: {isLogged: window.pfm.auth.isLogged, user: window.pfm.auth.user}
        login: (email, password, remember) ->
            def = new $.Deferred()
            $.post('/api/web/auth/login', {email: email, password: password, remember: remember, _token: pfm.token})
                .done ->
                    $rootScope.$apply -> def.resolve()

                .fail (res) ->
                    $rootScope.$apply -> def.reject res.responseJSON.messages

            def.promise()

        logout: -> $.post('/api/web/auth/logout', {_token: pfm.token})
])

