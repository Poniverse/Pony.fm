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
  event.respondWith(
    caches.match(event.request).then(function(response) {
      return response || fetch(event.request);
    }).catch(function () {
      if (event.request.mode == 'navigate') {
        return caches.match('/offline.html');
      }
    })
  )
});
