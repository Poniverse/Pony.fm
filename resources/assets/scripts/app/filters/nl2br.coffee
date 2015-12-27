# Based on https://gist.github.com/kensnyder/49136af39457445e5982

angular.module('ponyfm').filter 'nl2br', [
  '$sanitize'
  ($sanitize) ->
    tag = if /xhtml/i.test(document.doctype) then '<br />' else '<br>'
    (msg) ->
      # ngSanitize's linky filter changes \r and \n to &#10; and &#13; respectively
      msg = (msg + '').replace(/(\r\n|\n\r|\r|\n|&#10;&#13;|&#13;&#10;|&#10;|&#13;)/g, tag + '$1')
      $sanitize msg
]