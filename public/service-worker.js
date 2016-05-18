var urlsToCache = [
  '/',
  '/build/styles/app.css',
  '/build/scripts/app.js',
  '/build/scripts/templates.js',
  '/templates/directives/player.html',
  '/templates/directives/search.html',
  '/templates/directives/tracks-list.html',
  '/templates/directives/users-list.html',
  '/templates/directives/albums-list.html',
  '/templates/directives/playlists-list.html',
  '/templates/home/index.html',

];

var CACHE_NAME = 'pfm-cache-v1';

// Set the callback for the install step
self.addEventListener('install', function(event) {
  // Perform install steps
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(function(cache) {
        console.log('Opened cache');
        return cache.addAll(urlsToCache);
    })
  );
});

function updateCacheFile(request) {
  caches.open(CACHE_NAME).then(function(cache) {
    fetch(request).then(function(response) {
      cache.put(request, response.clone());
      console.log(request.url + " updated!");
    });
  });
}

self.addEventListener('fetch', function(event) {
  var request = event.request;

  if (request.url.indexOf('?') != -1) {
    if (!isNaN(request.url.charAt(request.url.indexOf('?') + 1))) {
      var url = request.url.substring(0, request.url.indexOf('?'));
      request = new Request(url, {
        mode: 'non-cors'
      });
    }
  }

  if (request.url.indexOf('pony') != -1 ||
      request.url.indexOf('local') != -1 ||
      request.url.indexOf('192') != -1 ||
      request.url.indexOf('fonts') != -1 ||
      request.url.indexOf('gravatar') != -1) {
    event.respondWith(
      caches.open(CACHE_NAME).then(function(cache) {
        return fetch(request).then(function(response) {
          cache.put(request, response.clone());
          return response;
        }).catch(function(err) {
          return caches.match(request);
        });
      })
    );
  } else {
    event.respondWith(
      fetch(event.request).then(function(response) {
        return response;
      })
    );
  }
});
