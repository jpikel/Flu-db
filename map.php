<!--Filename:fludb.php
    Author: Johannes Pikel
    ONID: pikelj
    Date: 2017.02.08
    email: pikelj@oregonstate.edu
    Class: CS340-400
    Assignment: Final project database interaction.  This page will display a google
    map of the checkins in the flu database.
    Due Date: 2017.03.19
    Major reference: a lot of the code in this page came from
    https://developers.google.com/maps/documentation/javascript/mysql-to-maps
-->



<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8">
        <link rel="stylesheet" href="style.css" type="text/css">
        <title>Flu Map</title>
    </head>
    <body>
        <h3>Flu DB Map</h3>
        <div class="note">
            The markers have info windows, but the click field is a bit above the marker
            <br>
            Sometimes the map looses its center. In that case please use the 
            recenter button.
            <br>
            Only those people that have a checkin should show up in this map.
        </div>    
        <div id="map"></div>
        <button id="recenter">Recenter map</button>
        <!-- button used during testing -->
<!--        <button id="getMap">get map</button>-->
        <a href="fludb.php">Back</a>
        <!-- This key is restricted to calls from this webpage -->
        <script src="mapscript.js"></script>
        <script async defer
        src="https://maps.googleapis.com/maps/api/js?key=ENTERYOUAPIKEYHERE&callback=initMap">
        </script>
  </body>
</html>

<!-- the following php will add an xml document to the page with the information
     gathered from the database.  Then the javascript functions will gather this 
     information to create the markers on the map -->

<?php ini_set('display_errors', 'On'); ?>
<?php
    include 'utility.php';
    make_markers();
?>

