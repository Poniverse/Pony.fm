def = new $.Deferred()

pfm.soundManager = def.promise()

soundManager.setup
	url: '/flash/soundmanager/'
	flashVersion: 9
	onready: () ->
		def.resolve()
