USE swd_auth;

ALTER TABLE users
    MODIFY COLUMN name VARCHAR(120) NOT NULL,
    MODIFY COLUMN email VARCHAR(254) NOT NULL;

ALTER TABLE login_attempts
    MODIFY COLUMN email VARCHAR(254) NOT NULL,
    MODIFY COLUMN ip VARCHAR(45) NOT NULL,
    ADD KEY login_attempts_ip_time (ip, attempted_at);
