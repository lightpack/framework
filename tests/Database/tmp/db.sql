-- create products table
DROP TABLE IF EXISTS `products`;
CREATE TABLE IF NOT EXISTS `products` (
    `id` int NOT NULL AUTO_INCREMENT,
    `name` varchar(55) COLLATE utf8_unicode_ci NOT NULL,
    `color` varchar(55) COLLATE utf8_unicode_ci NOT NULL,
    `price` decimal(10,2) NOT NULL DEFAULT '0.00',
    `views` INT DEFAULT 0,
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
  `tenant_id` int NULL DEFAULT NULL,
  `name` varchar(45) COLLATE utf8_unicode_ci NOT NULL,
  `active` TINYINT(1) NOT NULL DEFAULT 1,
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

-- create table projects
DROP TABLE IF EXISTS `projects`;
CREATE TABLE IF NOT EXISTS `projects` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(45) COLLATE utf8_unicode_ci NOT NULL,
  `status` varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- create table tasks
DROP TABLE IF EXISTS `tasks`;
CREATE TABLE IF NOT EXISTS `tasks` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(45) COLLATE utf8_unicode_ci NOT NULL,
  `project_id` int NOT NULL,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- create table comments
DROP TABLE IF EXISTS `comments`;
CREATE TABLE IF NOT EXISTS `comments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `task_id` int NOT NULL,
  `content` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- create table managers
DROP TABLE IF EXISTS `managers`;
CREATE TABLE IF NOT EXISTS `managers` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(45) COLLATE utf8_unicode_ci NOT NULL,
  `project_id` int NOT NULL,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- create table articles
DROP TABLE IF EXISTS `articles`;
CREATE TABLE IF NOT EXISTS `articles` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `status` varchar(255) COLLATE utf8_unicode_ci DEFAULT 'draft',
  `created_at` timestamp NULL,
  `updated_at` timestamp NULL,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- create table cast_models for testing attribute casting
DROP TABLE IF EXISTS `cast_models`;
CREATE TABLE IF NOT EXISTS `cast_models` (
  `id` int NOT NULL AUTO_INCREMENT,
  `string_col` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `integer_col` int DEFAULT NULL,
  `float_col` decimal(10,2) DEFAULT NULL,
  `boolean_col` tinyint(1) DEFAULT NULL,
  `json_col` json DEFAULT NULL,
  `date_col` date DEFAULT NULL,
  `datetime_col` datetime DEFAULT NULL,
  `timestamp_col` timestamp DEFAULT NULL,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- create pivot table for CastModel relationships
CREATE TABLE IF NOT EXISTS cast_model_relations (
    parent_id INTEGER,
    child_id INTEGER,
    PRIMARY KEY (parent_id, child_id)
);

-- create table polymorphic_comments for testing polymorphic relations
DROP TABLE IF EXISTS `polymorphic_comments`;
CREATE TABLE IF NOT EXISTS `polymorphic_comments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `body` text,
  `commentable_id` int NOT NULL,
  `commentable_type` varchar(50) NOT NULL,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- create table posts for polymorphic relation tests
DROP TABLE IF EXISTS `posts`;
CREATE TABLE IF NOT EXISTS `posts` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(255),
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- create table videos for polymorphic relation tests
DROP TABLE IF EXISTS `videos`;
CREATE TABLE IF NOT EXISTS `videos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(255),
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- insert dummy data for products
INSERT INTO `products` (`name`, `color`, `price`) VALUES
    ('Dummy Product', '#09F', 100.00),
    ('Dummy Product', '#CCC', 200.00);