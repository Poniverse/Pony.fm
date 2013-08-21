$profiler = $("<div class='profiler' />").appendTo document.body
$toolbar = $('<div class="buttons" />').appendTo $profiler

$('<a href="#" class="open-button"><i class="icon-chevron-down"></i></a>')
		.click (e) ->
			e.preventDefault()
			$(document.body).toggleClass 'profiler-open'
	.appendTo $toolbar

$('<a href="#" class="clear-button">Clear</a>')
		.click (e) ->
			e.preventDefault()
			$profiler.find('.requests').empty()
	.appendTo $toolbar

$requestItems = $("<ul class='requests'>").appendTo $profiler

appendRequest = (method, url, req) ->
	$requestItem = $("<li />")
	$requestHeader = ($("<h3 />")).appendTo $requestItem
	($("<span class='method' />").text method).appendTo $requestHeader
	($("<span class='url' />").text url).appendTo $requestHeader

	$logItems = $("<ul />").appendTo $requestItem

	for logItem in req.request.log
		$liItem = $("<li>")

		$("<h4 class='log-" + logItem.level + "' />")
			.html(logItem.message)
			.click () ->
				$(this).toggleClass 'open'
			.appendTo($liItem)

		$("<div class='clear' />").appendTo $liItem
		$liItem.appendTo $logItems

	for query in req.request.queries
		$liItem = $("<li>")
		($("<span class='time' />").text query.time).appendTo $liItem

		$("<h4 class='prettyprint' />")
			.html(prettyPrintOne(query.query, 'lang-sql'))
			.click () ->
				$(this).toggleClass 'open'
			.appendTo($liItem)

		$("<div class='clear' />").appendTo $liItem
		$liItem.appendTo $logItems

	$requestItem.appendTo $requestItems
	$requestItems.animate {scrollTop: $requestItems[0].scrollHeight}, 300

oldOpen = XMLHttpRequest.prototype.open

XMLHttpRequest.prototype.open = (method, url) ->
	intercept = =>
		return if this.readyState != 4
		return if !this.getResponseHeader('X-Request-Id')
		id = this.getResponseHeader('X-Request-Id')

		$.getJSON('/api/web/profiler/' + id).done (res) -> appendRequest method, url, res

	(this.addEventListener "readystatechange", intercept, false) if url.indexOf('/api/web/profiler/') == -1
	oldOpen.apply this, arguments