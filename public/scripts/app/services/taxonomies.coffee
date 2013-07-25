angular.module('ponyfm').factory('taxonomies', [
	'$rootScope'
	($rootScope) ->
		def = null

		self =
			trackTypes: [],
			licenses: []
			genres: []
			refresh: () ->
				return def if def != null

				def = new $.Deferred()
				$.getJSON('/api/web/taxonomies/all')
					.done (taxonomies) -> $rootScope.$apply ->
						self.trackTypes.push t for t in taxonomies.track_types
						self.licenses.push t for t in taxonomies.licenses
						self.genres.push t for t in taxonomies.genres
						def.resolve self
				def

		self
])