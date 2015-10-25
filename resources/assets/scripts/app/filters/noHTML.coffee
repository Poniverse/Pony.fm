angular.module('ponyfm').filter 'noHTML', () ->
    (input) ->
        return '' if !input

        input.replace(/&/g, '&amp;')
            .replace(/>/g, '&gt;')
            .replace(/</g, '&lt;')
