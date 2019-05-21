
-- ACL
INSERT INTO `acl` (`user_id`, `group_id`, `asset`, `asset_id`, `permission`) VALUES
    (0, 1, 'accounts', 0, 4),
    (0, 1, 'accounts', 0, 6),
    (0, 1, 'accounts', 0, 7),
    (0, 1, 'groups', 0, 4),
    (0, 1, 'groups', 0, 6),
    (0, 1, 'groups', 0, 7),
    (0, 1, 'media', 0, 4),
    (0, 1, 'media', 0, 6),
    (0, 1, 'media', 0, 7),
    (0, 1, 'menu', 0, 4),
    (0, 1, 'menu', 0, 6),
    (0, 1, 'menu', 0, 7),
    (0, 1, 'pages', 0, 4),
    (0, 1, 'pages', 0, 6),
    (0, 1, 'pages', 0, 7),
    (0, 1, 'settings', 0, 4),
    (0, 1, 'settings', 0, 6),
    (0, 1, 'settings', 0, 7);

-- Default account. USER: "admin" PASS: "testing123"
INSERT INTO `users` (`user_id`, `name`, `username`, `password`, `email`, `created`, `modified`, `is_active`) VALUES
    (1, 'Administrator', 'admin', '$2y$10$m0TA9FRupysB4wWlVX4R0.Dr4bHu1VIWjq9YHq.s04JPZMc5bgIM6', NULL, NULL, NOW(), 1);


-- Admin group.
INSERT INTO `groups` (`group_id`, `name`, `description`, `created`, `modified`) VALUES
    (1, 'Admin', 'Administrative group, usually with full access.', NULL, NULL),
	(2, 'CMS', 'Users in this group will have access to the CMS admin section (/update/)', NULL, NULL);

-- Link admins to groups.
INSERT INTO `user_groups` (`group_id`, `user_id`) VALUES 
	(1, 1),
	(2, 1);

-- Assets.
INSERT INTO `assets` (`name`, `location`, `clean_name`, `sort`, `visible`) VALUES
    ('accounts', '/update/accounts', 'Accounts',		0, 0),
    ('pages',	'/update/pages',	'Pages',			0, 0),
    ('media',	'/update/media',	'Media Library',	0, 0),
    ('menu',	'/update/menu',		'Menu',				0, 0),
    ('home',	'',					'Homepage',			0, 1),
    ('site',	'/update/settings', 'Settings',			0, 0),
	('snippets','/update/snippets',	'Snippets',			0, 0);

-- Pages.
INSERT INTO `pages` (`page_id`, `title`, `url`, `asset`, `body`) VALUES 
	(1,	'Homepage',	'/',		'home',	'<h2>MORE CONTENT</h2><p>This page definitely needs more content.</p>'),
	(2,	'About',	'/about/',	NULL,	'<h2>EXAMPLE PAGE</h2><p>This is an example "about" page.</p>');

-- Example menus and menu items.
INSERT INTO `menus` (`menu_id`, `name`) VALUES
	(1,	'TOP');
INSERT INTO `menu_items` (`menu_id`, `page_id`, `sort`, `link`, `title`) VALUES 
	(1,	NULL,	0,	'/',	'Home'),
	(1,	1,		1,	NULL,	'Link to homepage'),
	(1,	2,		2,	NULL,	'About');

-- Media Folders.
INSERT INTO `media_folders` (`display_name`) VALUES 
	('Default');


-- Data for menus in admin
INSERT INTO `admin_menus` (`title`, `code`, `link`, `class`, `sort`, `is_active`, `show_beneath`) VALUES 
	('Pages',			'pages',		'pages',			'ui-button-icon-primary ui-icon ui-icon-document',				'0',	'1', null),
	('Menu',			'menu',			'menu',				'ui-button-icon-primary ui-icon ui-icon-grip-solid-horizontal',	'1',	'1', null),
	('News',			'news',			'news',				'ui-button-icon-primary ui-icon ui-icon-signal-diag',			'2',	'1', null),
	('Calendar',		'calendar',		'calendar',			'ui-button-icon-primary ui-icon ui-icon-calendar',				'3',	'1', null),
	('Media Library',	'media',		'media',			'ui-button-icon-primary ui-icon ui-icon-image',					'4',	'1', null),
	('Accounts',		'accounts',		'accounts',			'ui-button-icon-primary ui-icon ui-icon-person',				'5',	'1', null),
	('Snippets',		'snippets',		'snippets',			'ui-button-icon-primary ui-icon ui-icon-comment',				'6',	'1', null),
	('Notifications',	'notifications','notifications',	'ui-button-icon-primary ui-icon ui-icon-alert',					'7',	'1', null),
	('Groups',			'groups',		'accounts/groups',	'',																'0',	'1', 'accounts'),
	('Galleries',		'galleries',	'galleries',		'',																'0',	'1', 'media'),
	('Settings',		'settings',		'settings',			'ui-button-icon-primary ui-icon ui-icon-pin-s',					'999',	'1', null);

INSERT INTO `mimes` (`mime`, `ext`, `allowed`, `image`) VALUES 
	('image/x-jg','.art',0,0),
	('application/x-troff-msvideo','.avi',0,0),
	('video/avi','.avi',0,0),
	('video/msvideo','.avi',0,0),
	('video/x-msvideo','.avi',0,0),
	('video/avs-video','.avs',0,0),
	('image/bmp','.bmp',0,0),
	('image/x-windows-bmp','.bmp',0,0),
	('application/x-bzip2','.bz2',0,0),
	('application/x-bsh','.shar',0,0),
	('text/plain','.txt',1,0),
	('application/pkix-cert','.crt',0,0),
	('application/x-x509-ca-cert','.der',0,0),
	('text/css','.css',0,0),
	('image/gif','.gif',1,1),
	('application/x-compressed','.zip',0,0),
	('application/x-gzip','.gzip',0,0),
	('multipart/x-gzip','.gzip',0,0),
	('image/x-icon','.ico',0,0),
	('image/jpeg','.jpg',1,1),
	('image/pjpeg','.jpg',1,1),
	('video/mpeg','.mpg',0,0),
	('audio/mpeg','.mpga',0,0),
	('application/x-midi','.midi',0,0),
	('audio/x-mid','.midi',0,0),
	('audio/x-midi','.midi',0,0),
	('music/crescendo','.midi',0,0),
	('x-music/x-midi','.midi',0,0),
	('video/quicktime','.qt',0,0),
	('audio/x-mpeg','.mp2',0,0),
	('video/x-mpeg','.mp3',0,0),
	('video/x-mpeq2a','.mp2',0,0),
	('audio/mpeg3','.mp3',0,0),
	('audio/x-mpeg-3','.mp3',0,0),
	('application/pkcs10','.p10',0,0),
	('application/x-pkcs10','.p10',0,0),
	('application/pkcs-12','.p12',0,0),
	('application/x-pkcs12','.p12',0,0),
	('application/x-pkcs7-signature','.p7a',0,0),
	('application/pkcs7-mime','.p7m',0,0),
	('application/x-pkcs7-mime','.p7m',0,0),
	('application/x-pkcs7-certreqresp','.p7r',0,0),
	('application/pkcs7-signature','.p7s',0,0),
	('application/pdf','.pdf',1,0),
	('application/mspowerpoint','.ppz',0,0),
	('application/vnd.ms-powerpoint','.pwz',0,0),
	('application/powerpoint','.ppt',0,0),
	('application/x-mspowerpoint','.ppt',0,0),
	('image/x-quicktime','.qtif',0,0),
	('video/x-qtc','.qtc',0,0),
	('audio/x-pn-realaudio','.rmp',0,0),
	('audio/x-pn-realaudio-plugin','.rpm',0,0),
	('audio/x-realaudio','.ra',0,0),
	('application/x-rtf','.rtf',1,0),
	('application/x-pkcs7-certificates','.spc',0,0),
	('application/x-tar','.tar',0,0),
	('application/plain','.text',0,0),
	('application/gnutar','.tgz',0,0),
	('image/tiff','.tiff',0,0),
	('image/x-tiff','.tiff',0,0),
	('text/x-vcalendar','.vcs',0,0),
	('audio/wav','.wav',0,0),
	('audio/x-wav','.wav',0,0),
	('application/excel','.xlw',0,0),
	('application/x-excel','.xlw',0,0),
	('application/x-msexcel','.xlw',0,0),
	('application/vnd.ms-excel','.xlw',0,0),
	('application/xml','.xml',0,0),
	('text/xml','.xml',0,0),
	('image/xpm','.xpm',0,0),
	('application/x-compress','.z',0,0),
	('application/x-zip-compressed','.zip',0,0),
	('application/zip','.zip',0,0),
	('multipart/x-zip','.zip',0,0),
	('image/png','.png',1,1),
	('video/x-flv','.flv',1,0);

INSERT INTO setting_categories (`setting_category_id`, `setting_category_name`, `setting_code`) VALUES 
	(1,	'Site Settings',		'site'),
	(2,	'Internal Settings',	'internal');

INSERT INTO `settings` (`title`, `description`, `type`, `name`, `value`) VALUES
	('Description',	'Website description used by search engines.',	'textarea',	'description',	'Another Crazed(Sanity) Website'),
	('Title',		'Website title used by search engines.',		'text',		'title',		'Base CS-CMS Website'),
	('Keywords',	'Website keywords use by search engines.',		'textarea',	'keywords',		'cscms crazedsanity cms default'),
	('Divide',		'Website title divider.',						'text',		'divide',		' | '),
	('Name',		'Website name',									'text',		'name',			'Another Crazed(Sanity) Website'),
	('OG Image',	'Image used when sharing on Facebook',			'image',	'og_image',		'');

INSERT INTO `snippets` (`code`, `description`, `body`) VALUES 
	('CONTACT_INFO', 'Contact information', 'CrazedSanity dot com (crazedsanity.com)');

INSERT INTO `notification_types` (`notification_type_id`, `notification_type`, `description`, `color`, `icon`)  VALUES 
	(1,'notice','Standard notice (green)','B8CDA6','speechbubble102'),
    (2,'warning','Warning (yellow)','FFFF33','flag97'),
    (3,'emergency','Emergency (red)','FF0000','alert2');


INSERT INTO `recurrence_types` (`recurrence_type_id`, `recurrence_type`, `interval`, `default_recurrence_end`) VALUES 
	(1,	'none',		null,		null),
	(2,	'daily',	'1 day',	'1 year'),
	(3,	'weekly',	'1 week',	'1 year'),
	(4,	'monthly',	'1 month',	'5 year'),
	(5,	'yearly',	'1 year',	'10 year');

INSERT INTO `calendars` (`calendar_id`, `title`, `color`) VALUES 
	(1,	'Default', '43bf80');
