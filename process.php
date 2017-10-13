<!--Filename: process.php
    Author: Johannes Pikel
    ONID: pikelj
    Date: 2017.02.08
    email: pikelj@oregonstate.edu
    Class: CS340-400
    Assignment: Final project
    Description: the php page that handles most of input and display features of the
    database.  I purposefully used both OOP and Procedural PHP interface with 
    MYSQLI so that I could learn both syntaxes. Also in some situations I found it
    easier to work with the Procedural because I at times had variable inputs to 
    handle from a form such as the checkin form, where a user can enter zero or more
    symptoms.
-->

<?php ini_set('display_errors', 'On'); ?>


<!--add some header text to this ouput results page and a style sheet-->
<html>
    <head>
        <meta charset="utf-8">
        <link rel="stylesheet" href="style.css" type="text/css">
        <title>Results</title>
    </head>

    <body>
<!--most everything else on this page will be handled by the different functions
    in php.
-->
<?php

/****************************************
* Function: decide()
* Description: When a POST is sent to this page this function
* is called at the end of this file so it will be run before 
* anything else.  Requires that the POST has a variable name 'action'
* from that variable it will switch to the various function to process
* the POST information.  This allows to add an entry to each of the tables
* in the database, view each table, update and delete. Finally prints
* a return link to go back to fludb.php
* This function opens the connection to the pikelj-db database and passes
* the variable to the functions as required.  It also handles closing the 
* connection
* Parameters: $_POST variables
* Preconditions: $_POST variables set from forms in fludb.php
* Postconditions: Correct action taken
****************************************/

function decide() {
    if(isset($_POST['submit'])){
        $conn = open_connection();
//        printf("%s", $_POST['action']);
        //The $_POST['action'] variable is a hidden variable contained
        //in each of the forms in fludb.php so that it can be used here.
        switch($_POST['action']){
            case "addperson":
                add_person($conn); 
                show_persons($conn);
                break;
            case "showpeople":
                show_full_people($conn);
                break;
            case "addbirthday":
                add_birthday($conn);
                show_birthdays($conn);
                break;
            case "showbdays":
                show_birthdays($conn);
                break;
            case "addsymptom":
                add_symptom($conn);
                show_symptoms($conn);
                break;
            case "showsymptoms":
                show_symptoms($conn);
                break;
            case "addenvironment":
                add_environment($conn);
                show_environments($conn);
                break;
            case "showenviros":
                show_environments($conn);
                break;
            case "addcheckin":
                add_checkin($conn);
                break;
            case "showcheckin":
                show_checkin($conn);
                break;
            case "showchksym":
                show_checkin_symptom($conn);
                break;
            case "deleteperson":
                delete_person($conn);
                show_persons($conn);
                break;
            case "search":
                search($conn);
                break;
            case "filter":
                filter($conn);
                break;
        }
        close_connection($conn);
        //Need more testing here but hopefully this will clear out the 
        //contents of the $_POST global variable so if the browsers back
        //button is used and forward button it won't repost the same 
        //information.
        $_POST = array();
    }

    return_link();
}

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
* Function: add_person()
* Description: escapes the values in the $_POST variable for
* nickname, email and the birthday with the explode().  If the 
* 'year' variable is 0 an entry was not received so we do not try to
* enter it into the database.  Otherwise using the add_birthday() check
* if the birthday already exists in the table `dateofbirth` if so it returns
* the ID of the row otherwise it will return the ID of the newly added birthday.
* Then check if the nickname already exists in the DB, if it does do not try to 
* insert it, so we don't increment the ID field.  If the nickname does not
* exist insert the new entry.
* Only nickname is required.
* Parameters: $_POST variables set with nickname, email and birthday
* Preconditions: a valid open DB connection and $_POST
* Postconditions: birthday and new person entered into the DB if required
****************************************/

function add_person($conn){

    //just a pre-formatted div to display some information to the user
    //hopefully escape any dangerous characters in the passed string
    //use explode to divide up the form date passed if it exists
    $nickname = mysqli_real_escape_string($conn, $_POST['nickname']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    if($_POST['birthday'] != 0){
        list($y, $m, $d) = explode('-', $_POST['birthday']);
    }

    //if the year is 0 no date was passed and explode seems to set it to 0
    //in that case do not attempt to enter the dob_id into the table
    //it will create and invalid date of 0/0/0 in the dateofbirth table
    if($_POST['birthday'] != 0){
        $dob_id = add_birthday($conn);
        if(!($sql = $conn->prepare("INSERT INTO `people`".
                                   " (`nickname`,`email`,`dob_id`)".
                                   " VALUES (?, ?, ?)"))){
            prepare_failed($sql); 
        }
        if(!($sql->bind_param('ssi', $nickname, $email, $dob_id))){
            bindp_failed($sql);
        }
    }
    else {
        if(!($sql = $conn->prepare("INSERT INTO `people` (`nickname`, `email`)".
                                  " VALUES (?, ?)"))){
            prepare_failed($sql);
        }
        if(!($sql->bind_param('ss', $nickname, $email))){
            bindp_failed($sql);
        }
    }
   
    //check if the nickname already exists
    if(!($query = $conn->prepare("SELECT nickname FROM `people` 
                                WHERE people.nickname = ?"))){
        prepare_failed($query);
    }
    if(!($query->bind_param('s', $nickname))){
        bindp_failed($query);
    }
    if(!($query->execute())){
        execute_failed($query);
    }
    //so we can check the number of rows returned
    if(!($query->store_result())){
        store_failed($query);
    }
    //if the previous query returned 0 rows we know it does not and we can proceed
    //with the DB INSERT otherwise print a warning statement to the user.
    //This extra step will hopefully keep the id field fro auto_incrementing

    div_server_note();
    if($query->num_rows < 1){
        if($sql->execute()){
            insert_success("Person");
        } else {
            execute_failed($sql);
        }
    } else {
        dup_found("Person");
    }

    $query->close();
    $sql->close();
    //now we'll show a modifed table to the user
    printf("<br>Persons in `people` table so far.</div>");
}

/****************************************
* Function: show_persons()
* Description: Prints a table to the screen of all the people in the `people` table
* and their respective birthdays with a join of the `dateofbirth` table.  We don't 
* need to show the dob_id stored in the `people` table because it would not make a lot
* of sense to the user.  The function only gets called after the add_person() 
* Uses a left join so we make sure to show all the people in `people` table even if 
* they do not have a valid birthday entered.
* Parameters: valid mySQL connection
* Preconditions: `people` table and `dateofbirth` table exits
* Postconditions: a table is shown on screen of their contents.
****************************************/

function show_persons($conn){
    //create a table header for our modified query, where we will join with the
    //`dateofbirth` table.
    echo "<table id='outputTable'>
                <thead>
                    <th>nickname</th>
                    <th>email</th>
                    <th>birthday</th>
                </thead>";

    //use a LEFT JOIN so we get all the people even if they do not have a birthday
    if(!($query = $conn->prepare("SELECT nickname, email, month, day, year ".
                             "FROM `people` LEFT JOIN ".
                             "`dateofbirth` ON people.dob_id = dateofbirth.id"))){
        prepare_failed($query);
    }
    if(!($query->execute())){
        execute_failed($query);
    }
    if(!($query->store_result())){
        store_failed($query);
    }
    if(!($query->bind_result($nickname, $email, $month, $day, $year))){
        bindr_failed($query);
    }

    //if we get results print them in the desired format
    //we'll join the separate day, month, year from the `dateofbirth` table into
    //a single cell as the birthday
    if($query->num_rows > 0) {
        while($query->fetch()){
            echo "<tr><td>".$nickname."</td><td>".$email."</td><td>";
            if($month || $day || $year){
                echo $month." / ".$day." / ".$year."</td></tr>";
            } 
            else {
                echo "</td></tr>";
            }
        }
    }
    $query->close();
    

    printf("</table>");
}

/****************************************
* Function: show_full_people()
* Description: Creates a query to `people` table that collects all the information
* displays the table as a raw table.  Uses the build_table_header function to construct
* the header of the html table from the column names of the table.
* In this case we'll display the dob_id that references the row id in the `dateofbirth`
* table and not the actual date of birthday saved so we can see what is saved in the
* table.
* Parameters: valid database connection to pikelj-db
* Preconditions: `people` table exists, valid db connection
* Postconditions: `people` table connects shown on screen
****************************************/

function show_full_people($conn){
    echo "<div class='note'>`people` table:</div>";

    //be explicit about what we want to select, could use * since we want everything
    if(!($query = $conn->prepare("SELECT id, nickname, email, dob_id FROM `people`"))){
        prepare_failed($query);
    }
    if(!($query->execute())){
        execute_failed($query);
    }
    if(!($query->store_result())){
        store_failed($query);
    }
    if(!($query->bind_result($id, $nickname, $email, $dob_id))){
        bindr_failed($query);
    }

    //if we get results, iterate through each of the rows and 
    //construct a new row and cell for that particular row
    if($query->num_rows > 0) {
        build_table_header("people", $conn);
        while($query->fetch()){
            echo "<tr><td>".$id."</td><td>".$nickname."</td><td>".$email."</td>".
                 "<td>".$dob_id."</td></tr>";
        }
    } else {
        table_empty();
    }

    $query->close();
    printf("</table>");
}

/****************************************
* Function: build_table_header()
* Description: Using the passed in table name and DB connection, queries that
* particular table to retrieve the column names in a result.  Then each column in 
* order is a row.  Construct an html table header for each row's COLUMN_NAME.
* Uses the mySQL INFORMATION_SCHEMA to collect the COLUMN names for the table.
* Parameters: valid table name and DB connnection
* Preconditions: table and db exist
* Postconditions: html table header created with the column names as <thead>
****************************************/

function build_table_header($table_name, $conn){
    //get the COLUMN NAMES of the particular table passed in
    //the result will be formatted such that each COLUMN NAME is
    //in it's individual row under the COLUMN_NAME field.
    if(!($query = $conn->prepare("SELECT `COLUMN_NAME` ".
            "FROM `INFORMATION_SCHEMA`.`COLUMNS` ".
            "WHERE `TABLE_SCHEMA`='fludb' ".
            "AND `TABLE_NAME`=? "))){
        prepare_failed($query);
    }

    if(!($query->bind_param('s', $table_name))){
        bindp_failed($query);
    }

    if(!($query->execute())){
        execute_failed($query);
    }

    if(!($query->store_result())){
        store_failed($query);
    }

    if(!($query->bind_result($column_name))){
        bindr_failed($query);
    }

    if($query->num_rows > 0) {
        printf("<table id='outputTable'><thead>");
        while($query->fetch()){
            echo "<th>".$column_name."</th>";
        }
        printf("</thead>");
    }
}

/****************************************
* Function: add_birthday()
* Description: First query the `dateofbirth` table to check if the birthday already
* exists. If it does do not attempt to INSERT it.  If it does not, attempt to insert
* the new birthday in the table.  
* Parameters: Valid DB connection
* Preconditions: `dateofbirth` table exists in the DB
* Postconditions: new birthday entered if necessary
                    returns the `id` of the birthday existing or newly entered
****************************************/

function add_birthday($conn){

    div_server_note();
    //explode the contents of the variable in post, so we can isolate the
    //month, day  and year for entry into the table
    list($y, $m, $d) = explode('-', $_POST['birthday']);

    //query the table to see if this particular date already exists
    if(!($query = $conn->prepare("SELECT `id` ".
             "FROM `dateofbirth` WHERE year=? AND month=? AND day=?"))){
        prepare_failed($query);
    }

    if(!($query->bind_param('iii', $y, $m, $d))){
        bindp_failed($query);
    }

    if(!($query->execute())){
        execute_failed($query);
    }

    if(!($query->store_result())){
        store_failed($query);
    }

    //if our result has a row in it we know it exists so the dob_id is the id of that
    //row and return this value.
    //only used when it is called from add_person otherwise the value is returned and
    //ignored
    if($query->num_rows > 0){
        if(!($query->bind_result($id))){
            bindr_failed($query);
        }
        if($query->fetch()){
            $dob_id = $id;
            dup_found("Birthday"); 
        }
    //if don't get results back we will add it and then our dob_id is that of the
    //newly entered row.
    } else {
        if(!($sql = $conn->prepare("INSERT INTO `dateofbirth` (`year`, `month`, `day`)".
                                   "VALUES (?, ?, ?)"))){
            prepare_failed($sql);
        }
        if(!($sql->bind_param('iii', $y, $m, $d))){
            bindp_failed($sql);
        }
        if(!($sql->execute())){
            execute_failed($sql);
        }
        else {
            $dob_id = $sql->insert_id;
            insert_success("Birthday");
        }
        
        $sql->close();
   }

    $query->close();

    printf("</div>");
    return $dob_id;
}

/****************************************
* Function: show_birthdays
* Description: Queries the dateofbirth table for it's entire contents and orders it
* by year, then month and finally day.  This should give the results in ascending order.
* so Oldest date first, in this case oldest birthday first
* Parameters: open connection to the DB
* Preconditions: `dateofbirth` table exists
* Postconditions: contents of dateofbirth on screen
****************************************/

function show_birthdays($conn){
    printf("<div class='note'>`dateofbirth` table:</div>");

    //make our query explicit to gather all the fields of our table
    //order by year, month and day
    if(!($query = $conn->prepare("SELECT id, month, day, year FROM `dateofbirth` 
                                ORDER BY year, month, day"))){
        prepare_failed($query);
    }
    if(!($query->execute())){
        execute_failed($query);
    }
    if(!($query->store_result())){
        store_failed($query);
    }
    if(!($query->bind_result($id, $month, $day, $year))){
        bindr_failed($query);
    }

    //if we have results print them here
    //otherwise print that the table is empty.
    if($query->num_rows > 0) {
        //build the headers of our table with this function 
        build_table_header("dateofbirth", $conn);
        while($query->fetch()){
            printf("<tr><td>%s</td>", $id);
            printf("<td>%s</td>", $month);
            printf("<td>%s</td>", $day);
            printf("<td>%s</td></tr>", $year);
        }
    } else {
        table_empty();
    }

    $query->close();
    printf("</table>");
}

/****************************************
* Function: add_symptom()
* Description: Attempts to add a new symptom's name ot the symptom table
* first checks if the name already exits and warns the user if it does and does nothing.
* Otherwise attempts to insert the symptom into the `symptom` table.
* Each `symptoms` name is unique.
* Parameters: open connection to db
* Preconditions: `symptom` tables exists in DB
* Postconditions: New symptom added if allowed
****************************************/

function add_symptom($conn){
    div_server_note();
    //try to clean-up any dangerous characters to be treated like text
    $symptom = mysqli_real_escape_string($conn, $_POST['symptom']);

    //check if the name already exists in our table
    if(!($query = $conn->prepare("SELECT name FROM `symptom`".
                                 "WHERE symptom.name=?"))){
        prepare_failed($query);
    }

    if(!($query->bind_param('s', $symptom))){
        bindp_failed($query);
    }

    if(!($query->execute())){
        execute_failed($query);
    }
    
    if(!($query->store_result())){
        store_failed($query);
    }

    //if we get back 0 rows then we can attempt to insert it
    //otherwise print warning that duplicate exists
    if($query->num_rows < 1){
        if(!($sql = $conn->prepare("INSERT INTO `symptom` (`name`) VALUES (?)"))){
            prepare_failed($sql);
        }
        if(!($sql->bind_param('s', $symptom))){
            bindp_failed($sql);
        }
        if(!($sql->execute())){
            execute_failed($sql);
        }
        else {
            insert_success("Symptom");
        } 
        $sql->close();
        
    } else {
        dup_found("Symptom"); 
    }
    
    $query->close();
    printf("<br>Symptoms entered so far.</div>"); 
}

    

/****************************************
* Function: show_symptoms()
* Description: Makes a query to the `symptom` table and collects all the 
* rows from that table.  prints to screen the contents of the `symptom` table
* Parameters: open DB connection
* Preconditions: `symptom` table exists and connection to DB is open
* Postconditions: contents of `symptom` table displayed
****************************************/

function show_symptoms($conn){
    printf("<div class='note'>`symptom` table:</div>");

    if(!($query = $conn->prepare("SELECT id, name FROM `symptom` ORDER BY id"))){
        prepare_failed($query);
    }
    if(!($query->execute())){
        exeucte_failed($query);
    }
    if(!($query->store_result())){
        store_failed($query);
    }
    if(!($query->bind_result($id, $name))){
        bindr_failed($query);
    }

    if($query->num_rows > 0) {
        build_table_header("symptom", $conn);
        while($query->fetch()){
            printf("<tr><td>%s</td>", $id);
            printf("<td>%s</td></tr>", $name);
        }
    } else {
        table_empty();
    }

    $query->close();
    printf("</table>");
}

/****************************************
* Function: add_environment()
* Description: Attempts to add a type of environment to the environment table
* If a duplicates exists it aborts the insert function before attempting.  Do
* this by querying the table first to see if at least one row is returned from the 
* entry.
* Parameters: connection to db
* Preconditions: valid connection, and environment table exists
* Postconditions: if not duplicate entry added to table
****************************************/

function add_environment($conn){
    div_server_note();
    //try to clean-up any dangerous characters to be treated like text
    $environment = mysqli_real_escape_string($conn, $_POST['envirotype']);

    //check if the name already exists in our table
    if(!($query = $conn->prepare("SELECT type FROM `environment` ".
                                 " WHERE environment.type=?"))){
        prepare_failed($query);
    }
    if(!($query->bind_param('s', $environment))){
        bindp_failed($query);
    }

    if(!($query->execute())){
        execute_failed($query);
    }
    if(!($query->store_result())){
        store_failed($query);
    }
    //if we get back 0 rows then we can attempt to insert it
    //otherwise print warning that duplicate exists
    if($query->num_rows < 1){
        if(!($sql = $conn->prepare("INSERT INTO`environment` (`type`) VALUES (?)"))){
            prepare_failed($sql);
        }
        if(!($sql->bind_param('s', $environment))){
            bindp_failed($sql);
        }
        if(!($sql->execute())){
            execute_failed($sql);
        }
        else {
            insert_success("Environment");
        }

        $sql->close();
    } else {
        dup_found("Environment");
    }
    $query->close();
   printf("<br>Environments entered so far.</div>"); 


}

/****************************************
* Function: show_environments()
* Description: Displays the full contents of the environment table
* iterates through the rows if any and adds them to the html table
* Parameters: valid connection to the table 
* Preconditions: `environment` table exists in DB
* Postconditions: contents of table displayed on screen
****************************************/

function show_environments($conn) {
    printf("<div class='note'>`environment` table:</div>");

    if(!($query = $conn->prepare("SELECT id, type FROM `environment` ORDER BY type"))){
        prepare_failed($query);
    }
    if(!($query->execute())){
        execute_failed($query);
    }
    if(!($query->store_result())){
        store_failed($query);
    }
    if(!($query->bind_result($id, $type))){
        bindr_failed($query);
    }

    if($query->num_rows > 0) {
        build_table_header("environment", $conn);
        while($query->fetch()){
            printf("<tr><td>%s</td>", $id);
            printf("<td>%s</td></tr>", $type);
        }
    } else {
        table_empty();
    }

    $query->close();
    printf("</table>");


}

/****************************************
* Function: add_checkin
* Description: Collects the information from the input new checkin form and 
* only collects those fields that have been set.  Identifies the person's id
* as well as the enviroment ID with select tables from their respective tables.
* We know these exist because the input form was limited to only those that are
* currently existing in their respective table.  Thus we can assume we'll return 
* 1 row only.  Since each person's nickname and each environment is unique.  
* THen we insert the checkin to the table.  Assuming this was a success.  We get
* the insert_id that will return the AUTO_Increment and pass it as well as the
* connection to add_checkin_symptoms.  This will add the symptoms and their 
* severities to the many to many table called check_symptoms.
* Parameters: valid mysql connection to db
* Preconditions: input form completed with required data
* Postconditions: checkin added to db
* **************************************/

function add_checkin($conn) {
    
    div_server_note();
    $lat = mysqli_real_escape_string($conn, $_POST['lat']);
    $lng = mysqli_real_escape_string($conn, $_POST['lng']);
    $qty = mysqli_real_escape_string($conn, $_POST['quantity']);
    $pid = mysqli_real_escape_string($conn, $_POST['pid']);
    $eid = mysqli_real_escape_string($conn, $_POST['enviro']);

    //the blank option has a value of 0 on the form so if we get this we insert NULL
    if($qty == 0 )
        $qty = NULL;    
    //similarly if we receive -1 then we know the environment was not selected hence
    //insert NULL
    if($eid == -1) {
        $eid = NULL;
    }

    /* insert a new checkin here*/
    /* if successful we'll also add to the check_symptom table */
    /* in this case pid, lat and lng are all required, but qty and eid are not */
    /* so we do not quote them so we can enter the NULL */
    if(!($stmt = $conn->prepare("INSERT INTO `checkin` ".
                "(`pid`, `latitude`, `longitude`, `quantity`, `eid`) VALUES ".
                "(?, ?, ?, ?, ?)"))){
        prepare_failed($stmt);
    }
    if(!($stmt->bind_param('iddii', $pid, $lat, $lng, $qty, $eid))){
        bindp_failed($stmt);
    }
    if(!($stmt->execute())){
        execute_failed($stmt);
    }
    else {
        insert_success("Checkin");
        echo '<br>';
        add_checkin_symptoms($conn, $stmt->insert_id);
    }

    $stmt->close();
    printf("</div>");
}

/****************************************
* Function: add_check_symptoms
* Parameters: connection to db, row_id of the checkin added
* Description: First we loop through the input fields received from the form
* if there are any duplicates we discard them and only use the last of the duplicate.
* only those symptoms where the corresponding severity has been selected will get added
* to the table.
* Then we add entries for each symptom's id, severity and relate it to the row_id of the
* checkin.  Thus we can keep track of the many symptoms to many check_ins relationship.
* Preconditions: get a check_in id
* Postconditions: symptoms added for the check_in
* Note: I used procedural mysqli here because I found it easier with the variable
* number of possible symptoms that may be input by the user.
* **************************************/

function add_checkin_symptoms($conn, $rowID){

    $stmt = "INSERT INTO `checkin_symptoms` (`checkin_id`, `symptom_id`, `severity` )
             VALUES (";

    /* loop through the inputs received and only added those that are not duplicated */
    /* if we find a duplicate the last one in the list will be the one added  to our */
    /* insert statement */
    $okToInsert = False;
    for($i = 0; $i < count($_POST['sym']); $i++){
        if( isset($_POST['sev'.$i])){
            $duplicate = False;
            $okToInsert = True; /* there is at least one symptom! */
            for($j = $i+1; $j < count($_POST['sym']); $j++){
                if( isset($_POST['sev'.$j])){
                    if($_POST['sym'][$i] == $_POST['sym'][$j]){
                        $duplicate = True;
                    }
                }
            }

            if($duplicate == False){
                /* echo $_POST['sym'][$i];*/
                $stmt .= '\''.$rowID.'\',';
                $sid = mysqli_real_escape_string($conn, $_POST['sym'][$i]);    
                $stmt .= '\''.$sid.'\',';
                $sev = mysqli_real_escape_string($conn, $_POST['sev'.$i]);
                $stmt .= '\''.$sev.'\'), (';
            }
        }
    }

    /* truncate the line so we get rid of that trailing , ( */
    /* then attempt to add all these to the check_symptom table  as one INSERT */
    /* statement for multiple value */
    $stmt = substr($stmt, 0, -3);

    if($okToInsert == True){
        if(mysqli_query($conn, $stmt)){
            insert_success("Checkin symptoms");
        } else {
            gen_error($conn);
        }
    }
    else {
        printf("No sysmptoms to insert");
    }

    echo '</div>';

}

/****************************************
* Function: show_checkin_symptom
* Parameters: connection to database
* Description: Selects all the columns from the table checkin_symptom and 
* creates a table on the page 
* Preconditions: check_symptom table exists
* Postconditions: table shown on page
* **************************************/

function show_checkin_symptom($conn){
    printf("<div class='note'>`checkin` table:</div>");

    if(!($query = $conn->prepare("SELECT checkin_id, symptom_id, severity 
                                FROM `checkin_symptoms`"))){
        prepare_failed($query);
    }
    if(!($query->execute())){
        execute_failed($query);
    }
    if(!($query->store_result())){
        store_failed($query);
    }
    if(!($query->bind_result($checkin_id, $symptom_id, $severity))){
        bindr_failed($query);
    }

    if($query->num_rows > 0){
        build_table_header("checkin_symptoms", $conn);
        while($query->fetch()){
            echo "<tr><td>".$checkin_id."</td><td>".$symptom_id."</td><td>".
                 $severity."</td></tr>";
        }
    } else {
        table_empty();
    }

    $query->close();
    printf("</table>");
}

/****************************************
* Function: show_checkin()
* Parameters: valid connection to the database
* Description: Selects all the columns from the checkin table and creates a table
* on the page of the contents of the checkin table.
* Preconditions: checkin table exists
* Postconditions: table shown on page
* **************************************/

function show_checkin($conn){
    printf("<div class='note'>`checkin` table:</div>");

    if(!($query = $conn->prepare("SELECT id, pid, latitude, longitude, tstamp, 
                                quantity, eid FROM `checkin`"))){
        prepare_failed($query);
    }
    if(!($query->execute())){
        execute_failed($query);
    }
    if(!($query->store_result())){
        store_failed($query);
    }
    if(!($query->bind_result($id, $pid, $lat, $lng, $tstamp, $qty, $eid))){
        bindr_failed($query);
    }

    if($query->num_rows > 0){
        build_table_header("checkin", $conn);
        while($query->fetch()){
            echo "<tr><td>".$id."</td><td>".$pid."</td><td>".$lat."</td><td>".$lng;
            echo "</td><td>".$tstamp."</td><td>".$qty."</td><td>".$eid."</td></tr>";
        }
    } else {
        table_empty();
    }
    $query->close();
    printf("</table>");
    
}

/****************************************
* Function: delete_person()
* Description: If the form field delperson is set, attempts to remove that person from
* the people table.  Also retrieves that person's dob_id and if no other person has the
* same dob_id, removes that date of birth from the dateofbirth table.  Checkin table
* uses each person's id as a foreign key and cascades on delete so all the related
* checkins and checkin_symptom records related to this person's id are remove as well.
* Parameters: valid connection to database
* Preconditions: person exists in table and given open connection to database
* Postconditions: person deleted from table
* **************************************/

function delete_person($conn){

    div_server_note();

    $pid = mysqli_real_escape_string($conn, $_POST['delperson']);

    /* first look up the dob_id for later use */
    if(!($query = $conn->prepare("SELECT nickname, dob_id FROM `people` WHERE ".
                                    "people.id = ?"))){
        prepare_failed($query);
    }
    if(!($query->bind_param('i', $pid))){
        bindp_failed($query);
    }
    if(!($query->execute())){
        execute_failed($query);
    }
    if(!($query->bind_result($nickname, $dobid))){
        bindr_failed($query);
    }
    if(!($query->store_result())){
        store_failed($query);
    }
    if($query->fetch()) {
        $dob_id = $dobid;
    }
    $query->close();
    /* since all nicknames are unique we'll be explicit here and only delete 1 */
    if(!($stmt = $conn->prepare("DELETE FROM `people` WHERE people.id = ? LIMIT 1"))){
        prepare_failed($stmt);
    }
    if(!($stmt->bind_param('i', $pid))){
        bindp_failed($stmt);
    }
    /* delete this record from the people table */
    if(!($stmt->execute())){
        execute_failed($stmt);
    }
    else { 
        printf("%s has been deleted successfully.\n", $nickname);
    }
    $stmt->close();
    /* now that person is deleted, do another search through the people table */
    /* for any matching dob_id, if we do not find any then the person was the */
    /* to have that dob_id so we can also delete it from the dateofbirth table */
    if(!($query = $conn->prepare("SELECT dob_id FROM `people` ".
                                 "WHERE people.dob_id = ?"))){
        prepare_failed($query);
    }
    if(!($query->bind_param('i', $dob_id))){
        bindp_failed($query);
    }
    if(!($query->execute())){
        execute_failed($query);
    }
    if(!($query->store_result())){
        store_failed($query);
    }
    //finally if we did not find anyone else with the same birthday
    //delete the birthday from the dateofbirth table as well.
    if($query->num_rows == 0){
        if(!($stmt = $conn->prepare("DELETE FROM `dateofbirth` ".
                                    "WHERE dateofbirth.id = ? LIMIT 1"))){
            prepare_failed($stmt);
        }
        if(!($stmt->bind_param('i', $dob_id))){
            bindp_failed($stmt);
        }
        if(!($stmt->execute())){
            execute_failed($stmt);
        }
        else { 
            printf("No one else had the same birthday, so it has been deleted\n");
        }
    
        $stmt->close();
    }

    $query->close();

    printf("</div>");

}

/****************************************
* Function: search()
* Parameters: open connection to database
* Description: given a string and a table in the database
* performs a basic search of the string to match any of the columns in the table
* specified.  If it finds any rows that match then it will create a table and show
* the results on the page.
* Preconditions: valid connection to db
* Postconditions: search results shown on screen
* **************************************/

function search($conn){
    div_server_note();
    echo "<br>";

    $string = mysqli_real_escape_string($conn, $_POST['searchstr']);
    $table = mysqli_real_escape_string($conn, $_POST['searchtbl']);

    printf("Searching for: %s<br>In table: %s<br>", $string, $table); 
    printf("</div>");

    $query = "SELECT * FROM `$table` WHERE ";

    /* first get and store the column names of the chosen table */
    /* these will be used to build the table header and later */
    /* for use when outputting specific information from the table */
    /* the array will be used later */
    $getCol = "SELECT `COLUMN_NAME` ".
            "FROM `INFORMATION_SCHEMA`.`COLUMNS` ".
            "WHERE `TABLE_SCHEMA`='pikelj-db' ".
            "AND `TABLE_NAME`='$table' ";

    /* assuming a valid table was provided we'll create the search string here */
    /* the final search string will be `column_name` LIKE '%string%' OR .... */
    $result = mysqli_query($conn, $getCol);
    /* assuming a valid table was provided build the table headers */
    if(mysqli_num_rows($result) > 0) {
        build_table_header($table, $conn);    
        while($row = $result->fetch_assoc()){
            $query .= $row['COLUMN_NAME'].' LIKE \'%';
            $query .= $string.'%\' OR ';
            $arr[] = $row['COLUMN_NAME']; 
        }
        /* remove the final 4 bytes, including a trailing OR */
        $query = substr($query, 0, -4);
    }

    $result = mysqli_query($conn, $query);

    /* for each result, using the array of stored column names we'll iterate through */
    /* the row and output each column value as a new cell.  Each record is a new table */
    /* row. */
    if(mysqli_num_rows($result) > 0){
        while($row = $result->fetch_assoc()){
            printf("<tr>");
            for($i = 0; $i < count($arr); $i++){
                printf("<td>%s</td>", $row[$arr[$i]]);
            }
            printf("</tr>");
        }

        printf("</table>");
    } 
    else {
        printf("</table>");
        printf("Nothing found or table empty"); 
    }
}

/****************************************
* Function: filter()
* Parameters: valid connection to database
* Description: filters all the tables in the database to only those entries that
* are related to the selected symptom.  Provides the person, their birthday, 
* the checkins they had and the details about the checkin itself
* Preconditions: passed open connection to db 
* Postconditions: filtered results presented in a table
* **************************************/

function filter($conn) {
    $id = mysqli_real_escape_string($conn, $_POST['filterby']);

    if(!($query = $conn->prepare("SELECT DISTINCT c.id, nickname, ".
            "CONCAT(month, ' / ', day, ' / ', year) AS dob, type, quantity, ".
            "name, severity, latitude, longitude, tstamp ".
            "FROM `symptom` s ".
            "INNER JOIN `checkin_symptoms` cs ON s.id = cs.symptom_id ".
            "INNER JOIN `checkin` c ON c.id = cs.checkin_id ".
            "INNER JOIN `people` p ON p.id = c.pid ".
            "LEFT JOIN `environment` e ON e.id = c.eid ".
            "LEFT JOIN `dateofbirth` d ON d.id = p.dob_id ".
            "WHERE s.id = ? ORDER BY tstamp"))){
        prepare_failed($query);
    }

    if(!($query->bind_param('i', $id))){
        bindp_failed($query);
    }
    
    if(!($query->execute())){
        execute_failed($query);
    }

    if(!($query->store_result())){
        store_failed($query);
    }

    if(!($query->bind_result($id, $nick, $dob, $typ, $qty, $sym, 
                             $sev, $lat, $lng, $tstmp))){
        $bindr_failed($query);
    }
    //create the filter table header to output pertinent attributes from the db
    echo "<table id='outputTable'><thead><th>checkin id</th><th>nickname</th>".
        "<th>dob</th><th>type</th><th>quantity</th><th>symptom</th><th>severity</th>".
        "<th>latitude</th><th>longitude</th><th>time stamp</th></thead>";

    //fill the table with information.
    while($query->fetch()){
        echo "<tr><td>".$id."</td><td>".$nick."</td><td>".$dob."</td><td>".
             $typ."</td><td>".$qty."</td><td>".$sym."</td><td>".$sev."</td><td>".
             $lat."</td><td>".$lng."</td><td>".$tstmp."</td></tr>";
    }

    echo "</table>";
    $query->close();

}

/****************************************
* Function: table_empty()
* Description: utility function that prints the table is empty statement
* Parameters: none as of now
* Preconditions: none
* Postcondition: statement shown on screen
****************************************/

function table_empty(){
    printf("<p>Table empty.</p>");
}

/****************************************
* Function: insert_success()
* Description: Writes a message to screen that the particular entity was entered
* successfully into the table.
* Parameters: string as message
* Preconditions: passed a string
* Postconditions: printed to screen
* **************************************/
function insert_success($message) {
    printf("%s entered successfully", $message);
}

/****************************************
* Function: dup_found()
* Description: A general statement that a duplicate was found in the table
* thus the current entry was not entered.
* Parameters: string for message, typically the variable or entity not entered
* Preconditions: none
* Postconditions: message printed to screen
* **************************************/

function dup_found($message) {
    printf("%s was not entered. Duplicate found.", $message);
}

/****************************************
* Function: gen_error()
* Description: writes the error string returned from the database to screen
* Parameters: valid open connection
* Preconditions: open connection
* Postconditions: most recent error written to screen
* **************************************/

function gen_error($conn) {
    printf("Database error: %s", mysqli_error($conn));
}

/****************************************
* Function: div_server_note()
* Description: creates a div element with some basic text
* the calling function needs to close the div when done writting
* to this section
* Parameters: none
* Preconditions: none
* Postconditions: div created of class serverNote
****************************************/

function div_server_note(){
    printf("<div class='serverNote'>DB notes: ");
}
/****************************************
* Function: return_link()
* Description: creates a simple href back to the fludb.php homepage.
* Parameters: none
* Preconditions: none
* Postconditions: link created to fludb.php
****************************************/

function return_link() {
    echo '<br><a href="fludb.php">Back</a>';
}

/****************************************
* Function: prepare_failed()
* Parameters: mysqli object
* Description: writes an error statement for failure of prepare
* Preconditions: passed mysqli object
* Postconditions: writes to page
* **************************************/

function prepare_failed($sql) {
    echo "prepare failed: ".$sql->errno." ".$sql->error;
}

/****************************************
* Function: bindp_failed()
* Parameters: mysqli object
* Description: writes an error statement for failure of bind_param
* Preconditions: passed mysqli object
* Postconditions: writes to page
* **************************************/

function bindp_failed($sql) {
    echo "bind param failed:".$sql->errno." ".$sql->error;
}

/****************************************
* Function: bindr_failed()
* Parameters: mysqli object
* Description: writes an error statement for failure of bind_result
* Preconditions: passed mysqli object
* Postconditions: writes to page
* **************************************/

function bindr_failed($sql) {
    echo "bind result failed:".$sql->errno." ".$sql->error;
}

/****************************************
* Function: execute_failed()
* Parameters: mysqli object
* Description: writes an error statement for failure of execute
* Preconditions: passed mysqli object
* Postconditions: writes to page
* **************************************/

function execute_failed($sql) {
    echo "execute failed: ".$sql->errno." ".$sql->error;
}

/****************************************
* Function: store_failed()
* Parameters: mysqli object
* Description: writes an error statement for failure of store_result
* Preconditions: passed mysqli object
* Postconditions: writes to page
* **************************************/

function store_failed($sql) {
    echo "store result failed: ".$sql->errno." ".$sql->error;
}

//start the entire php function with decide()
//This will route the page to the correct functions for database interaction
decide();

?>


    </body>
</html>
