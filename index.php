<?php
/* infilare prima dell' header */

    if($_SERVER["REQUEST_METHOD"]!="POST"){
        header("Location : /"); /*inserire la location adeguata*/ 
    }else{
        $dbconnect = pg_connect("host=localhost port=5433 dbname=Automarket user=postgres password=1234");
    }

/* infilare nel header */

    if($dbconnect){
        $email = $_POST=['email'];
        $query_email = 'SELECT * FROM' /* INSERIRE CON LA TABELLA ADEGUATA */;
        $risultato_query_email=pg_query_params($dbconnect, $query_email, array($email));
        if(){}  /* inserire controllo che l'email sia dentro il database*/;
        $username= $_POST[];
    
    }


?>