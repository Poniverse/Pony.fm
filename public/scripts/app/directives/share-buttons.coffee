angular.module('ponyfm').directive 'pfmShareButtons', () ->
	(scope, element) ->
		window.setTimeout((->
			Tumblr.activate_share_on_tumblr_buttons()
			FB.XFBML.parse(null, -> element.addClass('loaded'))
		), 0)