angular.module('ponyfm').directive 'pfmScrollRecorder', () ->
    (scope, element, attrs) ->
        timeout = null
        onScroll = null
        lastInView = null

        element.scroll (e) ->
            (window.clearTimeout timeout) if timeout
            timeout = window.setTimeout (-> onScroll e), 500

        onScroll = (e) -> scope.safeApply ->
            items = element.find 'li:not(.empty)'
            itemHeight = (items.eq 0).height()
            itemsArray = items.get()

            elementViewTop = element.offset().top
            elementViewBottom = elementViewTop + element.height()

            for i in [itemsArray.length - 1..0]
                listItem = $ itemsArray[i]

                listItemTop = listItem.offset().top + itemHeight
                isInView = listItemTop > elementViewTop && listItemTop < elementViewBottom
                if isInView
                    lastInView = listItem
                    break

            scope.$emit 'element-in-view', angular.element(lastInView).scope()
