/***************************************************************************
 * This file is part of Roundcube "summary" plugin.              
 *                                                                 
 * Your are not allowed to distribute this file or parts of it.    
 *                                                                 
 * This file is distributed in the hope that it will be useful,    
 * but WITHOUT ANY WARRANTY; without even the implied warranty of  
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.          
 *                                                                 
 * Copyright (c) 2012 - 2015 Roland 'Rosali' Liebl - all rights reserved
 * dev-team [at] myroundcube [dot] com
 * http://myroundcube.com
 ***************************************************************************/

var summary_geoip_data = [];

function summary_answer(a) {
    a.success ? (rcmail.display_message(rcmail.gettext("summary.answermatch"), "confirmation"), window.setTimeout("document.location.href='./?_task=mail';", 2E3), $("#secretanswer").props("readonly", !0)) : ($("#secretanswer").focus(), rcmail.display_message(rcmail.gettext("summary.answerdoesnotmatch"), "error"), a.logout ? document.location.href = "./?_task=logout&_err=pwtools.locked" : 2 < a.attempt && ($("#lastattempt").html(rcmail.gettext("summary.lastattempt")), rcmail.display_message(rcmail.gettext("summary.lastattempt"), "warning")))
}

function summary_force_secret_qa() {
    $("#header a").each(function() {
        !$(this).hasClass("button-logout") && !$(this).hasClass("support-link") && ($(this).attr("href", "#"), $(this).attr("onclick", ""), $(this).attr("target", ""), $(this).unbind("click"))
    });
    $(".deco").remove();
    $("form").remove();
    $("fieldset").each(function() {
        "flastlogin" != this.id && $(this).remove()
    });
    $(document).keypress(function(a) {
        13 == a.charCode && summary_unlock()
    });
    $("#unlockbutton").click(function() {
        summary_unlock()
    });
    $("#secretquestionanswer").show();
    $("#secretanswer").focus();
    rcmail.display_message(rcmail.gettext("summary.faraway"), "warning")
}

function summary_format_distance(a) {
    if (1 > a) return 0;
    var b = rcmail.gettext("summary.thousand_separator"),
        d = a.substr(a.length - 3),
        c = 0;
    3 < a.length && (c = a.substring(0, a.length - 3));
    a = "";
    0 < c && (a = c + b);
    return a + d
}

function summary_calculate_distance() {
    try {
        latLngA = new google.maps.LatLng(summary_geoip_data[0].latitude.replace(",", "."), summary_geoip_data[0].longitude.replace(",", "."));
        latLngB = new google.maps.LatLng(summary_geoip_data[1].latitude.replace(",", "."), summary_geoip_data[1].longitude.replace(",", "."));
        var a = Math.round(google.maps.geometry.spherical.computeDistanceBetween(latLngA, latLngB) / 1E3);
        if (a) {
            var b = Math.round(0.621371192237334 * a);
            rcmail.env.double_login_distance && a > rcmail.env.double_login_distance &&
                rcmail.http_post("plugin.summary_suspicious", "_distance=" + a + "&_currenthost=" + summary_geoip_data[0].ip + "&_lasthost=" + summary_geoip_data[1].ip + "&_currentcountryName=" + summary_geoip_data[0].country_name + "&_lastcountryName=" + summary_geoip_data[1].country_name + "&_currentcountryCode=" + summary_geoip_data[0].country_code + "&_lastcountryCode=" + summary_geoip_data[1].country_code + "&_currentregion=" + summary_geoip_data[0].region_name + "&_lastregion=" + summary_geoip_data[1].region_name + "&_currentcity=" + summary_geoip_data[0].city +
                    "&_lastcity=" + summary_geoip_data[1].city + "&_currentlatitude=" + summary_geoip_data[0].latitude + "&_lastlatitude=" + summary_geoip_data[1].latitude + "&_currentlongitude=" + summary_geoip_data[0].longitude + "&_lastlongitude=" + summary_geoip_data[1].longitude);
            a = summary_format_distance(a + "");
            b = summary_format_distance(b + "");
            0 != a && $("#distancecontainer").html("<br />" + rcmail.gettext("summary.distance") + "&nbsp;<b>" + a + "</b>&nbsp;" + rcmail.gettext("summary.kilometers") + "&nbsp;" + rcmail.gettext("summary.or") + "&nbsp;<b>" +
                b + "</b>&nbsp;" + rcmail.gettext("summary.miles") + ".")
        }
    } catch (d) {}
}

function summary_inject_geoip_session(a) {
    summary_inject_geoip(a.geoip, a.nb)
}

function summary_inject_geoip(a, b, d) {
    !a.ipv4 && a.ip && (a.ipv4 = a.ip);
    if (a.ip && a.latitude && a.longitude) {
        if (d)
            for (var c in a) $('<input name="_geoip[' + c + ']" value="' + a[c] + '" type="hidden" />').appendTo("form");
        else summary_geoip_data[b - 1] = a, $("#geoipcontainer" + b).html("<br />&raquo;&nbsp;<i>" + a.country_name + "&nbsp;[" + a.country_code + "]" + (a.region_name ? "&nbsp;-&nbsp;" + a.region_name : "") + (a.city ? "&nbsp;-&nbsp;" + a.city : "") + "</i>"), $("#geoiplink" + b).attr("onclick", "window.open('plugins/summary/maps.php?lang=" +
            rcmail.env.maps_lang + "&region=" + a.country_code + "&lcity=" + rcmail.gettext("summary.city") + "&city=" + a.city + "&lcountry=" + rcmail.gettext("summary.country") + "&country=" + a.country_name + "&lip=" + rcmail.gettext("summary.ipaddress") + "&ip=" + a.ipv4 + "&lat=" + a.latitude + "&long=" + a.longitude + "', \"geoip\", 'width=520,height=420,resizable=yes,toolbar=no,status=no,location=no')");
        rcmail.http_post("plugin.summary_geoip_db", "_data=" + JSON.stringify(a))
    }
    2 == summary_geoip_data.length && summary_calculate_distance()
}

function summary_unlock() {
    if ("" != $("#secretanswer").val()) {
        var a = $.trim($("#secretanswer").val());
        $("#secretanswer").val("");
        rcmail.http_post("plugin.summary_answer", "_ip=" + summary_geoip_data[0].ip + "&_answer=" + a)
    } else $("#secretanswer").focus(), rcmail.display_message(rcmail.gettext("summary.faraway"), "warning")
}
$(document).ready(function() {
    rcmail.addEventListener("plugin.summary_force_secret_qa", summary_force_secret_qa);
    rcmail.addEventListener("plugin.summary_inject_geoip_session", summary_inject_geoip_session);
    rcmail.addEventListener("plugin.summary_answer", summary_answer)
});
