angular.module('ponyfm').factory('favourites', [
	'$rootScope', '$http'
	($rootScope, $http) ->
		self =
			toggle: (type, id) ->
				def = new $.Deferred()
				$http.post('/api/web/favourites/toggle', {type: type, id: id, _token: pfm.token}).success (res) ->
					def.resolve res

				def.promise()

		self
])