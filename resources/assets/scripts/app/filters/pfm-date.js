/*
    If you're wondering, this is indeed a copy/paste of angular's date filter with all of its internal dependencies.
    Why, you ask? Well, I needed to add lines 190 and 191, and didn't want to edit the source of angular itself.
    Now this filter supports dates returned directly from Carbon.
 */

angular.module('ponyfm').filter('pfmdate', [
    '$locale',
    function($locale) {
        function isString(value){return typeof value == 'string';}
        function isNumber(value){return typeof value == 'number';}

        function isDate(value){
            if (!value)
                return false;

            return Object.prototype.toString.apply(value) == '[object Date]';
        }

        function padNumber(num, digits, trim) {
            var neg = '';
            if (num < 0) {
                neg =  '-';
                num = -num;
            }
            num = '' + num;
            while(num.length < digits) num = '0' + num;
            if (trim)
                num = num.substr(num.length - digits);
            return neg + num;
        }

        function int(str) {
            return parseInt(str, 10);
        }

        function concat(array1, array2, index) {
            return array1.concat([].slice.call(array2, index));
        }

        function isArrayLike(obj) {
            if (!obj || (typeof obj.length !== 'number')) return false;

            // We have on object which has length property. Should we treat it as array?
            if (typeof obj.hasOwnProperty != 'function' &&
                typeof obj.constructor != 'function') {
                // This is here for IE8: it is a bogus object treat it as array;
                return true;
            } else  {
                return obj instanceof JQLite ||                      // JQLite
                    (jQuery && obj instanceof jQuery) ||          // jQuery
                    toString.call(obj) !== '[object Object]' ||   // some browser native object
                    typeof obj.callee === 'function';              // arguments (on IE8 looks like regular obj)
            }
        }

        function isFunction(value){return typeof value == 'function';}

        function forEach(obj, iterator, context) {
            var key;
            if (obj) {
                if (isFunction(obj)){
                    for (key in obj) {
                        if (key != 'prototype' && key != 'length' && key != 'name' && obj.hasOwnProperty(key)) {
                            iterator.call(context, obj[key], key);
                        }
                    }
                } else if (obj.forEach && obj.forEach !== forEach) {
                    obj.forEach(iterator, context);
                } else if (isArrayLike(obj)) {
                    for (key = 0; key < obj.length; key++)
                        iterator.call(context, obj[key], key);
                } else {
                    for (key in obj) {
                        if (obj.hasOwnProperty(key)) {
                            iterator.call(context, obj[key], key);
                        }
                    }
                }
            }
            return obj;
        }

        var uppercase = function(string){return isString(string) ? string.toUpperCase() : string;};

        var DATE_FORMATS_SPLIT = /((?:[^yMdHhmsaZE']+)|(?:'(?:[^']|'')*')|(?:E+|y+|M+|d+|H+|h+|m+|s+|a|Z))(.*)/,
            NUMBER_STRING = /^\d+$/;

        var DATE_FORMATS = {
            yyyy: dateGetter('FullYear', 4),
            yy: dateGetter('FullYear', 2, 0, true),
            y: dateGetter('FullYear', 1),
            MMMM: dateStrGetter('Month'),
            MMM: dateStrGetter('Month', true),
            MM: dateGetter('Month', 2, 1),
            M: dateGetter('Month', 1, 1),
            dd: dateGetter('Date', 2),
            d: dateGetter('Date', 1),
            HH: dateGetter('Hours', 2),
            H: dateGetter('Hours', 1),
            hh: dateGetter('Hours', 2, -12),
            h: dateGetter('Hours', 1, -12),
            mm: dateGetter('Minutes', 2),
            m: dateGetter('Minutes', 1),
            ss: dateGetter('Seconds', 2),
            s: dateGetter('Seconds', 1),
            // while ISO 8601 requires fractions to be prefixed with `.` or `,`
            // we can be just safely rely on using `sss` since we currently don't support single or two digit fractions
            sss: dateGetter('Milliseconds', 3),
            EEEE: dateStrGetter('Day'),
            EEE: dateStrGetter('Day', true),
            a: ampmGetter,
            Z: timeZoneGetter
        };

        function dateGetter(name, size, offset, trim) {
            offset = offset || 0;
            return function(date) {
                var value = date['get' + name]();
                if (offset > 0 || value > -offset)
                    value += offset;
                if (value === 0 && offset == -12 ) value = 12;
                return padNumber(value, size, trim);
            };
        }

        function dateStrGetter(name, shortForm) {
            return function(date, formats) {
                var value = date['get' + name]();
                var get = uppercase(shortForm ? ('SHORT' + name) : name);

                return formats[get][value];
            };
        }

        function timeZoneGetter(date) {
            var zone = -1 * date.getTimezoneOffset();
            var paddedZone = (zone >= 0) ? "+" : "";

            paddedZone += padNumber(Math[zone > 0 ? 'floor' : 'ceil'](zone / 60), 2) +
                padNumber(Math.abs(zone % 60), 2);

            return paddedZone;
        }

        function ampmGetter(date, formats) {
            return date.getHours() < 12 ? formats.AMPMS[0] : formats.AMPMS[1];
        }

        var R_ISO8601_STR = /^(\d{4})-?(\d\d)-?(\d\d)(?:T(\d\d)(?::?(\d\d)(?::?(\d\d)(?:\.(\d+))?)?)?(Z|([+-])(\d\d):?(\d\d))?)?$/;
        function jsonStringToDate(string) {
            var match;
            if (match = string.match(R_ISO8601_STR)) {
                var date = new Date(0),
                    tzHour = 0,
                    tzMin  = 0,
                    dateSetter = match[8] ? date.setUTCFullYear : date.setFullYear,
                    timeSetter = match[8] ? date.setUTCHours : date.setHours;

                if (match[9]) {
                    tzHour = int(match[9] + match[10]);
                    tzMin = int(match[9] + match[11]);
                }
                dateSetter.call(date, int(match[1]), int(match[2]) - 1, int(match[3]));
                var h = int(match[4]||0) - tzHour;
                var m = int(match[5]||0) - tzMin;
                var s = int(match[6]||0);
                var ms = Math.round(parseFloat('0.' + (match[7]||0)) * 1000);
                timeSetter.call(date, h, m, s, ms);
                return date;
            }
            return string;
        }


        return function(date, format) {
            var text = '',
                parts = [],
                fn, match;

            if (typeof(date) == 'object' && date.date) {
                date = date.date;
            }

            format = format || 'mediumDate';
            format = $locale.DATETIME_FORMATS[format] || format;
            if (isString(date)) {
                if (NUMBER_STRING.test(date)) {
                    date = int(date);
                } else {
                    date = jsonStringToDate(date);
                }
            }

            if (isNumber(date)) {
                date = new Date(date);
            }

            if (!isDate(date)) {
                return date;
            }

            while(format) {
                match = DATE_FORMATS_SPLIT.exec(format);
                if (match) {
                    parts = concat(parts, match, 1);
                    format = parts.pop();
                } else {
                    parts.push(format);
                    format = null;
                }
            }

            forEach(parts, function(value){
                fn = DATE_FORMATS[value];
                text += fn ? fn(date, $locale.DATETIME_FORMATS)
                    : value.replace(/(^'|'$)/g, '').replace(/''/g, "'");
            });

            return text;
        };
    }]);
