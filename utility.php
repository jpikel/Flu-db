<?php
/****************************************
* Filename: utlity.php
* Author: Johannes Pikel
* Date: 2017.02.24
* ONID: pikelj
* Class: CS340-400
* ASsignment: Final Project
* Description: php file that allows access to some queries used on the main
* page of the Fludb.
* **************************************/

/****************************************
* Function: open_connection()
* Description: Opens the connection to the mySQL DB located at
* the host, connections to the 'pikelj-db' database and returns the 
* connection.  If anything fails, it dies and the rest of the page
* will fail then too.
* Parameters: NONE
* Preconditions: mySQL database exists at host: pikelj-db
* Postconditions: connection opened to pikelj-db
****************************************/

function open_connection() {
    $dbhost = 'localhost';
    $dbuser = 'database username';
    $dbpass = 'database password';
    $dbname = 'database name';

    $conn = mysqli_connect($dbhost, $dbuser, $dbpass, $dbname)
    or die('Error connecting to mySQL server.');
    return $conn;
}

/****************************************
* Function: close_connection()
* Description: when passed a valid connection, closes the connection
* to the mysql database
* Parameters: connection variable
* Preconditions: passed valid open connection
* Postconditions: connection closed to DB
****************************************/


function close_connection($conn){
    mysqli_close($conn);
}

/****************************************
* Function: get_nicknames
* Description: none
* Parameters: gets all the nicknames stored in the people table
* for each nickname returned by the db.  Adds an option tag to the calling page
* the option tag has the same value as the field.  This should be called within
* a select tag.
* Preconditions: called inside of a select tag
* Postconditions: nicknames added as options
* **************************************/

function get_nicknames() {
    $conn = open_connection();

    $query = "SELECT id, nickname FROM `people` ORDER BY nickname";

    $result = mysqli_query($conn, $query);
    //for each nickname we make sure to use single quotes here in case nicknames have
    //spaces so the value is not truncated at the space
    if(mysqli_num_rows($result) > 0){
        while($row = $result->fetch_assoc()){
           printf("<option value='%d'>%s</option>", $row['id'], $row['nickname']);
        }
    }

    close_connection($conn);
}

/****************************************
* Function: get_enviros
* Description: none
* Parameters: gets all the environment types in the environment table
* for each one adds an option tag with the value and field set as that environment's
* type.  Also begins by adding a blank option, so we are not forced to select an
* environment type.  Should be called within a select tag
* Preconditions: called inside of a select tag
* Postconditions: options of environment types output
* **************************************/

function get_enviros() {
    $conn = open_connection();
    $query = "SELECT id, type FROM `environment` ORDER BY type";
    $result = mysqli_query($conn, $query);
    if(mysqli_num_rows($result) > 0){
        printf("<option value='-1'></option>");
        while($row = $result->fetch_assoc()){
            printf("<option value='%d'>%s</option>", $row['id'], $row['type']);
        }
    }
}

/****************************************
* Function: get_sym_names()
* Description: gets all the possible symptom names currently contained inside of the
* symptom table.  Unlike the above functions this one generates the select tag for you.
* Generates a table where each row has an id unique to that row. A select with options
* of the symptom names where value is set to that symptom's name.  THen that row also
* has a series of radio buttons with values from 1 to 5 that are matched to that
* symptom.
* Parameters: none
* Preconditions: symptom table exists
* Postconditions: table of symptoms with optional severities
* **************************************/

function get_symp_names() {

    $conn = open_connection();
    $query = "SELECT id, name FROM `symptom` ORDER BY name";
    $result = mysqli_query($conn, $query);
    $numRows = mysqli_num_rows($result);
    //this loop will create the number of rows as there are the number symptoms
    // so if there are 5 symptoms stored in the table this will output 5 rows
    // each select option will contain all of the possible symptoms. Hence we
    // requery the same statment as below, so we can iterate through the result
    // until we've generated the correct number of rows initially returned above.
    if($numRows > 0){
        //generate the select statement with the symptom names as options
        for($i = 0; $i < $numRows; $i++){
            echo '<tr id="r'.$i.'">';
            echo '<td>';
            echo '<label for="sym'.$i.'">Choose Symptom: </label>';
            echo '<select id="sym'.$i.'" name="sym[]">';
            while($row = $result->fetch_assoc()){
                printf("<option value='%d'>%s</option>", $row['id'], $row['name']);
            }
            echo '</select>';
            echo '</td>';
        //generate a new cell that is a series of radio button assigned to this
        //select field with values from 1 - 5
            echo '<td>';
            echo '<label for="sev'.$i.'">Select severity: </label>';
            for($j = 1; $j < 6; $j++){
                echo '<input type="radio" name="sev'.$i.'" value="'.$j.'">'.$j.'';
            }
            echo '</td>';
            echo '</tr>';
            //perform the query again so we go back to the beginning of the list
            $result = mysqli_query($conn, $query);
        }
    }

    close_connection($conn);

}

function get_symp_names_filter() {
    $conn = open_connection();

    if(!($sql = $conn->prepare("SELECT id, name FROM `symptom` ORDER BY name"))){
        echo "prepare failed";
    }

    if(!($sql->execute())){
        echo "execute failed";
    }
    
    if(!($sql->bind_result($id, $name))){
        echo "bind result failed";
    }

    while($sql->fetch()){
        echo "<option value='".$id."'>".$name."</option>";
    }
    
    $sql->close();
    close_connection($conn);
}


/****************************************
* Function: get_table_names()
* Description: from the DB pikelj-db, gets all the table names that exist
* outputs each of the table names as a new option with that table's name as the 
* value.  Must be called within a select tag.
* Parameters: none
* Preconditions: called inside a select tag.
* Postconditions: table names in db output as options
* **************************************/
function get_table_names() {

    $conn = open_connection();

    $query = "SELECT table_name AS 'tn'
                FROM information_schema.tables 
                WHERE table_schema = 'pikelj-db'";

    $result = mysqli_query($conn, $query);
    if(mysqli_num_rows($result) > 0){
        while($row = $result->fetch_assoc()){
            printf("<option value='%s'>%s</option>", $row['tn'], $row['tn']);
        }
    }

    close_connection($conn);
}

/****************************************
* Function: make_markers
* Description: creates a new xml document that will be output to the current page
* the query t the database, joins all the currently avaiable tables.  We use inner
* join with table checkin so we only get those people with actual checkins.  Otherwise
* we use left join on the remaining tables so we don't exclude any checkins that do
* not have symptoms, environments, of people with out date of births.
* A checkin can have multiple symptoms so we perform the group_concatanate for that
* checkin_id so we can combine all the symptoms and severities for a given checkin.
* Also we want to concatanate the birthday stored into some thing that is human
* readble.
* Parameters: none
* Preconditions: none
* Postconditions: xml document generated with markers
* Reference: this code is directly from
* https://developers.google.com/maps/documentation/javascript/mysql-to-maps
* **************************************/

function make_markers() {
    
    $doc = new DOMDocument("1.0");
    $node = $doc->createElement("markers");
    $parnode = $doc->appendChild($node);

    $conn = open_connection();
    //Our large select statment
    //So we want join all the tables but we do not want to exclude a particular checkin
    // if it does not have an environment or symptoms so we make the left join
    // also if a person does does not have a dateofbirth we don't want to exclude that
    // person.  But we do only want to join people with their checkin if they have a
    // a checkin.  So a person in the table people without a checkin wil not be
    // returned.
    if(!($query = $conn->prepare("SELECT DISTINCT
                checkin.id, nickname, latitude, longitude, tstamp,  
                concat(month, '/', day, '/', year) AS dob, type, quantity, 
                group_concat(' ', name, ' (', severity, ')') as feels
                FROM `people`
                INNER JOIN `checkin` ON people.id = checkin.pid
                LEFT JOIN `dateofbirth` on people.dob_id = dateofbirth.id
                LEFT JOIN `environment` ON checkin.eid = environment.id
                LEFT JOIN `checkin_symptoms` cs ON checkin.id = cs.checkin_id
                LEFT JOIN `symptom` ON cs.symptom_id = symptom.id 
                GROUP BY checkin.id"))){
        echo "prepare failed for map";
    }

    if(!($query->execute())){
        echo "execute failed for map";
    }
    
    if(!($query->bind_result($id, $name, $lat, $lng, $tstamp, 
                             $dob, $type, $qty, $feels))){
        echo "bind results failed for map";
    }

    //write each of the fields per row returned as a new attribute in our
    //xml document as a marker.
    //This will be fetched by the initMap function that populates the google map
    while($query->fetch()){
        $node = $doc->createElement("marker");
        $newnode = $parnode->appendChild($node);

        $newnode->setAttribute("nickname", $name);
        $newnode->setAttribute("lat", $lat);
        $newnode->setAttribute("lng", $lng);
        $newnode->setAttribute("tstamp", $tstamp);
        $newnode->setAttribute("dob", $dob);
        $newnode->setAttribute("type", $type);
        $newnode->setAttribute("qty", $qty);
        $newnode->setAttribute("feels", $feels);
    }
    
    echo $doc->saveXML();
    $query->close();
    close_connection($conn);
}

?>
