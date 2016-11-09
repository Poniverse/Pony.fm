# Pony.fm - A community for pony fan music.
# Copyright (C) 2016 Peter Deltchev
#
# This program is free software: you can redistribute it and/or modify
# it under the terms of the GNU Affero General Public License as published by
# the Free Software Foundation, either version 3 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU Affero General Public License for more details.
#
# You should have received a copy of the GNU Affero General Public License
# along with this program.  If not, see <http://www.gnu.org/licenses/>.

window.pfm.preloaders['track-show'] = [
  'tracks', '$state', 'playlists'
  (tracks, $state, playlists) ->
    $.when.all [tracks.fetch $state.params.id, playlists.refreshOwned(true)]
]

module.exports = angular.module('ponyfm').controller "track-show", [
  '$scope', 'tracks', '$state', 'playlists', 'auth', 'favourites', '$modal'
  ($scope, tracks, $state, playlists, auth, favourites, $modal) ->
      $scope.formatPublishedDate = (track) ->
          locale = window.navigator.userLanguage || window.navigator.language
          moment.locale(locale)
          return moment(track.published_at).format('LLL')
]
