window.pfm.preloaders['account-tracks'] = [
	'account-tracks', 'account-albums', 'taxonomies'
	(tracks, albums, taxonomies) ->
		$.when.all [tracks.refresh(null, true), albums.refresh(true), taxonomies.refresh()]
]

angular.module('ponyfm').controller "account-tracks", [
	'$scope', '$state', 'taxonomies', '$dialog', 'lightbox', 'account-albums', 'account-tracks'
	($scope, $state, taxonomies, $dialog, lightbox, albums, tracks) ->
		$scope.data =
			selectedTrack: null

		$scope.tracks = []

		tracksDb = {}

		setTracks = (tracks) ->
			$scope.tracks.length = 0
			tracksDb = {}
			for track in tracks
				tracksDb[track.id] = track
				$scope.tracks.push track

			if $state.params.track_id
				$scope.data.selectedTrack = tracksDb[$state.params.track_id]

		tracks.refresh().done setTracks

		$scope.filters =
			published: [
				{title: 'Either', query: ''},
				{title: 'Yes', query: 'published=1'},
				{title: 'No', query: 'published=0'}]

			sort: [
				{title: 'Newest to Oldest', query: 'order=created_at,desc'},
				{title: 'Oldest to Newest', query: 'order=created_at,asc'}]

			genres: {}
			trackTypes: {}

		$scope.filter =
			published: $scope.filters.published[0]
			sort: $scope.filters.sort[0]
			genres: {}
			trackTypes: {}

		$scope.titles =
			genres: 'All'
			trackTypes: 'All'

		for genre in taxonomies.genres
			$scope.filters.genres[genre.id] =
				id: genre.id
				title: genre.name
				query: 'genres[]=' + genre.id

		for type in taxonomies.trackTypes
			$scope.filters.trackTypes[type.id] =
				id: type.id
				title: type.title
				query: 'types[]=' + type.id

		$scope.updateFilter = (type, filter) ->
			$scope.filter[type] = filter
			$scope.refreshList()

		$scope.toggleFilter = (type, id) ->
			if !$scope.filter[type][id]
				$scope.filter[type][id] = $scope.filters[type][id]
			else
				delete $scope.filter[type][id]

			length = _.keys($scope.filter[type]).length
			if length == 1
				$scope.titles[type] = _.map($scope.filter[type], (f) -> f.title).join ', '
			else if length > 1
				$scope.titles[type] = length + ' selected'
			else
				$scope.titles[type] = 'All'

			$scope.refreshList()

		$scope.refreshList = () ->
			parts = [$scope.filter.sort.query, $scope.filter.published.query]
			_.each $scope.filter.genres, (g) -> parts.push g.query
			_.each $scope.filter.trackTypes, (g) -> parts.push g.query
			query = parts.join '&'
			tracks.refresh(query).done setTracks

		$scope.selectTrack = (track) ->
			$scope.data.selectedTrack = track

		$scope.$on '$stateChangeSuccess', () ->
			if $state.params.track_id
				$scope.selectTrack tracksDb[$state.params.track_id]
			else
				$scope.selectTrack null

		$scope.$on 'track-deleted', () ->
			tracks.clearCache()
			$scope.refreshList()
]