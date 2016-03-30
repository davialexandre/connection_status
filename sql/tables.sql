CREATE TABLE `ping` (
  id             VARCHAR(100) PRIMARY KEY,
  last_ping_date DATETIME NOT NULL
);

CREATE TABLE `failure` (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  start      DATETIME NOT NULL,
  end        DATETIME     DEFAULT NULL,
  connection VARCHAR(100)
);

ALTER TABLE `failure`
ADD CONSTRAINT fk_connection
FOREIGN KEY (connection)
REFERENCES ping (id)
  ON DELETE RESTRICT
  ON UPDATE CASCADE;

CREATE UNIQUE INDEX unique_failure ON failure (connection, start);