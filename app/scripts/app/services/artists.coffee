angular.module('ponyfm').factory('artists', [
	'$rootScope', '$http'
	($rootScope, $http) ->
		artistPage = []
		artists = {}
		artistContent = {}
		artistFavourites = {}

		self =
			filters: {}

			fetchList: (page, force) ->
				force = force || false
				page = 1 if !page
				return artistPage[page] if !force && artistPage[page]
				artistsDef = new $.Deferred()
				$http.get('/api/web/artists?page=' + page).success (albums) ->
					artistsDef.resolve albums
					$rootScope.$broadcast 'artists-feteched', albums

				artistPage[page] = artistsDef.promise()

			fetch: (slug, force) ->
				force = force || false
				slug = 1 if !slug
				return artists[slug] if !force && artists[slug]
				artistsDef = new $.Deferred()
				$http.get('/api/web/artists/' + slug)
					.success (albums) ->
						artistsDef.resolve albums
					.catch () ->
						artistsDef.reject()

				artists[slug] = artistsDef.promise()

			fetchContent: (slug, force) ->
				force = force || false
				slug = 1 if !slug
				return artistContent[slug] if !force && artistContent[slug]
				artistsDef = new $.Deferred()
				$http.get('/api/web/artists/' + slug + '/content')
					.success (albums) ->
						artistsDef.resolve albums
					.catch () ->
						artistsDef.reject()

				artistContent[slug] = artistsDef.promise()

			fetchFavourites: (slug, force) ->
				force = force || false
				slug = 1 if !slug
				return artistFavourites[slug] if !force && artistFavourites[slug]
				artistsDef = new $.Deferred()
				$http.get('/api/web/artists/' + slug + '/favourites').success (albums) ->
					artistsDef.resolve albums

				artistFavourites[slug] = artistsDef.promise()

		self
])