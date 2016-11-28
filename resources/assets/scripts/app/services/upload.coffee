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

module.exports = angular.module('ponyfm').factory('upload', [
    '$rootScope', '$http', '$timeout', 'account-tracks'
    ($rootScope, $http, $timeout, accountTracks) ->
        self =
            queue: []

            finishUploadWrapper: (upload, versionUpdate)->
                ()->
                    self.finishUpload(upload, versionUpdate)

            # Polls for the upload's status
            finishUpload: (upload, versionUpdate) ->
                # TODO: update upload status
                endpoint = "/api/web/tracks/#{upload.trackId}/upload-status"

                if versionUpdate
                    endpoint = "/api/web/tracks/#{upload.trackId}/version-upload-status"

                $http.get(endpoint).then(
                    # handle success or still-processing
                    (response)->
                        if response.status == 202
                            $timeout(self.finishUploadWrapper(upload, versionUpdate), 5000)

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

            uploadProcess: (upload, formData, trackId) ->
                versionUpdate = false
                if trackId > 0
                    versionUpdate = true

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
                        self.finishUpload(upload, versionUpdate)

                    else
                        error =
                            if xhr.getResponseHeader('content-type') == 'application/json'
                                'Error: ' + $.parseJSON(xhr.responseText)?.errors?.track?.join ', '
                            else
                                'There was an unknown error!'

                        upload.isProcessing = false
                        upload.error = error
                        $rootScope.$broadcast 'upload-error', [upload, error]

                    accountTracks.refresh(null, true)
                        .done($rootScope.$broadcast('upload-finished', upload))

                endpoint = '/api/web/tracks/upload'

                if versionUpdate
                    endpoint = '/api/web/tracks/' + trackId + '/version-upload'

                xhr.open 'POST', endpoint, true
                xhr.setRequestHeader 'X-XSRF-TOKEN', $.cookie('XSRF-TOKEN')
                xhr.send formData

            uploadNewVersion: (file, userSlug, trackId) ->
                upload =
                    name: file.name
                    progress: 0
                    uploadedSize: 0
                    size: file.size
                    index: 0
                    isUploading: true
                    isProcessing: false
                    trackId: trackId
                    success: false
                    error: null

                self.queue.push upload
                $rootScope.$broadcast 'upload-added', upload

                formData = new FormData()
                formData.append('track', file)
                formData.append('user_slug', userSlug)

                self.uploadProcess(upload, formData, trackId)

            upload: (files, userSlug) ->
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

                    formData = new FormData()
                    formData.append('track', file)
                    formData.append('user_slug', userSlug)

                    self.uploadProcess(upload, formData, 0)
])
