<!DOCTYPE html>

<html>
<head>
<title>Mailserver Manager</title>
<meta name="viewport" content="width=device-width, initial-scale=0.8">
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
</style>
</head>
<body>
    <a href="https://<?= $_SERVER["HTTP_HOST"] ?>">Back to Roundcube</a><br>
    <h1>Manage Mailserver</h1>
    <?php
        error_reporting(E_ALL & ~E_NOTICE);
        $scriptfilename = basename($_SERVER["SCRIPT_FILENAME"]);

        session_start();
        
        if(!$_GET){
            header("Location: $scriptfilename?function=login");
            exit;
        }

        $include = true;
        include("dbconnect.php");

        if(!$_SESSION['user']){
            if($_POST['username'] && $_POST['passwd']){
                $username = explode("@", $_POST['username'])[0];
                $domain = explode("@", $_POST['username'])[1];
                $login = $dbc->query("SELECT password FROM accounts WHERE username = '$username' AND domain = '$domain' AND manager = 1;");
                if($login->num_rows > 0){
                    $passhash = $login->fetch_assoc()["password"];
                    $checkpass = exec("doveadm pw -s SHA512-CRYPT -t '$passhash' -p $_POST[passwd]");
                    if(strpos($checkpass,"verified")){
                        $_SESSION['user'] = $_POST['username'];
                        header("Location: $scriptfilename?function=overview");
                        exit;
                    }else{
                        echo "wrong username or password. try again.<br>";
                    }
                }else{
                    echo "wrong username or password. try again.<br>";
                }

            }
            echo "
                <form action='$scriptfilename?login' method='post'>
                    <h3>please login:</h3>
                    <input type='text' name='username' placeholder='username'><br>
                    <input type='password' name='passwd' placeholder='password'><br><br>
                    <button type='submit'>GO</button>
                </form>
            ";
            exit;
        }

        if($_SESSION['user']){
            // display a header
            echo "
                <a href='$scriptfilename?function=overview'>Overview</a> | 
                <a href='$scriptfilename?function=new&type=account'>New User</a> | 
                <a href='$scriptfilename?function=new&type=domain'>New Domain</a> | 
                <a href='$scriptfilename?function=new&type=alias'>New Alias</a> | 
                <a href='$scriptfilename?function=logout'>Logout</a> 
                
                <br><hr>
            ";
            switch($_GET['function']){
                case "logout":
                    session_destroy();
                    header("Location: $scriptfilename?function=login");
                    break;

                case "new":
                    //submit
                    if($_POST['save'] == "save"){
                        $insert = "insert into $_POST[type] (";
                        foreach($_POST as $postdesc=>$post){
                            if($postdesc != "type" && $postdesc != "save") $insert .= "$postdesc, ";
                        }
                        $insert = substr($insert, 0, -2);
                        $insert .= ") values (";
                            foreach($_POST as $postdesc=>$post){
                                if($postdesc == "password") $post = exec('doveadm pw -s SHA512-CRYPT -p ' . $post);
                                if($postdesc != "type" && $postdesc != "save") $insert .= "'$post', ";
                            }
                            $insert = substr($insert, 0, -2);
                            $insert .= ");";
                            $res = $dbc->query($insert);
                            if($res->error){
                                echo $res->error;
                                exit;
                            }
                            header("Location: $scriptfilename?function=overview");
                            exit;
                    }

                    //form
                    echo "<form action='$scriptfilename?function=new' method='post'>
                          <table>";
                    switch($_GET['type']){
                        case "account":
                            echo "<input type='hidden' name='type' value='accounts'>";
                            echo "<tr><td>username: </td><td><input type='text' name='username' placeholder='username'></td></tr>";
                            echo "<tr><td>domain: </td><td><input type='text' name='domain' placeholder='domain.tld'> - with TLD</td></tr>";
                            echo "<tr><td>password: </td><td><input type='password' name='password' placeholder='password...'> - NO SPACES!</td></tr>";
                            echo "<tr><td>quota: </td><td><input type='number' name='quota' value='2048' placeholder='2048'> MB</td></tr>";
                            echo "<tr><td>enabled: </td><td><input checked type='radio' name='enabled' value='1'> Yes | <input type='radio' name='enabled' value='0'> No</td></tr>";
                            echo "<tr><td>sendonly: </td><td><input type='radio' name='sendonly' value='1'> Yes | <input checked type='radio' name='sendonly' value='0'> No</td></tr>";
                            echo "<tr><td>manager: </td><td><input type='radio' name='manager' value='1'> Yes | <input checked type='radio' name='manager' value='0'> No</td></tr>";
                            echo "<tr><td colspan='2' style='text-align: right'><input type='submit' name='save' value='save'></td></tr>";
                            break;

                        case "domain":
                            echo "<input type='hidden' name='type' value='domains'>";
                            echo "<tr><td>domain: </td><td><input type='text' name='domain' placeholder='domain.tld'> - with TLD</td></tr>";
                            echo "<tr><td colspan='2' style='text-align: right'><input type='submit' name='save' value='save'></td></tr>";
                            break;

                        case "alias":
                            echo "<input type='hidden' name='type' value='aliases'>";
                            echo "<tr><td>source username: </td><td><input type='text' name='source_username' placeholder='source username'></td></tr>";
                            echo "<tr><td>source domain: </td><td><input type='text' name='source_domain' placeholder='source.tld'> - with TLD</td></tr>";
                            echo "<tr><td>destination username: </td><td><input type='text' name='destination_username' placeholder='destination username'></td></tr>";
                            echo "<tr><td>destination domain: </td><td><input type='text' name='destination_domain' placeholder='destination.tld'> - with TLD</td></tr>";
                            echo "<tr><td>enabled: </td><td><input checked type='radio' name='enabled' value='1'> Yes | <input type='radio' name='enabled' value='0'> No</td></tr>";
                            echo "<tr><td colspan='2' style='text-align: right'><input type='submit' name='save' value='save'></td></tr>";
                            break;
                        
                        default:
                            header("Location: $scriptfilename?function=overview");
                    }
                    echo "</table>
                          </form>";
                    break;

                case "modify":
                    if($_GET['table'] && $_GET['id']){
                        //submit
                        if($_POST['delete']){
                            $res = $dbc->query("delete from $_POST[table] where id = $_POST[id];");
                            if($res->error){
                                echo $res->error;
                                exit;
                            }
                            header("Location: $scriptfilename?function=overview");
                            exit;
                        }
                        if($_POST['save']){
                            $update = "update $_POST[table] set ";
                            foreach($_POST as $postdesc=>$post){
                                if($postdesc != "id" && $postdesc != "table" && $postdesc != "save" && $postdesc != "delete") $update .= "$postdesc='$post', ";
			                }
                            $update = substr($update, 0, -2);
                            $update .= " where id = $_POST[id];";
                            $res = $dbc->query($update);
                            if($res->error){
                                echo $res->error;
                                exit;
                            }
                            echo "<span style='background: lightgreen'>success</span>";
                        }

                        //form
                        $get_data = $dbc->query("select * from $_GET[table] where id = $_GET[id];");
                        $data = $get_data->fetch_assoc();
                        if (!empty($data['password'])){
                            echo "
                                    <script>
                                        function makenewhash(pwfield){
                                            var xhttp = new XMLHttpRequest();
                                            xhttp.open('POST', 'newhash.php', true);
                                            xhttp.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
                                            
                                            xhttp.onreadystatechange = function() {
                                                if (this.readyState == 4 && this.status == 200) {
                                                    // Typical action to be performed when the document is ready:
                                                    pwfield.value = this.responseText;
                                                }
                                            };
                                            xhttp.send('pw='+ pwfield.value);
                                        }
                                    </script>
                                    
                                    <input type='text' style='width: 412px' placeholder='generate password hash...'> <button onclick='makenewhash(this.previousElementSibling)'>generate</button><br><br>";
                        }

                        echo "<form action='$scriptfilename?function=modify&table=$_GET[table]&id=$_GET[id]' method='post'>";
                        echo "<input type='hidden' name='table' value='$_GET[table]'>";
                        echo "<input type='hidden' name='id' value='$_GET[id]'>";
                        echo "<table>";
                        foreach($data as $desc=>$field){
                            echo "<tr><td>$desc:</td>";
                            $checked0 = $checked1 = "";
                            $desc == "id" ? $disabled = "disabled" : $disabled = "";
                            if($desc == "enabled" || $desc == "sendonly" || $desc == "manager"){
                                $field == 1 ? $checked1 = "checked" : $checked0 = "checked";
                                echo "<td><input $checked1 type='radio' name='$desc' value='1'> Yes | 
                                          <input $checked0 type='radio' name='$desc' value='0'> No</td>";
			                }else{
                                echo "<td><input $disabled style='width: 400px' type='text' name='$desc' value='$field'></td>";
                            }
                            echo "</tr>";
                        }
                        echo "<tr><td colspan='2' style='text-align: right'><button type='submit' name='delete' value='delete' onclick='return confirm(\"Are you sure??\")'>delete!</button> <button type='submit' name='save' value='save'>Save</button></td></tr>";
                        echo "</table>";
                        echo "</form>";
                    }else{
                        header("Location: $scriptfilename?function=overview");
                    }
                    break;

                case "overview":
                default:
                    $get_accounts = $dbc->query("select * from accounts order by domain, username;");
                    
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
                    
                    echo "
                        <h3>Accounts</h3>
                        <table>
                        <tr><td><b>Account</b></td><td><b>Manager</b></td><td><b>enabled</b></td><td><b>sendonly</b></td><td><b>Storage usage</b></td><td><b>Messages</b></td></tr>
                    ";
                    $quotastr = "";
                    foreach($get_accounts as $account){
                        foreach($quota_array as $quota){
                            if($quota["Username"] == $account["username"]."@".$account["domain"] && $quota["Type"] == "STORAGE"){
                                $quotastr = (round($quota["Value"]/1024, 2))." of ".(round($quota["Limit"]/1024, 2))." MiB (".$quota["%"]."%)";
                            }elseif($quota["Username"] == $account["username"]."@".$account["domain"] && $quota["Type"] == "MESSAGE"){
                                $messagestr = $quota["Value"] . " of " . $quota["Limit"]. " (" . $quota["%"] . "%)";
                            }
                        }
			$manager = $account['manager'] ? "Y" : "N";
                        $enabled = $account['enabled'] ? "Y" : "N";
                        $sendonly = $account['sendonly'] ? "Y" : "N";
                        echo "<tr><td><a href='$scriptfilename?function=modify&table=accounts&id=$account[id]'>$account[username]@$account[domain]</a></td>
                              <td>$manager</td>
			      <td>$enabled</td>
                              <td>$sendonly</td>
                              <td>$quotastr</td>
                              <td>$messagestr</td></tr>";
                    }
                    echo "</table>";
                    $get_domains = $dbc->query("select * from domains order by domain;");
                    echo "
                        <h3>Domains</h3>
                        <table>
                        <tr><td><b>Domain</b></td></tr>
                    ";
                    foreach($get_domains as $domain){
                        echo "<tr><td><a href='$scriptfilename?function=modify&table=domains&id=$domain[id]'>$domain[domain]</a></td></tr>";
                    }
                    echo "</table>";
                    $get_aliases = $dbc->query("select * from aliases order by source_domain, source_username;");
                    echo "
                        <h3>Aliases</h3>
                        <table>
                        <tr><td><b>Source</b></td><td><b>Destination</b></td><td><b>enabled</b></td></tr>
                    ";
                    foreach($get_aliases as $alias){
                        $enabled = $alias['enabled'] ? "Y" : "N";
                        echo "<tr><td><a href='$scriptfilename?function=modify&table=aliases&id=$alias[id]'>$alias[source_username]@$alias[source_domain]</a></td>
                              <td>$alias[destination_username]@$alias[destination_domain]</td>
                              <td>$enabled</td></tr>";
                    }
                    echo "</table>";
                }
            }
    ?>
</body>
</html>
