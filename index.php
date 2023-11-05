<!DOCTYPE html>
<html>
    <head>
        <title>Mailserver Manager</title>
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
        </style>
    </head>
    <body>
        <div style="width: 100%; text-align: center; margin-top: 100px">
            <h2>Main Menu</h2>
            <a href="selfservice.php">User Selfservice</a><br>
            <a href="manage.php">Administrative Tools</a><br><br>
            <a href="https://<?= $_SERVER["HTTP_HOST"] ?>">Back to Roundcube</a><br>
        </div>
    </body>
</html>
