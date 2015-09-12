angular.module('ponyfm').factory('account-tracks', [
	'$rootScope', '$http'
	($rootScope, $http) ->
		cache = {}

		self =
			clearCache: () -> cache = {}

			getEdit: (id, force) ->
				url = '/api/web/tracks/edit/' + id
				force = force || false
				return cache[url] if !force && cache[url]

				def = new $.Deferred()
				cache[url] = def
				$http.get(url).success (track) -> def.resolve track
				def.promise()

			refresh: (query, force) ->
				query = query || 'created_at,desc'
				url = '/api/web/tracks/owned?' + query
				force = force || false
				return cache[url] if !force && cache[url]

				def = new $.Deferred()
				cache[url] = def
				$http.get(url).success (tracks) -> def.resolve tracks
				def.promise()

		self
])