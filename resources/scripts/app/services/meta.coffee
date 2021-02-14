# Pony.fm - A community for pony fan music.
# Copyright (C) 2016 Feld0
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

module.exports = angular.module('ponyfm').factory('meta', [
    '$rootScope'
    ($rootScope) ->
        self =
            reset: () ->
                this.setTitle('Pony.fm - Unlimited Pony Music Hosting', false)
                this.setDescription('Pony.fm is the world\'s largest collection of My Little Pony fan music. Enjoy unlimited uploads, streaming, and downloads!')

            setTitle: (title, withSuffix=true) ->
                $rootScope.title = title
                if withSuffix
                    $rootScope.title += ' - Pony.fm'

            setDescription: (description) ->
                $rootScope.description = description
])
