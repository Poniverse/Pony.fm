angular.module('ponyfm').factory('account-albums', [
	'$rootScope', '$http'
	($rootScope, $http) ->
		def = null
		albums = []

		self =
			getEdit: (id, force) ->
				url = '/api/web/albums/edit/' + id
				force = force || false
				return albums[id] if !force && albums[id]

				editDef = new $.Deferred()
				albums[id] = editDef
				$http.get(url).success (album) -> editDef.resolve album
				editDef.promise()

			refresh: (force) ->
				force = force || false
				return def if !force && def
				def = new $.Deferred()
				$http.get('/api/web/albums/owned').success (ownedAlbums) ->
					def.resolve(ownedAlbums)
				def.promise()

		self
])