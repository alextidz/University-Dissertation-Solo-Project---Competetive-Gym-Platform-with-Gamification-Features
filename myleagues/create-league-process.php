<?php

session_start();

// --- CREATE A NEW LEAGUE BASED ON INFORMATION ENTERED BY USER

$data = json_decode(file_get_contents("php://input"), true);    // Receive json data

if (isset($_SESSION["user_id"])) {                      // Check user is set

    // Check name, duration, join code, and exercise and number of reps for each leaderboard are set  
    if (isset($data["name"]) && isset($data["duration"]) && isset($data["code"]) && isset($data["exercise1"]) && isset($data["reps1"]) && isset($data["exercise2"]) && isset($data["reps2"]) && isset($data["exercise3"]) && isset($data["reps3"]) && isset($data["exercise4"]) && isset($data["reps4"]) && isset($data["exercise5"]) && isset($data["reps5"])) {     
        
        $name = $data["name"];                          // Store league name

        $duration = (int) $data["duration"];            // Store duration (used to calculate league end time)
        $currentDate = new DateTime(); 
        $currentDate->add(new DateInterval("P{$duration}M"));       // Add duration to current time to calculate league end time
        $endDate = $currentDate->format('Y-m-d H:i:s');                         // Store this league end time in correct format

        $code = $data["code"];                          // Store league join code
        $creatorId = (int) $_SESSION["user_id"];        // Store current user id as creator id

        $mysqli = require __DIR__ . "/database.php";    // Database connection

        $sql = "INSERT INTO leagues (league_name, creator_id, code, end_date ) VALUES (?, ?, ?, ?)";      // Insert new league info into leagues table
        
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("siss", $name, $creatorId, $code, $endDate);

        if ($stmt->execute()) {

            $leagueId = $mysqli->insert_id;             // Variable containing id of the league just created        

            $sql = "INSERT INTO league_entries (league_id, user_id) VALUES (?, ?)";     // Insert current user id and new league id into league entries table
            $stmt = $mysqli->prepare($sql);
            $stmt->bind_param("ii", $leagueId, $creatorId);

            if ($stmt->execute()) { 

                $exercise1 = $data["exercise1"];        // Variables containing leaderboards' info
                $reps1 = (int) $data["reps1"];
                $exercise2 = $data["exercise2"];
                $reps2 = (int) $data["reps2"];
                $exercise3 = $data["exercise3"];
                $reps3 = (int) $data["reps3"];
                $exercise4 = $data["exercise4"];
                $reps4 = (int) $data["reps4"];
                $exercise5 = $data["exercise5"];
                $reps5 = (int) $data["reps5"];

                // Insert first leaderboard details into league leaderboards table
                $sql1 = "INSERT INTO league_leaderboards (league_id, exercise, num_reps) VALUES (?, ?, ?)";     
                $stmt1 = $mysqli->prepare($sql1);
                $stmt1->bind_param("isi", $leagueId, $exercise1, $reps1);
                $stmt1->execute();
                $leaderboard1Id = $mysqli->insert_id;       // Store first leaderboard id
                $stmt1->close();

                // Insert second leaderboard details into league leaderboards table
                $sql2 = "INSERT INTO league_leaderboards (league_id, exercise, num_reps) VALUES (?, ?, ?)";     
                $stmt2 = $mysqli->prepare($sql2);
                $stmt2->bind_param("isi", $leagueId, $exercise2, $reps2);
                $stmt2->execute();
                $leaderboard2Id = $mysqli->insert_id;       // Store second leaderboard id
                $stmt2->close();

                // Insert third leaderboard details into league leaderboards table
                $sql3 = "INSERT INTO league_leaderboards (league_id, exercise, num_reps) VALUES (?, ?, ?)";     
                $stmt3 = $mysqli->prepare($sql3);
                $stmt3->bind_param("isi", $leagueId, $exercise3, $reps3);
                $stmt3->execute();
                $leaderboard3Id = $mysqli->insert_id;       // Store third leaderboard id
                $stmt3->close();

                // Insert fourth leaderboard details into league leaderboards table
                $sql4 = "INSERT INTO league_leaderboards (league_id, exercise, num_reps) VALUES (?, ?, ?)";     
                $stmt4 = $mysqli->prepare($sql4);
                $stmt4->bind_param("isi", $leagueId, $exercise4, $reps4);
                $stmt4->execute();
                $leaderboard4Id = $mysqli->insert_id;       // Store fourth leaderboard id
                $stmt4->close();

                // Insert fifth leaderboard details into league leaderboards table
                $sql5 = "INSERT INTO league_leaderboards (league_id, exercise, num_reps) VALUES (?, ?, ?)";     
                $stmt5 = $mysqli->prepare($sql5);
                $stmt5->bind_param("isi", $leagueId, $exercise5, $reps5);

                if ($stmt5->execute()) { 

                    $leaderboard5Id = $mysqli->insert_id;   // Store fifth leaderboard id

                    $score = 0;             // Set default entry score and video
                    $video = "null";

                    // Insert user default entry for first leaderboard
                    $sql1 = "INSERT INTO league_leaderboards_entries (leaderboard_id, user_id, score, video) VALUES (?, ?, ?, ?)";     
                    $stmt1 = $mysqli->prepare($sql1);
                    $stmt1->bind_param("iids",$leaderboard1Id, $creatorId, $score, $video);
                    $stmt1->execute();
                    $stmt1->close();

                    // Insert user default entry for second leaderboard
                    $sql2 = "INSERT INTO league_leaderboards_entries (leaderboard_id, user_id, score, video) VALUES (?, ?, ?, ?)";     
                    $stmt2 = $mysqli->prepare($sql2);
                    $stmt2->bind_param("iids",$leaderboard2Id, $creatorId, $score, $video);
                    $stmt2->execute();
                    $stmt2->close();

                    // Insert user default entry for third leaderboard
                    $sql3 = "INSERT INTO league_leaderboards_entries (leaderboard_id, user_id, score, video) VALUES (?, ?, ?, ?)";     
                    $stmt3 = $mysqli->prepare($sql3);
                    $stmt3->bind_param("iids",$leaderboard3Id, $creatorId, $score, $video);
                    $stmt3->execute();
                    $stmt3->close();

                    // Insert user default entry for fourth leaderboard
                    $sql4 = "INSERT INTO league_leaderboards_entries (leaderboard_id, user_id, score, video) VALUES (?, ?, ?, ?)";     
                    $stmt4 = $mysqli->prepare($sql4);
                    $stmt4->bind_param("iids",$leaderboard4Id, $creatorId, $score, $video);
                    $stmt4->execute();
                    $stmt4->close();

                    // Insert user default entry for fifth leaderboard
                    $sql5 = "INSERT INTO league_leaderboards_entries (leaderboard_id, user_id, score, video) VALUES (?, ?, ?, ?)";     
                    $stmt5 = $mysqli->prepare($sql5);
                    $stmt5->bind_param("iids",$leaderboard5Id, $creatorId, $score, $video);

                    if ($stmt5->execute()) { 
                        echo json_encode(["success" => true]);       // Return json indicating league was created successfully
                    } else {
                        echo json_encode(["success" => false, "error" => "Database update failed"]);        // Return json indicating error
                    }

                } else {
                    echo json_encode(["success" => false, "error" => "Database update failed"]);        // Return json indicating error
                }

            } else {
                echo json_encode(["success" => false, "error" => "Database update failed"]);        // Return json indicating error
            }

        } else {
            echo json_encode(["success" => false, "error" => "Database update failed"]);        // Return json indicating error
        }
         
    }

}

// ---