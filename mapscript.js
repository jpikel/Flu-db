/****************************************
 * Filename: mapscript.js
 * Author: Johannes Pikel
 * ONID: pikelj
 * Class: CS340-400
 * Date: 2016.02.19
 * Assignment: Final Project
 * Description: This generates a google map populated with markers from the flu database
 * Reference: a lot of this code is directly from the google maps api example page
 *
 * https://developers.google.com/maps/documentation/javascript/mysql-to-maps
 * *************************************/

//a global of the map and bounds object
//this way other function may have access to these and they do not lose scope
//I realize there is a definite downside to globals, but hopefully this will be ok
var map = null;
var bounds = null;


/****************************************
* Function: initMap()
* Parameters: none
* Description: This is the callback function that the google maps script will call 
* when it is complete with its request from the server as called in map.php
* Creates a new google map when the div with id map is located.
* Center's the map to Corvallis Oregon.  Populates the map the with any people that
* have a checkin in the database.  
* Preconditions: callback from google api
* Postconditions: map generated with markers
* *************************************/

function initMap() {
    //initialize the map and set it's center to Corvallis OR.
    var point = {lat: 44.564568, lng: -123.262047};
    map = new google.maps.Map(document.getElementById('map'), {
        center: point
    });

    //initialize the bounds variable as a new LAT and LNG object
    //this will be used to store the bounds of the markers in the map
    bounds = new google.maps.LatLngBounds();

    //call our function downloadUrl to get 
    //this function performs an async call to the current page to get the xml 
    //document to populate the map with markers.
    downloadUrl('http://www.jpikel.com:8042/map.php', function(){
        //get all the tags that have the name marker
        var markers = document.getElementsByTagName('marker');
        Array.prototype.forEach.call(markers, function(markerElem, markers){
            //assign each of the attributes to a variable
            //these will be used to populate the infowindow of the map
            var name = markerElem.getAttribute('nickname');
            var point = new google.maps.LatLng(
                parseFloat(markerElem.getAttribute('lat')),
                parseFloat(markerElem.getAttribute('lng')));

            var tstmp = markerElem.getAttribute('tstamp');
            var dob = markerElem.getAttribute('dob');
            var etype = markerElem.getAttribute('type');
            var qty = markerElem.getAttribute('qty');
            var feels = markerElem.getAttribute('feels');

            //start generating the string that will be inside of our infowindow
            //this can accept html tags as well as strings
            //first add the name
            var contentString = "<strong>" + name + "</strong>";

            //add the timestamp of this entry
            contentString += "<p>Checked in at: " + tstmp + "</p>";

            //I make several checks to make sure we get any possible options here
            //if a particular field is blank, does not exist or has not value
            //make it explicit if we did not get a date of birth from the db
            if (dob === null || dob === "" || dob === undefined){
                dob = "unknown";
            }
            //add a string about the date of birth
            contentString += "<p>Born: " + dob + "</p>";

            //etype is the environment, if one was not stored in the db replace it
            //with the following
            if (etype === null || !etype || etype === undefined){
                etype = "unknown environment";
            }
            //similarly for the quantity of people at that location
            if (qty === null || qty === "0" || !qty){
                qty = "unknown number of "; 
            }

            contentString += "<p>In a: " + etype + " with ";
            //if we did have a value stored for quantity make the value makes some
            //human interpretable sense
            if(qty === "5")
                contentString += qty + " or less ";
            else if(qty === "10")
                contentString += (qty - 4) + " - " + qty + " ";
            else if (qty === "20" || qty === "30" || qty === "40" || qty === "50")
                contentString += (qty - 9) + " - "  + qty + " ";
            else if (qty === "51")
                contentString += "a lot of ";
            else
                contentString += qty;

            contentString += "people around</p>";

            //now add the symptoms that were concatanated together from the database
            //to our infowindow
            contentString += "<p>Had these symptoms and severities:</p>";
            if(feels === null || feels === undefined || feels === ""){ 
                feels = "none";
            } 
            contentString += "<p>" + feels + "</p>";
            //create a new marker, at the given lat and lng above
            //add it to the map on the page
            var marker = new google.maps.Marker({
                map: map,
                position: point,

            });
            //extend the bounds of the map to include this new marker as 
            //necessary
            bounds.extend(marker.getPosition());
            //also add the infowwindow as an attribute of this marker
            //this is the popup window when the marker is clicked
            marker.infowindow = new google.maps.InfoWindow({
                content: contentString 
            });
    
            //and add a listener to open this infowindow when the marker is clicked
            marker.addListener('click', function(){
                marker.infowindow.open(map, marker);
            });
        
        });
        //recenter the map to Corvallis and reset it bounds to the bounds obejct
        //I added this because when I refreshed or used back and forward in my browser
        //I would end up in the Pacific ocean.  So an explicit call to recenter
        //This is the same function that the recenter button on the page calls
        recenter();
    });
}

/****************************************
* Function: recenter
* Parameters: none -- accesses the global variables map and bounds
* Description: sets the center of the map to be Corvallis Oregon and then
* sets the bounds of the map as stored in the bounds object
* Preconditions: map and bounds exist and are not null
* Postconditions: map centered and bounds extended to include all markers
* *************************************/

function recenter(){
    map.setCenter(new google.maps.LatLng(44.564568, -123.262047));
    map.fitBounds(bounds);
}    

/****************************************
 * Function: downloadUrl
 * Parameters: a url and callback function
 * Description: creates a new XMLHttpRequest to the given url as an asynchronous call
 * when the page is ready then it calls back to the function passed in.  
 * In this case I changed this from the google example.  Instead of sending back the
 * data which was causing me errors. I just call the callback function in initMap once
 * the page responds with the readystate.
 * Then from within the initMap function I collect all the tags that are a marker
 *
 * Preconditions: valid url and callback function passed in
 * Postconditions: once readystate changes to 4 the call the call back function
 * *************************************/

function downloadUrl(url, callback) {
    var request = window.ActiveXObject ?
        new ActiveXObject('Microsoft.XMLHTTP') :
        new XMLHttpRequest;

    request.onreadystatechange = function() {
        if(request.readyState == 4) {
            request.onreadystatechange = doNothing;
            callback();
        }
    };
      
    request.open('GET', url, true);
    request.send(null);
}  

/****************************************
* Function: doNothing
* Parameters: none
* Description: does nothing. used in the downloadUrl
* Preconditions: none
* Postconditions: none
* *************************************/

function doNothing() {}

/****************************************
 * Function: after the DOMContentLoaded
 * Parameters: none
 * Description: adds event listener to the buttons on the page
 * Preconditions: buttons exist on the page and Dom Content is loaded
 * Postconditions: buttons have event listeners
 * *************************************/

document.addEventListener('DOMContentLoaded', function(){
    /* no longer in use as the page now automatically gets the map with the */
    /* callback feature in the map.php page where the google maps api is called */
/*    document.getElementById('getMap').addEventListener('click', function(){
        initMap();
    });*/


    /* this button serves to recenter the map */
    document.getElementById('recenter').addEventListener('click', function(){
        recenter();
    });


});

