# manageVmail

Simple Management Interface for Thomas' Leisters EMail Server Setup.

**Attention!**    
**be careful with the admin interface. It might have some security flaws after login.**

## Installation

1. create a new table in the database:    
   ```SQL
    CREATE TABLE `managers` (
        `id` INT(6) UNSIGNED NOT NULL AUTO_INCREMENT,
        `username` VARCHAR(30) NOT NULL,
        `password` VARCHAR(50) NOT NULL,
        PRIMARY KEY (`id`)
    )
    COLLATE='utf8_general_ci'
    ENGINE=InnoDB
    ;
   ```
2. create a manager user:    
   ```SQL
   INSERT INTO managers (username, password) values ('madm','$SHA1_HASH');
   ```
3. create a database user separate from the vmail user:    
    ```SQL
    GRANT ALL PRIVILEGES ON `vmail`.* TO 'mailadm'@'localhost' IDENTIFIED BY 'your-password';
    ```
4. create a `dbconnect.php` file from the template.
5. create a cronjob for the quota display:    
   `0 * * * * /var/www/webmail/manage/get_quota.sh`