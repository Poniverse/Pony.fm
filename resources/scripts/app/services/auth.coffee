# Pony.fm - A community for pony fan music.
# Copyright (C) 2015 Feld0
#
# This program is free software: you can redistribute it and/or modify
# it under the terms of the GNU Affero General Public License as published by
# the Free Software Foundation, either version 3 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU Affero General Public License for more details.
#
# You should have received a copy of the GNU Affero General Public License
# along with this program.  If not, see <http://www.gnu.org/licenses/>.

module.exports = angular.module('ponyfm').factory('auth', [
    '$rootScope'
    ($rootScope) ->
        data: {isLogged: window.pfm.auth.isLogged, user: window.pfm.auth.user, isAdmin: (window.pfm.auth.isLogged && window.pfm.auth.user.roles[0] && window.pfm.auth.user.roles[0].id == 2)}
        login: (email, password, remember) ->
            def = new $.Deferred()
            $.post('/api/web/auth/login', {email: email, password: password, remember: remember})
                .done ->
                    $rootScope.$apply -> def.resolve()

                .fail (res) ->
                    $rootScope.$apply -> def.reject res.responseJSON.messages

            def.promise()

        logout: -> $.post('/api/web/auth/logout')

        # Updates the pfm.auth.user object's values with the current server-side ones.
        refresh: ->
            def = new $.Deferred()
            $.get("/api/web/users/#{window.pfm.auth.user.id}")
                .done (data) ->
                    _.extend(window.pfm.auth.user, data.user)
                    $rootScope.$apply -> def.resolve()

                .fail (response) ->
                    $rootScope.$apply -> def.reject response.responseJSON

            def.promise()
])
