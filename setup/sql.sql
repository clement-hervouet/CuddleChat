-- you should have to run this SQL only once, to create the the table and it's associated columns. You can run this SQL after creating the database (named as you wish) do not forget to put this name in the config file!

USE your_database;

CREATE TABLE users (
    id_user         INT NOT NULL    PRIMARY KEY AUTO_INCREMENT,
    username        VARCHAR(50)     NOT NULL    UNIQUE,
    firstname       VARCHAR(50)     NOT NULL,
    lastname        VARCHAR(50)     NOT NULL,
    password        VARCHAR(255)    NOT NULL,
    created_at      DATETIME        DEFAULT     CURRENT_TIMESTAMP,
    status          ENUM            ('admin','guest','editor') DEFAULT 'guest'  -- still not in use today
);
