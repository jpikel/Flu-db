<!-- Filename: fludb.php
    Author: Johannes Pikel
    ONID: pikelj
    Date: 2017.02.08
    email: pikelj@oregonstate.edu
    Class: CS340-400
    Assignment: Final project.  A webpage that interacts with a mysql database
    This is the main page for the web site that holds all the input forms, search form,
    delete form and filter options. 
    Due Date: 2017.03.19
-->

<?php ini_set('display_errors', 'On'); ?>
<?php include 'utility.php' ?>

<html>
    <head>
        <meta charset="utf-8">
        <link rel="stylesheet" href="style.css" type="text/css">
        <title>Flu Database</title>
    </head>

    <body>
        <h4>
            <div class="serverNote">
            Welcome to the fludb.  This idea behind this database is to track the 
            potential movement of the flu virus through a season.  People are entered
            in just as a nickname and email address.  Their Birthday's are stored for
            possible future metrics.  Email and birthday's are not required.
            A person can check in at a given location with any number of symptoms, that
            have a particular severity.  Thus eventually a temporal map could be created
            from the data of location, symptoms, severity and the type of environment
            that "check-in" location was like.
            <br>
            <span class="redtext">*</span> are required.
            </div>
        </h4>
         <!-- This field has only one hidden field that is missing a value.  From the
            script.js each of these buttons is given a 'click' event so that it changes
            the value of the hidden field prior to submitting.  So far it has worked
            -->
        <div class="note">
            From here you may select an option just to view the entire contents of
            the tables contained in the fludb.
        </div>

        <form action="process.php" method="post">
            <input type="hidden" id="showWhat" name="action">
            <input type="submit" id="ShowPeopleBtn" name="submit" value="Show People">
            <input type="submit" id="ShowBdayBtn" name="submit" value="Show Birthdays">
            <input type="submit" id="ShowSympBtn" name="submit" value="Show Symptoms">
            <input type="submit" id="ShowEnvBtn" name="submit" value="Show Environments">
            <input type="submit" id="ShowChkBtn" name="submit" value="Show Checkins">
            <input type="submit" id="ShowChkSym" name="submit" value="Checkin_symptom">
        </form>
        
        <div class="note">
            In these first two forms you may enter a new person with a birthday or just
            a new birthday.
            If that birthday exists in the dateofbirth table then the ID 
            associated with that row will be looked up and added to the person's
            row.  If that particular birthday does not exist yet it will be added
            to the dateofbirth table and the new ID will be added to the person's
            row. After either a person has been sucessfully entered or a birthday
            has been entered it will show the table of all the current rows for
            that particular table. Nickname's are unique.
        </div>
        <!-- Form: input new person
            Description: This form will allow the entry of a new person into the
            flu database.  Do note that the nickname is a unique key.
            -->
        <fieldset> <!--input person form -->
            <legend class="inputLegend">Input new person</legend>
            <form class="inputForm" action="process.php" method="post">
                <input type="hidden" name="action" value="addperson">
                <span class="redtext">*</span>
                <label for="nickname">Nickname:</label>
                    <input type="text" name="nickname" required>
                <label for="email">Email:</label>
                    <input type="email" name="email">
                <label for="birthday">Choose birthday:</label>
                    <input type="date" name="birthday">
                <input type="submit" id="AddPerson" name="submit" value="Add Person">
            </form>
        </fieldset>

        <!-- Form: this is the delete form.  This form will allowed for the deletion of
            a person from the table and should cascade through other tables deleting
            a person's checkins and their checkin_symptom records-->

        <div class="note">From here you may delete a nickname from the people table.
            This should also cascade into the checkin and the checkin_symptoms tables.
            So any reference to this persons id will be gone. Also this will check if
            anyone else in the people table has the same birthday or if we can delete
            the birthday from the dateofbirth table as well.
        </div>

        <fieldset>
            <legend class="inputLegend">Delete a person (e.g. nickname)</legend>
            <form class="inputForm" action="process.php" method="post">
                <input type="hidden" name="action" value="deleteperson">
                <span class="redtext">*</span>
                <label for="delperson">
                    <select name="delperson" id="delperson">
                        <?php get_nicknames(); ?>
                    </select>
                <input type="submit" id="DelPer" name="submit" value="Delete Person">
            </form>
        </fieldset>

        <!-- Form:input new birthday form
            Description: allow the entry of a new date as a birthday into the database
            each date is considered unique and can only exists once in the database
            -->
        <fieldset> <!--input birthday form -->
            <legend class="inputLegend">Add just a new birthday</legend>
            <form class="inputForm" action="process.php" method="post">
                <input type="hidden" name="action" value="addbirthday">
                <span class="redtext">*</span>
                <label for="birthday">Choose birthday:</label>
                    <input type="date" name="birthday" required>
                <input type="submit" id="AddBirthday" name="submit" value="Add Birthday">
            </form>
        </fieldset>

        <div class="note">
            Symptoms should be flu-like symptoms or similar.  There are several already
            added to the database.  If you would like to find more potential adds please
            go here 
            <a href="http://www.webmd.com/cold-and-flu/flu-guide/is-it-cold-flu" 
                target="_blank">Webmd</a>. Symptom names are unique.
        </div>
        <!-- Form: input form to add new flu-like symptoms to the database
             Description: note that each flu-like symptom is considered unique
            -->

        <fieldset>
            <legend class="inputLegend">Input new symptom</legend>
            <form class="inputForm" action="process.php" method="post">
                <input type="hidden" name="action" value="addsymptom">
                <span class="redtext">*</span>
                <label for="symptom">Symptom Name:</label>
                    <input type="text" name="symptom" id="symptom" required>
                <input type="submit" id="AddSymptom" name="submit" value="Add Symptom">
            </form>
        </fieldset>

        <!-- Form: input a new environment.
            Description: Environments are things like airport, subway, home, outside
            etc.  Each Environment is unqiue in this table

        -->
        <div class="note">
            Environments have a type such as "airport", "subway", "street", etc.
            Note that each environment is unique in the table so we may not have
            duplicates.
        </div>
        <fieldset>
            <legend class="inputLegend">Input new environment</legend>
            <form class="inputForm" action="process.php" method="post">
                <input type="hidden" name="action" value="addenvironment">
                <span class="redtext">*</span>
                <label for="envirotype">Environment type:</label>
                    <input type="text" name="envirotype" id="envirotype" required>
                <input type="submit" id="AddEnvir" name="submit" value="Add Environment">
            </form>
        </fieldset>

            <!-- Form: look up an address lat and lng
             Description: This form allows a user to look up a latitude and longitude
             as returned from the google maps api.
            -->
        <div class="note">
            To populate the latitude and longitude in the Input new checkin form
            enter an address or a partial address here and google maps will return a
            latitude and longitude into your fields below. i.e. "The Vortex" returns
            a great burger restaurant with the Coronary Burger...the buns are grilled
            cheese sandwiches. Or search for 215 SW 5th St, Corvallis
        </div>    
        <fieldset>
            <legend class="inputLegend">Find your latitude and longitude</legend>
            <form class="inputForm" id="addressLookUp" name="addressForm">
                <span class="redtext">*</span>
                <label for="address">Enter your address: </label>
                    <input type="text" name="addressInput" id="addressInput" required>
                <button id="convertAddressBtn" value="Convert">Convert</button>
                <div class="serverNote">
                    <span id="waitStmt"></span>
                </div>
                <div class="note">
                    This makes a call to a node.js server that will return a
                    string that can be parsed into an object which contains 
                     the latitude and longitude of the given location. Then it will
                    insert those values into the fields below in the checkin form.
                    This is reliant on the Google Maps API.
                </div>
            </form>
        </fieldset>

         <!-- Form: input a new checkin
             Description: Checkins are where a particular user checks in at given a
            latitude and longitude.  Also a timestamp is written to this particular 
            checkin. The quantity attribute is for the number of people in that area.
            An environment may be selected from the environment table.  Only the lat 
            and long are required.
            -->
         <div class="note">
            A checkin has a latitude and longitude for the given location. You may
            select an existing nickname from the people table. A timestamp will be added
            to this given entry by the server for tracking.  Please also provide an
            environment type and the number of people around you at the moment. Then 
            The possible symptoms are listed below.  If you would like to add a symptom
            add one above first and come back here.  If you have duplicate symptoms
            the last symptom of the duplicates will be entered into the database.
            Only those symptoms with a severity chosen will be entered in the database.
            If you leave the severity blank that symptom will not be added to the 
            database.
            <p>This form will insert into two tables at once.  It first inserts into
            the checkin table and then adds the corresponding checkin id, the symptom 
            id's and their severities into the checkin_symptoms table</p>
            <p>Severity is on a scale of 1 to 5.</p>
            <ul>
                <li>1 is  mild</li>
                <li>2 is in between</li>
                <li>3 is moderate</li>
                <li>4 is in between</li>
                <li>5 is severe</li>
            </ul>
        </div>

        <fieldset>
            <legend class="inputLegend">Input new checkin</legend>
            <form class="inputForm" action="process.php" method="post">
                <input type="hidden" name="action" value="addcheckin">
                <span class="redtext">*</span>
                <label for="pid">Choose nickname: </label>
                    <select name ="pid" id="pid" required>
                        <?php //creates a select with options of the nicknames
                              get_nicknames();
                        ?>
                    </select>
                <span class="redtext">*</span>
                <label for="lat">Latitude: </label>
                    <input type="number" step="any" name="lat" id="lat" required>
                <span class="redtext">*</span>
                <label for="lng">Longitude: </label>
                    <input type="number" step="any" name="lng" id="lng" required>
                <p></p>
                <label for="environment">Choose environment: </label>
                    <select name="enviro" id="environment">
                        <?php get_enviros(); ?><!--prints option tags to the select-->
                    </select>
                <label for="quantity">People in the area: </label>
                    <select name="quantity" id="quantity">
                        <option value="0"></option>
                        <option value="5"> 5 or less</option>
                        <option value="10">6 - 10</option>
                        <option value="20">11 - 20</option>
                        <option value="30">21 - 30</option>
                        <option value="40">31 - 40</option>
                        <option value="50">41 - 50</option>
                        <option value="51">51 + </option>
                    </select>
                <p></p>
                <table id="symptoms" class="outputTable">
                     <?php get_symp_names(); ?><!-- prints select and options along with
                                                severity range for each -->
                </table>
                <input type="submit" id="AddCheckIn" name="submit" value="Add Checkin">
            </form>
        </fieldset>
        
        <!-- Form: this is the search form 
            From here you may entered a string of text and search the specified table
            that you select from all the tables available for that particular
            string of text -->
        <div class="note">
            This is the search form.  Here you may enter a string and select a table
            from the database.  The string will be compared to all the possible
            columns in that table with column LIKE string OR column LIKE string and so
            forth.
        </div>
        <fieldset>
            <legend class="inputLegend">Search a table</legend>
            <form class="inputForm" action="process.php" method="post">
                <input type="hidden" name="action" value="search">
                <label for="searchstr">Search for </label>
                    <input type="text" name="searchstr">
                <label for="searchtbl"> in this table </label>
                    <select name="searchtbl">
                        <?php get_table_names(); ?>
                    </select>
                <input type="submit" id="searchDB" name="submit" value="Search DB">
            </form>
        </fieldset>

        <!-- form: filter by symptom. This form will allow the user to select a
            particular symptom and see all the related entries in the entire database
            that have that symptom -->
        <fieldset>
            <legend class="inputLegend">Filter by symptom</legend>
            <form class="inputForm" action="process.php" method="post">
                <input type="hidden" name="action" value="filter">
                <label for="filterby">Filter by: </label>
                    <select name="filterby">
                        <?php get_symp_names_filter(); ?>
                    </select>
                <input type="submit" id="filter" name="submit" value="Filter">
            </form>
        </fieldset> 
        

        <!-- Google maps API integration with the website -->
        <!-- this would be a very basic implementation of what the DB could be used
            for.  to map the movement of the flu through time or via a heatmap for
            instance.  This is just a basic example -->
        <div class="note">
            Now see a map of the people and their locations!
        </div>
        <h2>
            <a href="map.php">Map it</a>
        </h2>
        <div class="note"><br></div>
        <script src='script.js'></script>
    </body>
</html>
