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
    $query = 'SELECT * FROM login_activity ORDER BY login_time ASC';
    $result = mysqli_query($conn, $query);
    
    $user_count = array();
    $haha = 1;
    while($row = mysqli_fetch_assoc($result)) {
        // --- Doing this removes duplicates
        // creating new "total-users" and "new-users" array in user_count and storing user_ids in them
        $user_count[$row['login_time']]['total-users'][$row['user_id']] = $row['user_id'];
        if($row['is_new_user'] == 1){
            $user_count[$row['login_time']]['new-users'][$row['user_id']] = $row['user_id'];
        }
    }

    $dates = array_keys($user_count);

    // user retention over days from the start (x-axis)
    // for every row
    for($i = 0; $i < count($dates); $i++){
        // reset for every row
        $total_user_count_till_date = 0;
        $index = 0;
        // for every column
        for($j = $i; $j < count($dates); $j++){
            if($j == 0){
                $user_count[$dates[$i]]['revisit-percent'][$j] = '<span>assume</span>100';
                $total_user_count_till_date += count($user_count[$dates[$j]]['total-users']);
            } else {
                // total user count till j
                // total_user_count_till_date = total-user on day j - revisits from day j-1
                $total_user_count_till_date += count($user_count[$dates[$j]]['total-users']) - 
                (count($user_count[$dates[$j]]['total-users']) - count(array_diff($user_count[$dates[$j]]['total-users'], $user_count[$dates[$j-1]]['total-users'])));

                // revisits on day j = total users on day j - new users on day j
                $revisit = count($user_count[$dates[$j]]['total-users']) - count($user_count[$dates[$j]]['new-users']);
                
                $revisit_prcnt =  number_format((($revisit / $total_user_count_till_date) * 100), 2, '.', '');
                $user_count[$dates[$i]]['revisit-percent'][$j] = $revisit_prcnt;
            }
        }
    }
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

    span{
        font-size: .5em;
        position: absolute;
        top: 9.5em;
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
                        <td>" . count($user_count[$dates[$i]]['new-users']) . "</td>";
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
