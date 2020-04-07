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
define(['jquery', 'jqueryui', 'core/ajax', 'core/str', 'core/form-autocomplete', 'core/notification', 'core/templates', 'core/url'],
        function ($, JqUi, Ajax, str, Autocomplete, notification, Templates, URL) {
            var manager = {
                showCoursesItems: function () {
                    var categoryid = parseInt($(this).val());
                    manager.clearActivitiesitems();
                    if (categoryid) {
                        var request = {

                            methodname: 'block_customreports_get_courses_list',
                            args: {
                                categoryid: categoryid
                            },
                            done: function (data) {
                                var courselist = [];
                                var data = $.map(data, function (value) {
                                    return [value];
                                });
                                courselist['courses'] = data;
                                Templates.render('block_customreports/courselist', courselist).done(function (data) {

                                    $("#id_course").html(data);
                                    $("#id_course").removeAttr('disabled');
                                });
                            }


                        };
                        Ajax.call([request]);
                    } else {
                        manager.clearCoursesitems();
                    }
                },
                showActivitiesItems: function () {

                    var courseid = parseInt($(this).val());
                    var reportType = $("[name='reporttype']:checked").val();
                    if (courseid) {
                        var request = {

                            methodname: 'block_customreports_get_activities_list',
                            args: {
                                courseid: courseid,
                                report: reportType
                            },
                            done: function (data) {

                                var activitieslist = [];
                                var data = $.map(data, function (value) {
                                    return [value];
                                });
                                activitieslist['activities'] = data;
                                Templates.render('block_customreports/activitylist', activitieslist).done(function (data) {
                                    $("#id_activity").html(data);
                                    $("#id_activity").removeAttr('disabled');
                                });
                            }


                        };
                        Ajax.call([request]);
                    }
                },
                clearActivitiesitems: function () {
                    str.get_string("all").done(function (str) {
                        $(".customreportsform #id_activity").html("<option value=0>" + str + "</option>");
                        $(".customreportsform #id_activity").attr('disabled', 'disabled');
                    });
                },
                clearCoursesitems: function () {
                    str.get_string("all").done(function (str) {
                        $(".customreportsform #id_course").html("<option value=0>" + str + "</option>");
                    });
                    $(".customreportsform #id_course").attr('disabled', 'disabled');
                },
                addProfileItem: function () {

                    var field = $(this).val();
                    var fieldlabel = $(this).find("option:selected").text();
                    $("#id_userprofile option[value=" + field + "]").remove();
                    var data = [];
                    data['field'] = field;
                    data['label'] = fieldlabel;
                    if (field == 'country') {
                        $.ajax({
                            url: 'country.php',
                            dataType: "json",
                            success: function (countries) {
                                data['makeinput'] = 0;
                                data['countries'] = countries;

                                Templates.render('block_customreports/country', data).done(function (data) {
                                    $("#profilefield_filters").append(data);
                                });

                            },
                            deferRequestBy: 400
                        });

                    } else {

                        Templates.render('block_customreports/addprofilefield', data).done(function (data) {
                            $("#profilefield_filters").append(data);


                            $("#" + field).autocomplete({

                                source: function (request, response) {
                                    $.ajax({
                                        url: 'auto_suggestion.php',
                                        dataType: "json",
                                        data: {'field': field, 'search': request.term},
                                        success: function (data) {
                                            response(data);
                                        },
                                        deferRequestBy: 400
                                    });
                                }
                            });

                        });
                    }
                },
                removeProfileItem: function () {

                    var field = $(this).attr("profilefield");
                    var label = $(this).attr("profilelabel");
                    $(this).closest('#' + field + "_container").remove(); // Remove profile filter item
                    $('#id_userprofile')
                            .append($("<option></option>")
                                    .attr("value", field)
                                    .text(label));
                }
                ,
                setup: function () {

                    $('body').delegate('.customreportsform #id_category', 'change', manager.showCoursesItems);
                    $('body').delegate('.customreportsform #id_course', 'change', manager.showActivitiesItems);
                    $('body').delegate('.customreportsform #id_userprofile', 'change', manager.addProfileItem);
                    $('body').delegate('.removefieldcontainer', 'click', manager.removeProfileItem);
                    $('body').delegate('.customreportsform', 'submit', manager.getReport);
                    $('body').delegate('.customreportsform #id_exportcsv', 'click', manager.exportreport);
                    $('body').delegate('.customreportsform #id_submitbutton', 'click', manager.showReport);
                    $('body').delegate('.pagination .page-item', 'click', manager.paginationShowReport);
                },
                showReport: function () {

                    manager.exportReport = false;

                    $(".customreportsform").submit();
                },
                paginationShowReport: function (e) {
                    // context = $(this).find('a');
                    e.preventDefault();
                    // $(this).find('a').attr("disabled", "disabled");
                    var hash;
                    var link = $(this).find('a').attr('href');

                    var hashes = link.slice(link.indexOf('?') + 1).split('&');
                    $(".customreportsform").find(".hiddenelement").html('');
                    for (var i = 0; i < hashes.length; i++)
                    {
                        var hash = hashes[i].split('=');
                        //data[hash[0]] = hash[1];

                        var hiddenelement = $(".customreportsform").find(".hiddenelement");
                        hiddenelement.append('<input type="hidden" value=' + hash[1] + ' name=' + hash[0] + '>');
                    }

                    manager.exportReport = false;

                    $(".customreportsform").submit();
                },
                exportreport: function () {

                    $(".customreportsform").find(".hiddenelement").append('<input type="hidden" value="csv" name="format">');
                    // $(".customreportsform").stopPropagation();
                    manager.validateFilters();
                    if (!manager.generateReport) {

                        return false;
                    }
                    // $(".customreportsform").unbind("submit", manager.getReport);
                    manager.exportReport = true;
                    var reportType = $("[name='reporttype']:checked").val();
                    $(".customreportsform").attr('action', URL.fileUrl("/blocks/customreports/type/" + reportType + ".php", ""));
                    $(".customreportsform").submit();


                },
                getReport: function (e) {


                    if (!manager.exportReport) {
                        $(".customreportsform").attr('action', URL.fileUrl("/blocks/customreports/addreport.php", ""));
                        e.preventDefault();
                    } else {

                        return true;
                    }
                    $("input[name='format']").remove();
                    var reportType = $("[name='reporttype']:checked").val();
                    manager.validateFilters();
                    if (!manager.generateReport) {

                        return false;
                    }

                    var data = {};
                    if ($("#id_category").val() == 0) {
                        return false;
                    }
                    var context = '';
                    data = $(this).serializeArray();
                    context = $("#id_submitbutton");
                    $("#id_submitbutton").attr("disabled", "disabled");
                    //}

                    //console.log($(this).serializeArray());
                    $(".reportContainer").html('');
                    $(".spinnercontainer").show();
                    //$(".spinner").show();
                    // manager.addSpinner();
                    $.ajax({

                        url: URL.fileUrl("/blocks/customreports/type/" + reportType + ".php", ""),
                        data: data,
                        context: context,
                        //async: false,
                        complete: function () {
                            $(".spinnercontainer").hide();
                            context.removeAttr("disabled");
                            $(".customreportsform").find(".hiddenelement").html("");
                        },
                        //dataType: "html",
                        error: function () {

                            $(".reportContainer").html("<div class='alert alert-danger'>error on page</div>");
                        },
                        statusCode: {
                            404: function () {
                                var localhtml = "<div class='alert alert-danger'>";
                                localhtml += "Coding error detected, please contact to site Admin";
                                localhtml += "</div>";
                                $(".reportContainer").html(localhtml);
                            }
                        },
                        success: function (data) {

                            $(".reportContainer").html(data);
                        }

                    });
                },
                submitReport: function (e) {

                    //var formdata = $(this).serializeArray();
                    e.preventDefault();
                    manager.getReport();
                }
                ,
                validateFilters: function () {
                    //alert($("#id_reporttype_grade").val() );
                    if ($("[name='datefilter']:checked").val()) {
                        var fromDate_year = $("[name='datefrom[year]']").val();
                        var fromDate_month = $("[name='datefrom[month]']").val();
                        var fromDate_day = $("[name='datefrom[day]']").val();
                        var toDate_year = $("[name='dateto[year]']").val();
                        var toDate_month = $("[name='dateto[month]']").val();
                        var toDate_day = $("[name='dateto[day]']").val();
                        var fromDate = new Date(fromDate_year, fromDate_month, fromDate_day);
                        var toDate = new Date(toDate_year, toDate_month, toDate_day);
                        if (fromDate > toDate) {
                            str.get_string("invalidaterange", "block_customreports").done(function (str) {
                                manager.showValidationErrorMessage(str);
                            });
                            manager.generateReport = false;
                            return;
                        }
                    }
                    if ($("#id_category").val() == 0 || $("#id_category").val() == '') {
                        str.get_string("invalidcategory", "block_customreports").done(function (str) {
                            manager.showValidationErrorMessage(str);
                        });
                        manager.generateReport = false;
                        return;
                    }
                    if (!$("[name='reporttype']").val()) {
                        str.get_string("invalidreporttype", "block_customreports").done(function (str) {
                            manager.showValidationErrorMessage(str);
                        });
                        manager.generateReport = false;
                        return;
                    }

                    var validProfileField = true;
                    if ($("[name='usefilefilter']").is(":checked") && !$(".filepicker-filename a").length) {
                        str.get_string("filenotuploaded", "block_customreports", $(this).attr('placeholder')).then(function (str) {
                            manager.showValidationErrorMessage(str);

                        });

                    }
                    if ($("[name='usefilefilter']").is(":not(:checked)")) {
                        $("[name*='filter_profile']").each(function () {

                            if (!$(this).val()) {
                                var placeholder = $(this).attr('placeholder');
                                var invalidprofilestr = str.get_string("invalidprofilefield", "block_customreports", placeholder);
                                invalidprofilestr.then(function (str) {
                                    manager.showValidationErrorMessage(str);

                                });

                                validProfileField = false;
                            }

                        });
                    }
                    if (!validProfileField) {

                        manager.generateReport = false;
                        return;
                    }

                    //var reportRequiredCourseArr = ['attempt', 'user', 'monitoring'];
                    //$.inArray($("[name='reporttype']:checked").val(), reportRequiredCourseArr);
                    if ($("#id_course").val() == 0) {
                        str.get_string("selectcourse", "block_customreports").done(function (str) {
                            manager.showValidationErrorMessage(str);
                        });
                        manager.generateReport = false;
                        return;
                    }
                    manager.generateReport = true;

                    $('.customreportvalidationerror').hide();
                    $('.customreportvalidationerror').text('');
                },
                showValidationErrorMessage: function (str) {
                    $('.customreportvalidationerror').show();
                    $('.customreportvalidationerror').text(str).focus();
                    $(window).scrollTop(0);
                }
                ,
                generateReport: true

                        /* addSpinner: function () {
                         $('body').addClass('updating');
                         var spinner = $('body').find('img.spinner');
                         if (spinner.length) {
                         spinner.show();
                         } else {
                         spinner = $('<img/>')
                         .attr('src', URL.imageUrl('i/loading_small'))
                         .addClass('spinner').addClass('smallicon')
                         ;
                         $('body').append(spinner);
                         }
                         },
                         removeSpinner: function () {
                         $('body').removeClass('updating');
                         $('body').find('img.spinner').hide();
                         }*/
                , exportReport: false
            };
            return {
                setup: manager.setup
            };
        });
