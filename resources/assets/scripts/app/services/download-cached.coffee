angular.module('ponyfm').factory('download-cached', [
  '$rootScope', '$http', '$log'
  ($rootScope, $http, $log) ->
    download = (type, id, format) ->
      url = '/api/web/' + type + '/download-cached/' + id + '/' + format

      encodingComplete = (response) ->
        if response.data.url == null
          'pending'
        else
          response.data.url

      encodingFailed = (error) ->
        $log.error 'Error downloading encoded file - Status: ' + error.status + '- Message: ' + error.data
        'error'

      $http.get(url).then(encodingComplete).catch encodingFailed

    {download: download}
])