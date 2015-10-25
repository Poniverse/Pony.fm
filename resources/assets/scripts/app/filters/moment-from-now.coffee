angular.module('ponyfm').filter 'momentFromNow', () ->
    (input) ->
        moment.utc(input).fromNow()
