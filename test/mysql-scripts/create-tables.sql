CREATE TABLE IF NOT EXISTS`user` (
	`id` BIGINT NOT NULL,
	`username` VARCHAR(126),
	`passhash` VARCHAR(126),
	`emailaddress` VARCHAR(126),
	PRIMARY KEY (`id`)
);
CREATE TABLE IF NOT EXISTS`organization` (
	`id` BIGINT NOT NULL,
	`name` VARCHAR(126) NOT NULL,
	PRIMARY KEY (`id`)
);
CREATE TABLE IF NOT EXISTS`abc` (
	`a` BIGINT NOT NULL,
	`b` INT NOT NULL,
	`c` TEXT NOT NULL,
	PRIMARY KEY (`a`, `b`)
);
CREATE TABLE IF NOT EXISTS`userorganizationattachment` (
	`userid` BIGINT NOT NULL,
	`organizationid` BIGINT NOT NULL,
	PRIMARY KEY (`userid`, `organizationid`),
	FOREIGN KEY (`userid`) REFERENCES `user` (`id`),
	FOREIGN KEY (`organizationid`) REFERENCES `organization` (`id`)
);
CREATE TABLE IF NOT EXISTS`computationstatus` (
	`statuscode` VARCHAR(126) NOT NULL,
	PRIMARY KEY (`statuscode`)
);
CREATE TABLE IF NOT EXISTS`computation` (
	`expression` VARCHAR(126) NOT NULL,
	`statuscode` VARCHAR(126) NOT NULL,
	`result` VARCHAR(126),
	PRIMARY KEY (`expression`),
	FOREIGN KEY (`statuscode`) REFERENCES `computationstatus` (`statuscode`)
);
