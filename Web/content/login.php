<?php
    if(isset($_POST['uname'], $_POST['pswd']))
    {
        session_start();
        $servername = "localhost:3306";
        $username = "usermanager";
        $password = "0uhW/5/f8xGT!GSP";
        $db = "usbraidlogin";
        
        $uname = htmlspecialchars($_POST['uname']);
        $pswd = htmlspecialchars($_POST['pswd']);
        

            $conn = new mysqli($servername, $username, $password, $db);
            $conn->set_charset("utf8");
            
            if ($conn->connect_error) 
            {
               die("Connection failed: " . $conn->connect_error);
            }
            else 
            {
                        
                $sql = "SELECT * FROM users WHERE uname=?";
                $stmt = $conn->prepare($sql);
            
                $stmt->bind_param("s", $uname);
            
                $stmt->execute();
            
                $result = $stmt->get_result();
            
                if($result && $result->num_rows > 0)
                {
                    while($row = $result->fetch_assoc()) 
                    {
                        $hash = $row["pswd"];

                        echo "Username: " . $uname . "<br>Password: " . $pswd . "<br>Hash: " . $hash;

                        if(password_verify($pswd, $hash)){
                            $_SESSION['uname'] = $uname;
                            header("Location: FTP.php");
                            exit;
                        } 
                    }
                    header("location: notAuthorized.html");
                    exit;
                } 
                else 
                {
                   header("location: notAuthorized.html");
                   exit;
                }
                $result->free_result();
                $stmt->close();
                $conn->close();
            }
    } 
    else 
    {
        header("location: notAuthorized.html");
        exit;
    }
?>