CREATE TABLE `storagetest`.`user` (
	`id` BIGINT NOT NULL,
	`username` VARCHAR(126),
	`passhash` VARCHAR(126),
	`emailaddress` VARCHAR(126),
	PRIMARY KEY (`id`)
);
CREATE TABLE `storagetest`.`organization` (
	`id` BIGINT NOT NULL,
	`name` VARCHAR(126) NOT NULL,
	`officelocation` GEOMETRY(POINT,4326),
	PRIMARY KEY (`id`)
);
CREATE TABLE `storagetest`.`abc` (
	`a` BIGINT NOT NULL,
	`b` INT NOT NULL,
	`c` TEXT NOT NULL,
	PRIMARY KEY (`a`, `b`)
);
CREATE TABLE `storagetest`.`userorganizationattachment` (
	`userid` BIGINT NOT NULL,
	`organizationid` BIGINT NOT NULL,
	PRIMARY KEY (`userid`, `organizationid`),
	FOREIGN KEY (`userid`) REFERENCES `storagetest`.`user` (`id`),
	FOREIGN KEY (`organizationid`) REFERENCES `storagetest`.`organization` (`id`)
);
CREATE TABLE `storagetest`.`computationstatus` (
	`statuscode` VARCHAR(126) NOT NULL,
	PRIMARY KEY (`statuscode`)
);
CREATE TABLE `storagetest`.`computation` (
	`expression` VARCHAR(126) NOT NULL,
	`statuscode` VARCHAR(126) NOT NULL,
	`result` VARCHAR(126),
	PRIMARY KEY (`expression`),
	FOREIGN KEY (`statuscode`) REFERENCES `storagetest`.`computationstatus` (`statuscode`)
);
