<!DOCTYPE html>

<html>
<head>
    <title>User Selfservice</title>
    <meta name="viewport" content="width=device-width, initial-scale=0.8">
    <meta http-equiv="cache-control" content="no-cache, no-store, must-revalidate" />
    <meta http-equiv="pragma" content="no-cache" />
    <meta http-equiv="expires" content="0" />
    <style>
        a{
            color: #728521;
            text-decoration: none;
        }
        a:visited{
            color: #728521;
            text-decoration: none;
        }
        a:hover{
            color: darkorange;
        }
        td, th{
            padding-left: 5px;
            padding-right: 5px;
        }
        input:invalid{
            background-color: darkorange;
            color: white;
        }
    </style>
    <script>
        function checkpass(confirmfield, pwfield){
            if(pwfield.value == confirmfield.value){
                pwfield.setCustomValidity("");
                confirmfield.setCustomValidity("");
            }else{
                pwfield.setCustomValidity("not matching");
                confirmfield.setCustomValidity("not matching");
            }
        }
    </script>
</head>
<body>
    <a href="https://<?= $_SERVER["HTTP_HOST"] ?>">Back to Roundcube</a><br>
    <h1>User Selfservice</h1>
    <?php

        $scriptfilename = basename($_SERVER["SCRIPT_FILENAME"]);

        session_start();

        if(!$_GET){
            header("Location: $scriptfilename?function=login");
            exit;
        }

        $include = true;
        include("dbconnect.php");

        if(!$_SESSION['userdata']){
            if($_POST['username'] && $_POST['passwd']){
                $username = explode("@", $_POST['username'])[0];
                $domain = explode("@", $_POST['username'])[1];

                $get_passhash = $dbc->query("select * from accounts where username = '$username' and domain = '$domain';");
                $userdata = $get_passhash->fetch_assoc();
                $passhash = $userdata['password'];
                $checkpass = exec("doveadm pw -s SHA512-CRYPT -t '$passhash' -p $_POST[passwd]");

                if(strpos($checkpass,"verified")){
                    $_SESSION['userdata'] = $userdata;
                    header("Location: $scriptfilename?function=overview");
                    exit;
                }else{
                    echo "wrong username or password. try again.<br>";
                }

            }
            echo "
                <form action='$scriptfilename?login' method='post'>
                    <h3>please login:</h3>
                    <input type='email' name='username' placeholder='full email'><br>
                    <input type='password' name='passwd' placeholder='password'><br><br>
                    <button type='submit'>GO</button>
                </form>
            ";
            exit;
        }

        if($_SESSION['userdata']){
            $userdata = $_SESSION['userdata'];
            echo "
                <a href='$scriptfilename?function=overview'>Overview</a> | 
                <a href='$scriptfilename?function=setpasswd'>Change Password</a> | 
                <a href='$scriptfilename?function=newalias'>New Alias</a> | 
                <a href='$scriptfilename?function=logout'>Logout</a> 
                
                <br><hr>
            ";

            switch($_GET['function']){
                case "logout":
                    session_destroy();
                    header("Location: $scriptfilename?function=login");
                    break;
                case "editalias":
                    //submit, handle delete first
                    if($_POST['deletealias'] === "delete"){
                        $res = $dbc->query("delete from aliases where id = $_POST[id];");
                        if($res->error){
                            echo $res->error;
                            exit;
                        }
                        header("Location: $scriptfilename?function=overview");
                        exit;
                    }elseif($_POST['editalias'] === "save"){
                        $res = $dbc->query("update aliases 
                                            set source_username='$_POST[source_username]', source_domain='$_POST[source_domain]', enabled=$_POST[enabled]
                                            where id = $_POST[id];");
                        if($res->error){
                            echo $res->error;
                            exit;
                        }
                        header("Location: $scriptfilename?function=overview");
                        exit;
                    }
                    

                    //form
                    echo "<h3>Edit alias</h3>";
                    $get_alias_details = $dbc->query("select * from aliases where id = $_GET[id] and destination_username = '$userdata[username]' and destination_domain = '$userdata[domain]' order by source_domain, source_username;");
                    $alias_details = $get_alias_details->fetch_assoc();
                    echo "<form action='$scriptfilename?function=editalias' method='post'>
                            <input type='hidden' name='id' value='$_GET[id]'>
                            <input required type='text' name='source_username' placeholder='alias' value='$alias_details[source_username]'> @ 
                            <select name='source_domain' required>";
                            $get_avail_domains = $dbc->query("select * from domains order by domain;");
                            foreach($get_avail_domains as $avail_domain){
                                $avail_domain['domain'] == $alias_details['source_domain'] ? $selected = "selected" : $selected = "";
                                echo "<option $selected value='$avail_domain[domain]'>$avail_domain[domain]</option>";
                            }
                    echo "
                            </select> &rarr; 
                            <select required name='enabled'>";
                            $alias_details['enabled'] ? $selected1 = "selected" : $selected0 = "selected";
                    echo "
                                <option $selected1 value='1'>active</option>
                                <option $selected0 value='0'>inactive</option>
                            </select> &rarr; 
                            <input type='submit' name='deletealias' value='delete' onclick='return confirm(\"Are you sure you want to delete this alias?\")'> / 
                            <input type='submit' name='editalias' value='save'>
                        </form>";
                    break;

                case "newalias":
                    //submit
                    if($_POST['newalias'] === "save"){
                        $res = $dbc->query("insert into aliases (source_username, source_domain, destination_username, destination_domain, enabled) 
                                            values ('$_POST[source_username]', '$_POST[source_domain]', '$userdata[username]', '$userdata[domain]', $_POST[enabled]);");
                        if($res->error){
                            echo $res->error;
                            exit;
                        }
                        header("Location: $scriptfilename?function=overview");
                        exit;
                    }

                    //form
                    echo "<h3>Create new alias for your account</h3>";
                    echo "<form action='$scriptfilename?function=newalias' method='post'>
                            <input required type='text' name='source_username' placeholder='alias'> &rarr; 
                            <select name='source_domain' required>
                                <option disabled selected>choose a domain</option>";
                            $get_avail_domains = $dbc->query("select * from domains order by domain;");
                            foreach($get_avail_domains as $avail_domain){
                                echo "<option value='$avail_domain[domain]'>$avail_domain[domain]</option>";
                            }
                    echo "
                            </select> &rarr; 
                            <select name='enabled' required>
                                <option value='1'>active</option>
                                <option value='0'>inactive</option>
                            </select> &rarr; 
                            <input type='submit' name='newalias' value='save'>
                        </form>";
                    break;

                case "setpasswd":
                    //submit
                    if($_POST['setpassword'] === "save"){
			$postdata = [];
                        foreach($_POST as $i=>$post){
                            $postdata[$i] = htmlentities($post);
                        }
                        $newhash = exec("doveadm pw -s SHA512-CRYPT -p $_POST[password]");
                        $res = $dbc->query("update accounts set password='$newhash' where id = $userdata[id];");
                        if($res->error){
				echo "bist du hier stehengeblieben?";
                            echo $res->error;
                            exit;
                        }
                        echo "<span style='background: lightgreen'>success</span>";
                    }

                    //form
                    echo "<h3>Set new Password</h3>";
                    echo "<form action='$scriptfilename?function=setpasswd' method='post'>
                            <input type='password' name='password' placeholder='new password...' onkeyup='checkpass(this.nextElementSibling, this)'> &rarr; 
                            <input type='password' name='password' placeholder='confirm...' onkeyup='checkpass(this, this.previousElementSibling);'> &rarr; 
                            <input type='submit' name='setpassword' value='save'>
                        </form>";
                    break;

                case "overview":
                default:
                    //read quotas from crontab generated file
                    $quotafile = file_get_contents("./quota");
                    $quota_array = explode("\n", $quotafile);
                    $descriptors = array();
                    foreach($quota_array as $index=>$line){
                        if($index == 0){
                            $descriptors = explode("\t",$line);
                        }else{
                            $quota_array[$index] = array();
                            foreach(explode("\t", $line) as $findex=>$fragment){
                                $quota_array[$index][$descriptors[$findex]] = $fragment;
                            }
                        }
                    }
                    unset($quota_array[0]);
                    foreach($quota_array as $quota){
                        if($quota["Username"] == "$userdata[username]@$userdata[domain]"){
                            if($quota["Type"] == "STORAGE"){
                                $storage = (round($quota["Value"]/1024, 2))." of ".(round($quota["Limit"]/1024, 2))." MiB (".$quota["%"]."%)";
                            }
                            if($quota["Type"] == "MESSAGE"){
                                $message = "$quota[Value] of $quota[Limit] Mails (".$quota["%"]."%)";
                            }
                        }
                    }

                    echo "<h3>General Info</h3>";
                    $userdata['enabled'] ? $status = "active" : $status = "inactive";
                    echo "<table>
                          <tr><th style='text-align: right'>Your Email: </th><td>$userdata[username]@$userdata[domain]</td></tr>
                          <tr><th style='text-align: right'>Status: </th><td>$status</td></tr>
                          <tr><th style='text-align: right'>Storage: </th><td>$storage</td></tr>
                          <tr><th style='text-align: right'>Messages: </th><td>$message</td></tr>
                          </table>";

                    echo "<h3>Aliases</h3>";
                    $get_aliases = $dbc->query("select * from aliases where destination_username = '$userdata[username]' and destination_domain = '$userdata[domain]' order by source_domain, source_username;");
                    echo "<table>";
                    echo "<tr><td><b>Alias</b></td><td><b>Active?</b></td></tr>";
                    foreach($get_aliases as $alias){
                        $alias['enabled'] ? $enabled = "Y" : $enabled = "N";
                        echo "<tr><td><a href='$scriptfilename?function=editalias&id=$alias[id]'>$alias[source_username]@$alias[source_domain]</a></td><td>$enabled</td></tr>";
                    }
                    echo "</table>";
            }
        }
    ?>
</body>
</html>
