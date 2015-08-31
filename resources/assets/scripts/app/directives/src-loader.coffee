angular.module('ponyfm').directive 'pfmSrcLoader', () ->
	(scope, element, attrs) ->
		size = attrs.pfmSrcSize || 'normal'
		element.css {opacity: .5}

		update = (val) ->
			element.attr 'src', '/images/icons/loading_' + size + '.png'

			image = element.clone()
			image.removeAttr 'pfm-src-loader'
			image.removeAttr 'pfm-src-size'

			image[0].onload = ->
				element.attr 'src', val
				element.css {opacity: 0}
				element.animate {opacity: 1}, 250

			image[0].src = val

		update scope.$eval attrs.pfmSrcLoader

		scope.$watch attrs.pfmSrcLoader, update