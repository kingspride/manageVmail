# manageVmail

Simple Management Interface for Thomas' Leisters EMail Server Setup.

**Warning!**    
**NO SQL SECURITY MEASURES TAKEN!**

## Installation

1. add a new column to the accounts table:    
   ```SQL
    ALTER TABLE `accounts`
	ADD COLUMN `manager` TINYINT(1) NOT NULL DEFAULT '0' AFTER `sendonly`;
   ```
2. promote user to manager:    
   ```SQL
   UPDATE `vmail`.`accounts` SET `manager`='1' WHERE  `username`=`you` and `domain`=`yourdomain.tld`;
   ```
3. create a database user separate from the vmail user:    
    ```SQL
    GRANT ALL PRIVILEGES ON `vmail`.* TO 'mailadm'@'localhost' IDENTIFIED BY 'your-password';
    ```
4. create a `dbconnect.php` file from the template.
5. create a cronjob for the quota display:    
   `0 * * * * /bin/bash /var/www/webmail/manage/get_quota.sh`