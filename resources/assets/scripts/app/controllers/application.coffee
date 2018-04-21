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

module.exports = angular.module('ponyfm').controller "application", [
    '$scope', 'auth', '$location', 'upload', '$state', '$stateParams', '$injector', '$rootScope', 'playlists', 'notifications'
    ($scope, auth, $location, upload, $state, $stateParams, $injector, $rootScope, playlists, notifications) ->
        $scope.auth = auth.data
        $scope.$state = $state
        $scope.$stateParams = $stateParams
        $scope.isPinnedPlaylistSelected = false
        $scope.notifActive = false
        $scope.nCount = 0
        $scope.nCountFormatted = '0'
        $loadingElement = null
        loadingStateName = null

        if 'serviceWorker' of navigator
            console.log 'Service Worker is supported'
            navigator.serviceWorker.register('service-worker.js').then((reg) ->
                console.log 'SW registered', reg
            ).catch (err) ->
                console.log 'SW register failed', err
                notifications.serviceWorkerSupported = false

        $scope.menuToggle = () ->
            $rootScope.$broadcast('sidebarToggled')

            if $scope.notifActive
                notifications.markAllAsRead()

            $scope.notifActive = false
        
        $scope.notifPulloutToggle = () ->
            $scope.notifActive = !$scope.notifActive

            if !$scope.notifActive
               notifications.markAllAsRead()

        $rootScope.$on 'notificationsUpdated', () ->
            $scope.nCount = notifications.getUnreadCount()
            if $scope.nCount > 99
                $scope.nCountFormatted = '99+'
            else
                $scope.nCountFormatted = $scope.nCount

        if window.pfm.error
            $state.transitionTo 'errors-' + window.pfm.error

        $rootScope.safeApply = (fn) ->
            phase = $rootScope.$$phase
            if (phase == '$apply' || phase == 'digest')
                fn()
                return

            $rootScope.$apply fn

        $scope.logout = () ->
            auth.logout().done -> location.reload()

        $scope.isActive = (loc) -> $location.path() == loc
        $scope.$on '$viewContentLoaded', () ->
            window.setTimeout (-> window.handleResize()), 0

            if $loadingElement
                $loadingElement.removeClass 'loading'
                $loadingElement = null

        $scope.stateIncludes = (state) ->
            if $loadingElement
                newParts = state.split '.'
                oldParts = loadingStateName.split '.'
                for i in [0..newParts.length]
                    continue if !newParts[i]
                    return false if newParts[i] != oldParts[i]

                return true
            else
                $state.includes(state)

        statesPreloaded = {}
        $scope.$on '$stateChangeStart', (e, newState, newParams, oldState, oldParams) ->
            $rootScope.$broadcast('sidebarHide')
            if $scope.notifActive
                notifications.markAllAsRead()
            $scope.notifActive = false
            $scope.isPinnedPlaylistSelected = false

            if newState.name == 'content.playlist'
                $scope.isPinnedPlaylistSelected = playlists.isPlaylistPinned newParams.id

            return if !oldState || !newState.controller

            preloader = window.pfm.preloaders[newState.controller]
            return if !preloader

            if statesPreloaded[newState]
                delete statesPreloaded[newState]
                return

            e.preventDefault()
            loadingStateName = newState.name

            selector = ''
            newParts = newState.name.split '.'
            oldParts = oldState.name.split '.'
            zipped = _.zip(newParts, oldParts)
            for i in [0..zipped.length]
                break if !zipped[i] || zipped[i][0] != zipped[i][1]
                selector += ' ui-view '

            selector += ' ui-view ' if newState.name != oldState.name

            $loadingElement = $ selector
            $loadingElement.addClass 'loading'

            stateToInject = angular.copy newState
            stateToInject.params = newParams
            try
                $injector.invoke(preloader, null, {$state: stateToInject}).then ->
                    statesPreloaded[newState] = true
                    $state.transitionTo newState, newParams, {location: 'replace'}
            catch error
                $state.transitionTo newState, newParams
]
