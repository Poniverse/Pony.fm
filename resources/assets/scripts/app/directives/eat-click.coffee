angular.module('ponyfm').directive 'pfmEatClick', () ->
    (scope, element) ->
        $(element).click (e) ->
            e.preventDefault()
