DROP TABLE IF EXISTS `products`;
CREATE TABLE IF NOT EXISTS `products` (
    `id` int NOT NULL AUTO_INCREMENT,
    `name` varchar(55) COLLATE utf8_unicode_ci NOT NULL,
    `color` varchar(4) COLLATE utf8_unicode_ci NOT NULL,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS `options`;
CREATE TABLE IF NOT EXISTS `options` (
    `id` int NOT NULL AUTO_INCREMENT,
    `product_id` int NOT NULL,
    `name` varchar(45) COLLATE utf8_unicode_ci NOT NULL,
    `value` varchar(4) COLLATE utf8_unicode_ci NOT NULL,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS `owners`;
CREATE TABLE IF NOT EXISTS `owners` (
  `id` int NOT NULL AUTO_INCREMENT,
  `product_id` int NOT NULL,
  `name` varchar(45) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `products` (`id`, `name`, `color`) VALUES
    (331, 'Dummy Product', '#09F'),
    (332, 'Dummy Product', '#CCC');