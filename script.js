/****************************************
 * Filename: script.js
 * Author: Johannes Pikel
 * ONID: pikelj
 * Class: CS340-400
 * Date: 2016.02.19
 * Assignment: Final Project
 * Description: a short javascript file that is used by the fludb.php adds some
 * event handlers to the show table buttons.  Also makes an asynchronous call to
 * a proxy server to handle a google maps api interaction to get the latitude and
 * longitude of a location.
 * *************************************/

/****************************************
 * Function: show_buttons_function()
 * Parameters: none
 * Description: for each of the buttons on the page that are designed to simply
 * show the contents of each of the tables in the database, change the value of the
 * tag with id showWhat to that buttons action.
 * This way all the buttons can reside in one form and each one alters the hidden
 * input showWhat so the php shows that particular table
 * Preconditions: button and showWhat id tag exist
 * Postconditions: tag with id showWhat changed value
 * *************************************/

function show_buttons_function() {
    document.getElementById('ShowPeopleBtn').addEventListener('click', function(){
       document.getElementById('showWhat').value = "showpeople";
    });

    document.getElementById('ShowBdayBtn').addEventListener('click', function(){
        document.getElementById('showWhat').value = "showbdays";
    });

    document.getElementById('ShowSympBtn').addEventListener('click', function(){
        document.getElementById('showWhat').value = "showsymptoms";
    });

    document.getElementById('ShowEnvBtn').addEventListener('click', function(){
        document.getElementById('showWhat').value = "showenviros";
    });

    document.getElementById('ShowChkBtn').addEventListener('click', function(){
        document.getElementById('showWhat').value = "showcheckin";
    });

    document.getElementById('ShowChkSym').addEventListener('click', function(){
        document.getElementById('showWhat').value = "showchksym";
    });

}

/****************************************
 * Function: convert_address()
 * Parameters: none
 * Description: gathers the information from the addressInput field, if it is blanks
 * adds and error statment.  Then makes an asynchronous call to the proxy server that
 * performs a call to the google maps api to return the geographic information about the
 * address entered.  Specifically this function pulls out the latitude and longitude and
 * updates the latitude and longitude fields in the Add checkin form.
 * Preconditions: addressInput field has a value
 * Postconditions: latitude and longitude retrieved.
 * *************************************/

function convert_address() {

    var req = new XMLHttpRequest();
    req.open("POST", 'http://www.jpikel.com:6823', true);
    req.setRequestHeader('Content-Type', 'application/json');

    var payload = { addressInput : document.getElementById('addressInput').value };
    if(payload.addressInput === ""){
        add_err_stmt();
    }
    else {

    req.send(JSON.stringify(payload));
    req.addEventListener('load', function() {
        if(req.status >= 200 && req.status < 400){
            //once we receive a response back from the server
            //parse the string we got back
            //then only add the fields from the object that are the lat and lng
            //to the check input fields for latitude and longitude
            var results = JSON.parse(req.responseText);
            //console.log(results);
            document.getElementById('lat').value = results['results'][0]['geometry']
                                                            ['location']['lat'];
            document.getElementById('lng').value = results['results'][0]['geometry']
                                                            ['location']['lng'];
            rm_wait_stmt();
            document.getElementById('addressLookUp').reset();
            document.getElementById('waitStmt').textContent = "Google returned: ";
            document.getElementById('waitStmt').textContent += results['results'][0]
                                                                ['formatted_address'];
        }
        else {
            document.getElementById('waitStmt').textContent = "Server error";
            console.log("Error");
            console.log(req);
        }
    });
    add_wait_stmt();
    }
    event.preventDefault();

}

/****************************************
 * Function: add_err_Stmt()
 * Parameters: none
 * Description: changes the text content of the element waitStmt to have
 * an error statement
 * Preconditions: tag with id waitStmt exists
 * Postconditions: text changed
 * *************************************/

function add_err_stmt() {
    document.getElementById('waitStmt').textContent = "Input cannot be empty.";
}

/****************************************
 * Function: add_wait_stmt()
 * Parameters: none
 * Description: adds a please wait statement to the waitStmt tag.  Also empties the
 * values of the latitude and longitude fields
 * Preconditions: tag with id waitStmt exists
 * Postconditions: text changed on page
 * *************************************/

function add_wait_stmt(){
    document.getElementById('waitStmt').textContent = "Please wait...";
    document.getElementById('lat').value = "";
    document.getElementById('lng').value = "";
}

/****************************************
 * Function: rm_wait_stmt()
 * Parameters: none
 * Description: clears the text content of the waitStmt tag
 * Preconditions: tag with id waitStmt exists
 * Postconditions: textConent is emptied
 * *************************************/

function rm_wait_stmt(){
    document.getElementById('waitStmt').textContent = "";
}

/****************************************
 * Function: on page load
 * Parameters: none
 * Description: after the page has loaded all the DOMContent, we'll run the function
 * show_buttons_function and add an event listener to the converAddressBtn used to 
 * look up the latitude and longitude of an address
 * Preconditions: DOMContent loaded
 * Postconditions: buttons activated
 * *************************************/


document.addEventListener('DOMContentLoaded', function() {
    show_buttons_function();

    document.getElementById('convertAddressBtn').addEventListener('click', function(){
        convert_address();
    });
});
