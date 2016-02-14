# Pony.fm - A community for pony fan music.
# Copyright (C) 2015 Peter Deltchev
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

module.exports = angular.module('ponyfm').factory('comments', [
    '$rootScope', '$http'
    ($rootScope, $http) ->
        commentCache = []

        self =
            filters: {}

            addComment: (resourceType, resourceId, content) ->
                commentDef = new $.Deferred()
                $http.post('/api/web/comments/' + resourceType + '/' + resourceId, {content: content}).success (comment) ->
                    commentDef.resolve comment

                commentDef.promise()

            fetchList: (resourceType, resourceId, force) ->
                key = resourceType + '-' + resourceId
                force = force || false
                return commentCache[key] if !force && commentCache[key]
                commentDef = new $.Deferred()
                $http.get('/api/web/comments/' + resourceType + '/' + resourceId).success (comments) ->
                    commentDef.resolve comments

                commentCache[key] = commentDef.promise()

        self
])
