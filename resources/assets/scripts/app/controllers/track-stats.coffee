# Pony.fm - A community for pony fan music.
# Copyright (C) 2016 Peter Deltchev
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


module.exports = angular.module('ponyfm').controller 'track-stats', [
    '$scope', '$state', 'track-stats'
    ($scope, $state, statsService) ->
        $scope.trackId = parseInt($state.params.id)

        labelArray = []
        dailyArray = []
        cumArray = []

        statsLoaded = (stats) ->
            console.log(stats)

            for key, value of stats.playStats
                labelArray.push value.hour || value.days
                dailyArray.push value.plays

            i = 0
            while i < dailyArray.length
                if i == 0
                    cumArray[i] = dailyArray[0]
                else
                    cumArray[i] = cumArray[i - 1] + dailyArray[i]
                i++

            $scope.playsLabels = labelArray
            $scope.playsData = cumArray
            $scope.colours = ['#B885BD']
            $scope.series = ['Plays']
            $scope.totalSelected = true

            $scope.dailyText = stats.type

        $scope.totalClick = () ->
            $scope.playsData = cumArray
            $scope.totalSelected = true

        $scope.dailyClick = () ->
            $scope.playsData = dailyArray
            $scope.totalSelected = false

        statsService.loadStats($scope.trackId).done statsLoaded
]
