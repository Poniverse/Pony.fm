angular.module('ponyfm').controller "application", [
	'$scope', 'auth', '$location', 'upload', '$state', '$stateParams', '$injector'
	($scope, auth, $location, upload, $state, $stateParams, $injector) ->
		$scope.auth = auth.data
		$scope.$state = $state
		$scope.$stateParams = $stateParams
		$loadingElement = null

		$scope.logout = () ->
			auth.logout().done -> location.reload()

		$scope.isActive = (loc) -> $location.path() == loc
		$scope.$on '$viewContentLoaded', () ->
			window.handleResize()

			if $loadingElement
				$loadingElement.removeClass 'loading'
				$loadingElement = null

		statesPreloaded = {}
		$scope.$on '$stateChangeStart', (e, newState, newParams, oldState) ->
			return if !oldState || !newState.controller

			preloader = window.pfm.preloaders[newState.controller]
			return if !preloader

			if statesPreloaded[newState]
				delete statesPreloaded[newState]
				return

			e.preventDefault()

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
			$injector.invoke(preloader, null, {$state: stateToInject}).then ->
				statesPreloaded[newState] = true
				$state.transitionTo newState, newParams
]