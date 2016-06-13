// Pony.fm - A community for pony fan music.
// Copyright (C) 2016 Josef Citrine
//
// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU Affero General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU Affero General Public License for more details.
//
// You should have received a copy of the GNU Affero General Public License
// along with this program.  If not, see <http://www.gnu.org/licenses/>.


var urlsToCache = [
  '/offline.html',
  '/styles/offline.css',
  '/images/ponyfm-logo-white.svg'
];

var CACHE_NAME = 'pfm-offline-v1';

var notifUrlCache = {};

function getChromeVersion () {
  var raw = navigator.userAgent.match(/Chrom(e|ium)\/([0-9]+)\./);

  return raw ? parseInt(raw[2], 10) : false;
}

// Set the callback for the install step
self.addEventListener('install', function(event) {
  // Perform install steps
  event.waitUntil(
    caches.open(CACHE_NAME)
    .then(function(cache) {
      cache.addAll(urlsToCache);
    })
  );
});

// Delete old caches
self.addEventListener('activate', function (event) {
  event.waitUntil(caches.keys().then(function (cacheNames) {
    return Promise.all(cacheNames.map(function (cacheName) {
      if (cacheName != CACHE_NAME) {
        return caches.delete(cacheName);
      }
    }));
  }));
});

// Basic offline mode
// Just respond with an offline error page for now
self.addEventListener('fetch', function(event) {
  if (event.request.url.indexOf('stage.pony.fm') > -1) {
    event.respondWith(fetch(event.request));
  } else {
    event.respondWith(
        caches.match(event.request).then(function (response) {
          return response || fetch(event.request);
        }).catch(function () {
          if (event.request.mode == 'navigate') {
            return caches.match('/offline.html');
          }
        })
    )
  }
});

self.addEventListener('push', function(event) {
  console.log(event);
  var data = {};

  if (event.data) {
    console.log(event.data.json());
    data = JSON.parse(event.data.text());
  }

  notifUrlCache['pfm-' + data.id] = data.url;

  self.registration.showNotification(data.title, {
    body: data.text,
    icon: data.image,
    tag: 'pfm-' + data.id
  })
});

self.addEventListener('notificationclick', function(event) {
  event.notification.close();

  event.waitUntil(
    clients.matchAll({
      type: "window"
    })
    .then(function(clientList) {
      var url = notifUrlCache[event.notification.tag];
      for (var i = 0; i < clientList.length; i++) {
        var client = clientList[i];
        if (client.url == url && 'focus' in client)
          return client.focus();
      }

      if (clients.openWindow) {
        return clients.openWindow(url);
      }
    })
  );
});