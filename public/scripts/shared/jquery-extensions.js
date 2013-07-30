if (jQuery.when.all===undefined) {
	jQuery.when.all = function(deferreds) {
		var deferred = new jQuery.Deferred();
		$.when.apply(jQuery, deferreds).then(
			function() {
				deferred.resolve(Array.prototype.slice.call(arguments));
			},
			function() {
				deferred.fail(Array.prototype.slice.call(arguments));
			});

		return deferred;
	}
}