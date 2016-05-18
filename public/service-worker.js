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
  // Doesn't do anything right now
  // Could never get offline to fully
  // work without bugs :(

  // Perform install steps
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(function(cache) {
        console.log('Opened cache');
    })
  );
});
