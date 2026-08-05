# Pony.fm - A community for pony fan music.
# Copyright (C) 2016 Feld0
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

pfmUserCreatorController = [
    'artists',
    (artists)->
        ctrl = this

        ctrl.usernameToCreate = ''
        ctrl.isCreating = false
        ctrl.creationMessage = null
        ctrl.creationSucceeded = false
        ctrl.user = null

        ctrl.createUser = ->
            ctrl.isCreating = true
            ctrl.creationMessage = null
            ctrl.creationSucceeded = false

            artists.create(ctrl.usernameToCreate)
                .then (response)->
                    ctrl.creationSucceeded = true
                    ctrl.user = response.data.user
                    ctrl.creationMessage = response.data.message
                .catch (response)->
                    ctrl.creationSucceeded = false
                    ctrl.user = response.data.errors.user
                    ctrl.creationMessage = response.data.errors.message
                .finally ->
                    ctrl.isCreating = false

        return ctrl
]

module.exports = angular.module('ponyfm').component('pfmUserCreator', {
    templateUrl: '/templates/directives/user-creator.html',
    controller: pfmUserCreatorController,
})
