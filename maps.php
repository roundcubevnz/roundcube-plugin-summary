<?php
# 
# This file is part of Roundcube "summary" plugin.
# 
# Your are not allowed to distribute this file or parts of it.
# 
# This file is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
# 
# Copyright (c) 2012 - 2015 Roland 'Rosali' Liebl - all rights reserved.
# dev-team [at] myroundcube [dot] net
# http://myroundcube.com
# 
if(!isset($_GET['lat'])||!isset($_GET['long'])||!isset($_GET['city'])){header("HTTP/1.1 401 Unauthorized");die('Access denied');}foreach($_GET as$key=>$val){if(!$val){header("HTTP/1.1 401 Unauthorized");die('Access denied');}$_GET[$key]=trim(urldecode($val));}?>
<!DOCTYPE html>
<html>
<title>Google Maps</title>
<head>
<script src="../../program/js/jquery.min.js"></script>
<script src="https://maps.googleapis.com/maps/api/js?v=3.exp&sensor=false&region=<?PHP echo$_GET['region']?>&language=<?PHP echo$_GET['lang']?>"></script>
<script>
  var geocoder;
  var map;
  var latlng;
  var init = false;
  function initialize() {
    $('#map-canvas').height($(document).innerHeight() - 20);
    $('#map-canvas').width($(document).innerWidth() - 20);
    try{
      latlng = new google.maps.LatLng(<?PHP echo$_GET['lat']?>,<?PHP echo$_GET['long']?>);
      geocoder = new google.maps.Geocoder();
      var mapOptions = {
        zoom: 8,
        center: latlng
      }
      map = new google.maps.Map(document.getElementById("map-canvas"), mapOptions);
      return true;
    }
    catch(e){
      return false;
    }
  }

  function codeAddress() {
    <?php
$repl=array('Monaco');$replby=array('Munich');$string=str_replace($repl,$replby,$_GET['city']);if(!$city=iconv('UTF-8','ISO-8859-1//TRANSLIT//IGNORE',$string)){$city=$_GET['city'];}?>
    var address = "<?PHP echo$city?>";
    geocoder.geocode( { 'address': address}, function(results, status) {
      if (status == google.maps.GeocoderStatus.OK) {
        var contentString = "<div style=\"font-family:Arial,Helvetica,Tahoma;font-size:11px;\" align=\"left\"><strong><?PHP echo$_GET['lcity']?></strong>: <?PHP echo utf8_decode($string)?><br><strong><?PHP echo$_GET['lcountry']?></strong>: <?PHP echo$_GET['country']?><br><strong><?PHP echo$_GET['lip']?></strong>: <?PHP echo$_GET['ip']?><br> </div>";
        var infowindow = new google.maps.InfoWindow({
           content: contentString,
        });
        map.setCenter(results[0].geometry.location);
        var marker = new google.maps.Marker({
            map: map,
            position: results[0].geometry.location
        });
      } else {
        var contentString = "<div style=\"font-family:Arial,Helvetica,Tahoma;font-size:11px;\" align=\"left\"><strong><?PHP echo$_GET['lcountry']?></strong>: <?PHP echo$_GET['country']?><br><strong><?PHP echo$_GET['lip']?></strong>: <?PHP echo$_GET['ip']?><br> </div>";
        var infowindow = new google.maps.InfoWindow({
           content: contentString,
        });
        map.setCenter(latlng);
        var marker = new google.maps.Marker({
            map: map,
            position: latlng
        });
      }
      infowindow.open(map, marker);
    });
  }
</script>
</head>
<body onload="if(initialize()){ codeAddress(); }" style="background: url(../../skins/larry/images/linen.jpg?v=0382.14157) repeat #d1d5d8;">
  <center><div id="map-canvas" style="width: 500px; height: 400px;"></div></center>
</body>