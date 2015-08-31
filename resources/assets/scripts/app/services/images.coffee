angular.module('ponyfm').factory('images', [
	'$rootScope'
	($rootScope) ->
		def = null
		self =
			images: []
			isLoading: true
			refresh: (force) ->
				return def if !force && def
				def = new $.Deferred()

				self.images = []
				self.isLoading = true

				$.getJSON('/api/web/images/owned').done (images) -> $rootScope.$apply ->
					self.images = images
					self.isLoading = false
					def.resolve images

				return def

		self.refresh()
		return self
])

