angular.module('ponyfm').factory('taxonomies', [
	'$rootScope', '$http'
	($rootScope, $http) ->
		def = null

		self =
			trackTypes: []
			licenses: []
			genres: []
			showSongs: []
			refresh: () ->
				return def.promise() if def != null

				def = new $.Deferred()
				$http.get('/api/web/taxonomies/all')
					.success (taxonomies) ->
						self.trackTypes.push t for t in taxonomies.track_types
						self.licenses.push t for t in taxonomies.licenses
						self.genres.push t for t in taxonomies.genres
						self.showSongs.push t for t in taxonomies.show_songs
						def.resolve self

				def.promise()

		self
])