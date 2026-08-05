# Pony.fm - A community for pony fan music.
# Copyright (C) 2015 Feld0
#
# This program is free software: you can redistribute it and/or modify
# it under the terms of the GNU Affero General Public License as published by
# the Free Software Foundation, either version 3 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU Affero General Public License for more details.
#
# You should have received a copy of the GNU Affero General Public License
# along with this program.  If not, see <http://www.gnu.org/licenses/>.


require 'script!../base/underscore'
require './jquery-extensions'
require './layout.coffee'
require 'script!./underscore-extensions'

def = new $.Deferred()

pfm.soundManager = def.promise()

soundManager.setup
	url: '/flash/soundmanager/'
	flashVersion: 9
	onready: () ->
		def.resolve()
