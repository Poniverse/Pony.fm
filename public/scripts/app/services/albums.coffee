angular.module('ponyfm').factory('albums', [
	'$rootScope', '$http'
	($rootScope, $http) ->
		albumPages = []
		albums = {}

		self =
			filters: {}

			fetchList: (page, force) ->
				force = force || false
				page = 1 if !page
				return albumPages[page] if !force && albumPages[page]
				albumsDef = new $.Deferred()
				$http.get('/api/web/albums?page=' + page).success (albums) ->
					albumsDef.resolve albums
					$rootScope.$broadcast 'albums-feteched', albums

				albumPages[page] = albumsDef.promise()

			fetch: (id, force) ->
				force = force || false
				id = 1 if !id
				return albums[id] if !force && albums[id]
				albumsDef = new $.Deferred()
				$http.get('/api/web/albums/' + id).success (albums) ->
					albumsDef.resolve albums

				albums[id] = albumsDef.promise()

		self
])