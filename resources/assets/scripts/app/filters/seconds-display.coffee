# Pony.fm - A community for pony fan music.
# Copyright (C) 2015 Peter Deltchev
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

angular.module('ponyfm').filter 'secondsDisplay', () ->
    (input) ->
        sec_num = parseInt(input, 10)
        return '00:00' if !sec_num

        hours   = Math.floor(sec_num / 3600)
        minutes = Math.floor((sec_num - (hours * 3600)) / 60)
        seconds = sec_num - (hours * 3600) - (minutes * 60)

        if (hours < 10)
            hours = "0" + hours
        if (minutes < 10)
            minutes = "0" + minutes
        if (seconds < 10)
            seconds = "0" + seconds

        time = ''
        time += hours + ':' if hours != "00"
        time += minutes + ':' + seconds;
        return time;
