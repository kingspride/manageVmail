<?php
    if(!empty($_POST['pw'])){
        $result = exec("doveadm pw -s SHA512-CRYPT -p $_POST[pw]");
        echo $result;
    }else{
        echo "notvalid";
    }

?>