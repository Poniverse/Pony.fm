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

angular.module('ponyfm').factory('upload', [
    '$rootScope', '$http', '$timeout'
    ($rootScope, $http, $timeout) ->
        self =
            queue: []

            finishUploadWrapper: (upload)->
                ()->
                    self.finishUpload(upload)

            # Polls for the upload's status
            finishUpload: (upload) ->
                # TODO: update upload status
                $http.get("/api/web/tracks/#{upload.trackId}/upload-status").then(
                    # handle success or still-processing
                    (response)->
                        if response.status == 202
                            $timeout(self.finishUploadWrapper(upload), 5000)

                        else if response.status == 201
                            upload.isProcessing = false
                            upload.success = true

                    # handle error
                    ,(response)->
                        upload.isProcessing = false
                        if response.headers['content-type'] == 'application/json'
                            upload.error = response.data.error
                        else
                            upload.error = 'There was an unknown error!'
            )



            upload: (files) ->
                _.each files, (file) ->
                    upload =
                        name: file.name
                        progress: 0
                        uploadedSize: 0
                        size: file.size
                        index: self.queue.length
                        isUploading: true
                        isProcessing: false
                        trackId: null
                        success: false
                        error: null

                    self.queue.push upload
                    $rootScope.$broadcast 'upload-added', upload

                    xhr = new XMLHttpRequest()
                    xhr.upload.onprogress = (e) ->
                        $rootScope.$apply ->
                            upload.uploadedSize = e.loaded
                            upload.progress = e.loaded / upload.size * 100
                            $rootScope.$broadcast 'upload-progress', upload

                    # TODO: Implement polling here
                    # event listener
                    xhr.onload = -> $rootScope.$apply ->
                        upload.isUploading = false
                        upload.isProcessing = true

                        if xhr.status == 200
                            # kick off polling
                            upload.trackId = $.parseJSON(xhr.responseText).id
                            self.finishUpload(upload)

                        else
                            error =
                                if xhr.getResponseHeader('content-type') == 'application/json'
                                    $.parseJSON(xhr.responseText).errors.track.join ', '
                                else
                                    'There was an unknown error!'

                            upload.isProcessing = false
                            upload.error = error
                            $rootScope.$broadcast 'upload-error', [upload, error]

                        $rootScope.$broadcast 'upload-finished', upload

                    # send the track to the server
                    formData = new FormData();
                    formData.append('track', file);

                    xhr.open 'POST', '/api/web/tracks/upload', true
                    xhr.setRequestHeader 'X-XSRF-TOKEN', $.cookie('XSRF-TOKEN')
                    xhr.send formData
])
