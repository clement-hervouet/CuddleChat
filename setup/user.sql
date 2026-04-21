-- you should have to run this SQL only once, to create the user and grant the necessary permissions to access the database.

-- 'your-passwd' should be replaced with your own password.
-- Note: The '%' wildcard allows the user to connect from any host. You can replace it with 'localhost' if you want to restrict access to the local machine only.

CREATE USER 'app.login'@'%' IDENTIFIED BY 'your-passwd';


-- no need for DELETE: the actual programm don't permit to delete users, only to update them (change password)
GRANT SELECT, INSERT, UPDATE ON your_database.users TO 'app.login'@'%';

-- Note: This user only have the right to the users table, if you want this user to acces all tables in the database, you can replace "your_database.users" with "your_database.*"

-- Note: FLUSH PRIVILEGES seems to be deprecated in future update of MySQL
-- still supported on version "9.5.0 - MySQL Community Server - GPL"
FLUSH PRIVILEGES;

-- DO NOT FORGET TO ALSO UPDATE config.php with your credentials