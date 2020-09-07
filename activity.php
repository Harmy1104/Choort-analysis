<?php

    // --- Defining variables to set sql connection
    $host = 'localhost';
    $db_name = 'login_activity';
    $username = 'root';
    $password = '';

    // --- Connecting to sql on local server.
    $conn = mysqli_connect($host, $username, $password, $db_name);
    // --- Commenting this since it is running on localserver.
    // if($conn->connect_error){
    //     die('Connection Error: ' . $conn->connect_error);
    // }

    // --- Getting dates
    $query = 'SELECT login_time FROM login_activity ORDER BY login_time ASC';
    $result = mysqli_query($conn, $query);
    
    $dates = array();
    while($row = mysqli_fetch_assoc($result)) {
        // --- Doing this removes duplicates
        $dates[$row['login_time']] = $row['login_time'];
        // echo gettype($row['login_time']);
    }
    $dates = array_keys($dates);

    // --- Creating queries for every date to get unique new and total user count
    $user_count = array();
    for($i = 0; $i < count($dates); $i++){
        // --- Creating base query for getting user ids
        $base_query = 'SELECT user_id FROM login_activity WHERE login_time = "' . $dates[$i] . '"';  

        // --- Defining array for a date: [2015-11-23] => Array()
        $user_count[$dates[$i]] = array();

        // --- Counting total unique users on a given date
        $unique_users = array();
        $result = mysqli_query($conn, $base_query);

        while($row = mysqli_fetch_assoc($result)){
            $unique_users[$row['user_id']] = $row['user_id'];
        }
        
        // --- Adding total user count and all user's unique ids in $user-count array
        $user_count[$dates[$i]]['total-users'] = array(
            'count' => count($unique_users),
            'user-ids' => array_keys($unique_users)
        );
        
        // --- Counting total unique new users on a given date
        $unique_users = array();
        // --- Query for new users
        $new_user_query = $base_query . ' AND is_new_user = ' . 1;
        $result = mysqli_query($conn, $new_user_query);

        while($row = mysqli_fetch_assoc($result)){
            $unique_users[$row['user_id']] = $row['user_id'];
        }
        // --- Adding total new user count and new user's unique ids in $user-count array
        $user_count[$dates[$i]]['new-users'] = array(
            'count' => count($unique_users),
            'user-ids' => array_keys($unique_users)
        );
        $user_count[$dates[$i]]['revisit-percent'] = array();
    }
    mysqli_close($conn);

    // user retention over days from the start (x-axis)
    // for every row
    for($i = 0; $i < count($dates); $i++){
        // reset for every row
        $total_user_count_till_date = 0;
        $total_revisit = 0;
        // for every column
        for($j = $i; $j < count($dates); $j++){
            if($j == 0){
                $user_count[$dates[$i]]['revisit-percent'][$j] = '100';
                $total_user_count_till_date += $user_count[$dates[$j]]['total-users']['count'];
            } else {
                // total revisited users till j(th) day. 
                // total_revisited = total_revisited + (total-users at day j - non common users between day j and j-1)
                $total_revisit += $user_count[$dates[$j]]['total-users']['count'] - 
                count(array_diff($user_count[$dates[$j]]['total-users']['user-ids'], $user_count[$dates[$j-1]]['total-users']['user-ids']));
                
                // total user count till j
                // total_user_count_till_date = total-user on day j - revisits from day j-1
                $total_user_count_till_date += $user_count[$dates[$j]]['total-users']['count'] - 
                ($user_count[$dates[$j]]['total-users']['count'] - count(array_diff($user_count[$dates[$j]]['total-users']['user-ids'], $user_count[$dates[$j-1]]['total-users']['user-ids']))); 
                
                $revisit = $user_count[$dates[$j]]['total-users']['count'] - $user_count[$dates[$j]]['new-users']['count'];
                
                $revisit_prcnt =  number_format((($revisit / $total_user_count_till_date) * 100), 2, '.', '');
                $user_count[$dates[$i]]['revisit-percent'][$j] = $revisit_prcnt;
            }
        }
        // echo "---------------\n";
    }

    // print_r($user_count);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="style.css">
</head>

<style>

    *{
        font-family: 'Montserrat', sans-serif;
        color: #ddd;
    }

    body{
        background-color: #002c33;
        margin: 1em;
    }
    
    table{
        border-collapse: collapse;
        background-color: #006a82;
        white-space: nowrap;
    }

    tr, td, th{
        border: 1px solid #efefef;
        padding: 1em 1em;
        text-align: center;
    }
    
    th{
        background-color: #008caf;
    }

</style>

<body>
    <div>
        <table>
            <thead>
                <tr>
                    <th>Cohort</th>
                    <th>New Users</th>
                    <?php 
                        for($i = 0; $i < count($dates); $i++){
                            echo "<th>Day " . ($i+1)  . "</th>";
                        }
                    ?>
                </tr>
            </thead>
            <tbody>
                <?php
                    for($i = 0; $i < count($dates); $i++){
                        echo "<tr>
                        <th>" . $dates[$i] . "</th>
                        <td>" . $user_count[$dates[$i]]['new-users']['count'] . "</td>";
                        for($j = $i; $j < count($dates); $j++){
                            echo "<td>" .
                                $user_count[$dates[$i]]['revisit-percent'][$j]
                            . " %</td>";
                        }
                        echo "</tr>";
                    }
                ?>
            </tbody>
        </table>
    </div>
</body>
</html>
