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

module.exports = angular.module('ponyfm').factory('player', [
    '$rootScope', '$http', '$modal'
    ($rootScope, $http, $modal) ->
        readyDef = new $.Deferred()

        play = (track) ->
            self.currentTrack = track
            $rootScope.$broadcast 'player-starting-track', track

            streams = []
            streams.push track.streams.mp3
            streams.push track.streams.ogg if track.streams.ogg
            streams.push track.streams.aac if track.streams.aac

            track.progress = 0
            track.progressSeconds = 0
            track.loadingProgress = 0

            self.currentSound = soundManager.createSound
                url: streams,
                volume: self.volume

                whileloading: () -> $rootScope.safeApply ->
                    track.loadingProgress = (self.currentSound.bytesLoaded / self.currentSound.bytesTotal) * 100

                whileplaying: () -> $rootScope.safeApply ->
                    track.progressSeconds = self.currentSound.position / 1000
                    track.progress = (self.currentSound.position / (track.duration * 1000)) * 100

                onload: (res) -> $rootScope.safeApply ->
                    if !res
                        # Track failed to load
                        dialog = $modal
                            templateUrl: '/templates/partials/track-load-fail-dialog.html',
                            show: true

                onfinish: () -> $rootScope.safeApply ->
                    if self.repeatState == 2
                        # Track repeat
                        # Playlist repeat is handled
                        # in self.playNext()
                        self.currentSound.play()
                    else
                        track.isPlaying = false
                        self.playNext()

                onstop: () -> $rootScope.safeApply ->
                    track.isPlaying = false
                    self.isPlaying = false

                onplay: () -> $rootScope.safeApply ->
                    track.isPlaying = true

                onresume: () -> $rootScope.safeApply ->
                    track.isPlaying = true

                onpause: () -> $rootScope.safeApply ->
                    track.isPlaying = false

            track.isPlaying = true
            self.isPlaying = true
            self.currentSound.play()

        updateCanGo = () ->
            self.canGoNext = self.playlistIndex < self.playlist.length - 1
            self.canGoPrev = self.playlistIndex > 0

        self =
            ready: false
            isPlaying: false
            currentTrack: null
            currentSound: null
            playlist: []
            playlistIndex: 0
            volume: 0
            readyDef: readyDef.promise()
            canGoPrev: false
            canGoNext: false
            repeatState: 0

            playPause: () ->
                return if !self.ready
                return if !self.isPlaying

                if self.currentSound.paused
                    self.currentSound.play()
                else
                    self.currentSound.pause()

            playNext: () ->
                return if !self.canGoNext && self.repeatState != 1

                self.currentSound.stop() if self.currentSound != null
                self.playlistIndex++
                if self.playlistIndex >= self.playlist.length
                    if self.repeatState != 1
                        self.playlist.length = 0
                        self.currentTrack = null
                        self.currentSong = null
                        self.isPlaying = false
                        return
                    else
                        self.playlistIndex = 0

                play self.playlist[self.playlistIndex]
                updateCanGo()

            playPrev: () ->
                return if !self.canGoPrev

                self.currentSound.stop() if self.currentSound != null
                self.playlistIndex--

                if self.playlistIndex < 0
                    self.playlist.length = 0
                    self.currentTrack = null
                    self.currentSong = null
                    self.isPlaying = false
                    return

                play self.playlist[self.playlistIndex]
                updateCanGo()

            toggleRepeat: () ->
                if self.repeatState >= 2
                    self.repeatState = 0
                else
                    self.repeatState++

            seek: (progress) ->
                return if !self.currentSound
                self.currentSound.setPosition(progress)

            setVolume: (theVolume) ->
                theVolume = 100 if theVolume > 100
                self.currentSound.setVolume(theVolume) if self.currentSound
                $.cookie('pfm-volume', theVolume)
                self.volume = theVolume

            playTracks: (tracks, index) ->
                return if !self.ready
                return if tracks.length == 0

                if tracks[index].isPlaying
                    self.playPause()
                    return

                self.currentSound.stop() if self.currentSound != null

                $rootScope.$broadcast 'player-stopping-playlist'
                self.playlist.length = 0
                self.playlist.push track for track in tracks
                self.playlistIndex = index

                $rootScope.$broadcast 'player-starting-playlist', tracks
                play tracks[index]
                updateCanGo()

        pfm.soundManager.done () ->
            self.ready = true
            self.setVolume($.cookie('pfm-volume') || 100)

            readyDef.resolve()

        codeArray = []
        codeKey = '38,38,40,40,37,39,37,39,66,65'
        $(document).keydown (e) ->
            codeArray.push e.keyCode
            if codeArray.toString().indexOf(codeKey) >= 0
                $http.get('https://pony.fm/api/web/tracks/23453').success (trackResponse) =>
                    toPlay = trackResponse.track
                    self.playTracks [toPlay], 0
                codeArray = []
            return

        self
])
