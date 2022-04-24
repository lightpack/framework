-- create products table
DROP TABLE IF EXISTS `products`;
CREATE TABLE IF NOT EXISTS `products` (
    `id` int NOT NULL AUTO_INCREMENT,
    `name` varchar(55) COLLATE utf8_unicode_ci NOT NULL,
    `color` varchar(4) COLLATE utf8_unicode_ci NOT NULL,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- create options table
DROP TABLE IF EXISTS `options`;
CREATE TABLE IF NOT EXISTS `options` (
    `id` int NOT NULL AUTO_INCREMENT,
    `product_id` int NOT NULL,
    `name` varchar(45) COLLATE utf8_unicode_ci NOT NULL,
    `value` varchar(4) COLLATE utf8_unicode_ci NOT NULL,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- create owners table
DROP TABLE IF EXISTS `owners`;
CREATE TABLE IF NOT EXISTS `owners` (
  `id` int NOT NULL AUTO_INCREMENT,
  `product_id` int NOT NULL,
  `name` varchar(45) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- create users table
DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(45) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- create roles table
DROP TABLE IF EXISTS `roles`;
CREATE TABLE IF NOT EXISTS `roles` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(45) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- create role_user table
DROP TABLE IF EXISTS `role_user`;
CREATE TABLE IF NOT EXISTS `role_user` (
  `user_id` int NOT NULL,
  `role_id` int NOT NULL,
  PRIMARY KEY (user_id, role_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- create permissions table
DROP TABLE IF EXISTS `permissions`;
CREATE TABLE IF NOT EXISTS `permissions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(45) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- create permission_role table
DROP TABLE IF EXISTS `permission_role`;
CREATE TABLE IF NOT EXISTS `permission_role` (
  `permission_id` int NOT NULL,
  `role_id` int NOT NULL,
  PRIMARY KEY (permission_id, role_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- insert dummy data for products
INSERT INTO `products` (`id`, `name`, `color`) VALUES
    (331, 'Dummy Product', '#09F'),
    (332, 'Dummy Product', '#CCC');

-- insert dummy data for users
INSERT INTO `users` (`id`, `name`) VALUES
    (1, 'John Doe'),
    (2, 'Jane Doe');

-- insert dummy data for roles
INSERT INTO `roles` (`id`, `name`) VALUES
    (1, 'admin'),
    (2, 'user'),
    (3, 'guest');

-- insert dummy data for role_user
INSERT INTO `role_user` (`user_id`, `role_id`) VALUES
    (1, 1),
    (2, 2),
    (2, 3),
    (1, 3),
    (2, 1);

-- insert dummy data for permissions
INSERT INTO `permissions` (`id`, `name`) VALUES
    (1, 'create'),
    (2, 'read'),
    (3, 'update'),
    (4, 'delete');

-- insert dummy data for permission_role
INSERT INTO `permission_role` (`permission_id`, `role_id`) VALUES
    (1, 1),
    (2, 1),
    (3, 1),
    (4, 1),
    (1, 2),
    (2, 2),
    (3, 2),
    (4, 2),
    (1, 3),
    (2, 3),
    (3, 3),
    (4, 3);