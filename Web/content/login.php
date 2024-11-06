
<?php
    if(isset($_POST['uname'], $_POST['pswd'])){
        session_start();
        $servername = "localhost:3306";
        $username = "userlogin";
        $password = "wjAk3OysUNfZmnK";
        $db = "usbraidlogin";
        
        $uname = htmlspecialchars($_POST['uname']);
        $pswd = htmlspecialchars($_POST['pswd']);

        $hash = password_hash($pswd, PASSWORD_BCRYPT);
        if (password_verify($pswd, $hash)) {

            $conn = new mysqli($servername, $username, $password, $db);
            $conn->set_charset("utf8");
            
            if ($conn->connect_error) {
               die("Connection failed: " . $conn->connect_error);
            }
            else {
                        
                $sql = "SELECT * FROM user WHERE uname=? and pswd=?";
                $stmt = $conn->prepare($sql);
            
                $stmt->bind_param("ss", $uname, $pswd);
            
                $stmt->execute();
            
                $result = $stmt->get_result();
            
                if($result !== false && $result->num_rows > 0){
                    $response = $result->fetch_row();
            
                    $_SESSION['uname'] = $uname;
            
                    header("location: FTP.php");
                 }
                else {
                    header("location: notAuthorized.html");
                 }
                $result->free_result();
            }
        } 
        else {
            header("location: notAuthorized.html");
        }
        

    }
?>