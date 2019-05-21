CREATE TABLE `medora`.`application_types` (
  `application_type_id` INT NOT NULL,
  `application_type` VARCHAR(45) NOT NULL,
  PRIMARY KEY (`application_type_id`),
  UNIQUE INDEX `application_type_UNIQUE` (`application_type` ASC));

INSERT INTO `medora`.`application_types` (`application_type_id`, `application_type`) VALUES ('1', 'employment');
INSERT INTO `medora`.`application_types` (`application_type_id`, `application_type`) VALUES ('2', 'volunteer');

ALTER TABLE `medora`.`applications` 
ADD COLUMN `application_type_id` INT NOT NULL DEFAULT 1 AFTER `status`;

ALTER TABLE `medora`.`applications` 
CHANGE COLUMN `encoded_data` `encoded_data` MEDIUMBLOB NULL ;

ALTER TABLE `medora`.`applications` 
CHANGE COLUMN `encoded_blob` `encoded_blob` MEDIUMBLOB NULL ,
CHANGE COLUMN `encoded_data` `encoded_data` MEDIUMBLOB NOT NULL ;
