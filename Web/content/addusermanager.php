<?php
session_start();
    if (empty($_SESSION['admin']) || $_SESSION['admin'] !== true) {
        session_destroy();
        header("location: ../index.html");
        exit();
    }

    else if(isset($_POST['uname'], $_POST['pswd'])){
        $servername = "localhost:3306";
        $username = "userlogin";
        $password = "zl*eDJmgT5sQNTuj";
        $db = "usbraidlogin";
        
        $uname = htmlspecialchars($_POST['uname']);
        $pswd = htmlspecialchars($_POST['pswd']);

        $conn = new mysqli($servername, $username, $password, $db);
        $conn->set_charset("utf8");
            
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }
        else {
            $sql = "SELECT * FROM users WHERE uname=?";
            $stmt = $conn->prepare($sql);
        
            $stmt->bind_param("s", $uname);
        
            $stmt->execute();
        
            $result = $stmt->get_result();
        
            if($result && $result->num_rows > 0)
            {
                header("Location: addusererror.html");
                exit;
            } 
            else if($result)
            {
                $sql = "INSERT INTO users(uname, pswd) VALUES (?, ?)";
                $stmt = $conn->prepare($sql);
                
                $hash = password_hash($pswd, PASSWORD_BCRYPT);
    
                $stmt->bind_param("ss", $uname, $hash);
                
                $stmt->execute();
    
                header("Location: addusersuccess.html");
                exit;
            }
            else 
            {
                header("Location: addusererror.html");
                exit;
            }
            $result->free_result();
        }
        $stmt->close();
        $conn->close();
    } 
?>