ALTER TABLE `request_visitor_guide` 
ADD COLUMN `is_archived` INT(1) NOT NULL DEFAULT 0 AFTER `added`;

INSERT INTO `admin_menus` (`title`, `code`, `link`, `sort`, `is_active`, `asset_id`) VALUES 
	('Request Visitor Guide', 'request_visitor_guide', 'request_visitor_guide', '10', '1', '82');

