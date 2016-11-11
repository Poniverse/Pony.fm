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

window.pfm.preloaders['dashboard'] = [
    'dashboard'
    (dashboard) -> dashboard.refresh(true)
]

module.exports = angular.module('ponyfm').controller "dashboard", [
    '$scope', 'dashboard', 'auth', '$http', 'announcements', '$compile'
    ($scope, dashboard, auth, $http, announcements, $compile) ->

        $scope.recentTracks = null
        $scope.popularTracks = null
        $scope.announcementClass = 'disabled'
        $scope.announceWrapperClass = 'disabled'

        $scope.loadAnnouncementTemplate = (url) ->
            $http.get('/templates/' + url).success (templateContent) ->
                compiledHtml = $compile(templateContent)($scope)
                $('#announcement').append(compiledHtml)

        dashboard.refresh().done (res) ->
            $scope.recentTracks = res.recent_tracks
            $scope.popularTracks = res.popular_tracks

        announcements.refresh().done (ann) ->
            $scope.announcement = ann
            if $scope.announcement != null
                if parseInt($.cookie('hide-announcement')) != parseInt($scope.announcement.id)
                    $scope.announcement.dismiss = () ->
                        $scope.announceWrapperClass = 'disabled'

                    $scope.announcement.dontShowAgain = () ->
                        $scope.announcement.dismiss()
                        $.cookie('hide-announcement', $scope.announcement.id)

                    switch $scope.announcement.announcement_type_id
                        when 1
                            $scope.announcementClass = "simple-announce " + $scope.announcement.css_class
                            $scope.announceWrapperClass = null
                            $scope.loadAnnouncementTemplate('partials/default-announcement.html')
                        when 2
                            $scope.announcementClass = "alert-announce " + $scope.announcement.css_class
                            $scope.announceWrapperClass = null
                            $scope.loadAnnouncementTemplate('partials/alert-announcement.html')
                        when 3
                            $scope.announcementClass = "serious-alert-announce " + $scope.announcement.css_class
                            $scope.announceWrapperClass = null
                            $scope.loadAnnouncementTemplate('partials/alert-announcement.html')
                        when 4
                            $scope.announcementClass = $scope.announcement.css_class
                            $scope.announceWrapperClass = null
                            $scope.loadAnnouncementTemplate($scope.announcement.template_url)
]
