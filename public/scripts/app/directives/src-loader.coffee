angular.module('ponyfm').directive 'pfmSrcLoader', () ->
	(scope, element, attrs) ->
		size = attrs.pfmSrcSize || 'normal'
		url = attrs.pfmSrcLoader

		element.attr 'src', '/images/icons/loading_' + size + '.png'

		image = element.clone()
		image.removeAttr 'pfm-src-loader'
		image.removeAttr 'pfm-src-size'

		image[0].onload = ->
			element.replaceWith image
			image.css {opacity: 0}
			image.animate {opacity: 1}, 250

		image[0].src = url