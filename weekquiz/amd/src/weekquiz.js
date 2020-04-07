// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 *
 * @module     block_weekquiz/weekquiz
 * @package    block_weekquiz
 * @copyright  2018 Sumit Negi
 */
define(['jquery', 'core/ajax', 'core/str', 'core/form-autocomplete', 'core/notification'],
        function ($, Ajax, str, Autocomplete, notification) {
            var manager = {
                showItems: function (e) {
                    var courseid = $(this).attr('data-value');
                    if (courseid) {
                        var request = {
                            methodname: 'block_weekquiz_get_course_quizzes',
                            args: {
                                courseid: courseid
                            },
                            done: function (data) {
                                var html = '<ul class="list-group">';
                                var index = '';
                                for (index in data) {
                                    var quiz = data[index];
                                    html += '<li class="list-group-item">';
                                    html += '<span><input type="radio" name="quizitem" class="quizitem" value="' + quiz['id'] + '">&nbsp;</span><span>' + quiz['name'] +'</span>';
                                    html += '</li>';
                                }
                                html += '</ul>';
                                $("#quizcontainer").html(html);
                            },
                        };
                        var requests = Ajax.call([request]);
                    }
                },

                setup: function () {
                    $('body').delegate('.form-group ul.form-autocomplete-suggestions li', 'click', manager.showItems);
                }
                ,
                quizlist: function (data) {
                   // console.log(data);
                }
            };

            return {
                setup: manager.setup
            };
        });
