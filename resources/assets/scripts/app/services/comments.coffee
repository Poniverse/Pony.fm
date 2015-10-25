angular.module('ponyfm').factory('comments', [
    '$rootScope', '$http'
    ($rootScope, $http) ->
        commentCache = []

        self =
            filters: {}

            addComment: (resourceType, resourceId, content) ->
                commentDef = new $.Deferred()
                $http.post('/api/web/comments/' + resourceType + '/' + resourceId, {content: content, _token: pfm.token}).success (comment) ->
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
