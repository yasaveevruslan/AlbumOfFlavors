-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Хост: 127.0.0.1:3306
-- Время создания: Ноя 17 2026 г., 14:05
-- Версия сервера: 8.0.30
-- Версия PHP: 8.0.22

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- База данных: `albumFlavors`
--

-- --------------------------------------------------------

--
-- Структура таблицы `albums`
--

CREATE TABLE `albums` (
  `id` char(36) NOT NULL DEFAULT (uuid()),
  `user_id` char(36) NOT NULL,
  `template_id` char(36) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `cover_url` text,
  `pages_count` int NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `albums`
--

INSERT INTO `albums` (`id`, `user_id`, `template_id`, `title`, `cover_url`, `pages_count`, `created_at`, `updated_at`) VALUES
('c7e51d90-ed71-4be5-bc15-c456d191a519', '2f3dd766-e13c-11f1-8335-1413335814c8', '46fc8f0c-e25e-11f1-b2f0-1413335814c8', 'Книга тест', 'asset/img/album_covers/cover_2.png', 4, '2026-11-17 12:40:40', '2026-11-17 13:58:20');

-- --------------------------------------------------------

--
-- Структура таблицы `album_pages`
--

CREATE TABLE `album_pages` (
  `id` char(36) NOT NULL DEFAULT (uuid()),
  `album_id` char(36) NOT NULL,
  `page_number` int NOT NULL,
  `layout` json DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `album_pages`
--

INSERT INTO `album_pages` (`id`, `album_id`, `page_number`, `layout`, `created_at`) VALUES
('1ffd3bb4-e26d-11f1-b2f0-1413335814c8', 'c7e51d90-ed71-4be5-bc15-c456d191a519', 4, '{}', '2026-11-17 12:56:14'),
('1ffd429b-e26d-11f1-b2f0-1413335814c8', 'c7e51d90-ed71-4be5-bc15-c456d191a519', 5, '{}', '2026-11-17 12:56:14'),
('f4f8a3d3-e26a-11f1-b2f0-1413335814c8', 'c7e51d90-ed71-4be5-bc15-c456d191a519', 2, '{}', '2026-11-17 12:40:42'),
('f4f8b04a-e26a-11f1-b2f0-1413335814c8', 'c7e51d90-ed71-4be5-bc15-c456d191a519', 3, '{}', '2026-11-17 12:40:42');

-- --------------------------------------------------------

--
-- Структура таблицы `album_templates`
--

CREATE TABLE `album_templates` (
  `id` char(36) NOT NULL DEFAULT (uuid()),
  `title` varchar(255) NOT NULL,
  `cover_url` text,
  `description` text,
  `page_layouts` json DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `album_templates`
--

INSERT INTO `album_templates` (`id`, `title`, `cover_url`, `description`, `page_layouts`, `is_active`, `created_at`) VALUES
('46fc8a54-e25e-11f1-b2f0-1413335814c8', 'Нежный', 'asset/img/album_covers/cover_1.png', 'Нежное оформление альбома', NULL, 1, '2026-11-17 11:09:57'),
('46fc8f0c-e25e-11f1-b2f0-1413335814c8', 'Цветочный', 'asset/img/album_covers/cover_2.png', 'Цветочное оформление альбома', NULL, 1, '2026-11-17 11:09:57'),
('46fc9034-e25e-11f1-b2f0-1413335814c8', 'Классический', 'asset/img/album_covers/cover_3.png', 'Классическое оформление альбома', NULL, 1, '2026-11-17 11:09:57'),
('b341ad61-e263-11f1-b2f0-1413335814c8', 'Мой первый альбом', '../asset/img/recipe_1.png', 'Тестовый шаблон для редактора', '[{\"type\": \"cover\"}, {\"type\": \"standard\"}, {\"type\": \"standard\"}, {\"type\": \"standard\"}]', 1, '2026-11-17 11:48:46');

-- --------------------------------------------------------

--
-- Структура таблицы `assets`
--

CREATE TABLE `assets` (
  `id` char(36) NOT NULL DEFAULT (uuid()),
  `type` enum('sticker','tape','stamp','frame') NOT NULL,
  `name` varchar(255) NOT NULL,
  `category` enum('desserts','vegetables','meat','drinks','decor','seasonal') DEFAULT NULL,
  `image_url` text NOT NULL,
  `pattern_url` text,
  `width` int DEFAULT NULL,
  `height` int DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `assets`
--

INSERT INTO `assets` (`id`, `type`, `name`, `category`, `image_url`, `pattern_url`, `width`, `height`, `is_active`, `created_at`) VALUES
('b3560661-e263-11f1-b2f0-1413335814c8', 'sticker', 'Клубника', 'desserts', '../asset/img/recipe_1.png', NULL, 100, 100, 1, '2026-11-17 11:48:46'),
('b35823d0-e263-11f1-b2f0-1413335814c8', 'sticker', 'Десерт', 'desserts', '../asset/img/recipe_1.png', NULL, 120, 120, 1, '2026-11-17 11:48:46'),
('b35a42b6-e263-11f1-b2f0-1413335814c8', 'tape', 'Декоративный скотч', NULL, '../asset/img/recipe_1.png', NULL, 150, 40, 1, '2026-11-17 11:48:46'),
('b35c3b13-e263-11f1-b2f0-1413335814c8', 'stamp', 'Кулинарный штамп', NULL, '../asset/img/recipe_1.png', NULL, 100, 100, 1, '2026-11-17 11:48:46'),
('b35e4200-e263-11f1-b2f0-1413335814c8', 'frame', 'Фоторамка', NULL, '../asset/img/recipe_1.png', NULL, 200, 200, 1, '2026-11-17 11:48:46');

-- --------------------------------------------------------

--
-- Структура таблицы `categories`
--

CREATE TABLE `categories` (
  `id` char(36) NOT NULL DEFAULT (uuid()),
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `description` text,
  `image_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `categories`
--

INSERT INTO `categories` (`id`, `name`, `slug`, `description`, `image_url`, `created_at`) VALUES
('35744f70-e215-11f1-8335-1413335814c8', 'категория', 'kategoriya', 'ацуацу', 'asset/img/categories/cat_1794871614_e6a621b7.png', '2026-11-17 02:26:54'),
('7e5ad14b-e115-11f1-8335-1413335814c8', 'Быстро, просто, вкусно', 'fast-simple-tasty', NULL, 'asset/img/image_for_cat/cat_1.png', '2026-11-15 19:56:25'),
('7e5adfe6-e115-11f1-8335-1413335814c8', 'Национальные кухни', 'national-cuisines', NULL, NULL, '2026-11-15 19:56:25'),
('7e5ae186-e115-11f1-8335-1413335814c8', 'Праздничный стол', 'holiday-table', NULL, 'asset/img/image_for_cat/cat_3.png', '2026-11-15 19:56:25'),
('7e5ae243-e115-11f1-8335-1413335814c8', 'На завтрак', 'for-breakfast', NULL, 'asset/img/image_for_cat/cat_4.png', '2026-11-15 19:56:25'),
('7e5ae2c9-e115-11f1-8335-1413335814c8', 'На ужин', 'for-dinner', NULL, 'asset/img/image_for_cat/cat_5.png', '2026-11-15 19:56:25'),
('7e5ae33d-e115-11f1-8335-1413335814c8', 'Рецепты для детей', 'for-kids', NULL, 'asset/img/image_for_cat/cat_6.png', '2026-11-15 19:56:25'),
('7e5ae3ae-e115-11f1-8335-1413335814c8', 'Закуски', 'snacks', 'Закуски', 'asset/img/image_for_cat/cat_7.png', '2026-11-15 19:56:25'),
('7e5ae41f-e115-11f1-8335-1413335814c8', 'Вторые блюда', 'main-courses', NULL, 'asset/img/image_for_cat/cat_8.png', '2026-11-15 19:56:25'),
('7e5ae4c9-e115-11f1-8335-1413335814c8', 'Летние блюда', 'summer-dishes', NULL, 'asset/img/image_for_cat/cat_9.png', '2026-11-15 19:56:25'),
('7e5ae53d-e115-11f1-8335-1413335814c8', 'Гарнир', 'side-dishes', NULL, 'asset/img/image_for_cat/cat_10.png', '2026-11-15 19:56:25'),
('7e5ae5a9-e115-11f1-8335-1413335814c8', 'Напитки', 'drinks', NULL, 'asset/img/image_for_cat/cat_11.png', '2026-11-15 19:56:25'),
('7e5ae7e2-e115-11f1-8335-1413335814c8', 'Десерты', 'desserts', NULL, 'asset/img/image_for_cat/cat_14.png', '2026-11-15 19:56:25'),
('7e5ae84b-e115-11f1-8335-1413335814c8', 'Бутерброды', 'sandwiches', 'пукпукпйкпцукпцуацуацу', 'asset/img/image_for_cat/cat_15.png', '2026-11-15 19:56:25'),
('7e5ae8b6-e115-11f1-8335-1413335814c8', 'Выпечка', 'baking', NULL, 'asset/img/image_for_cat/cat_16.png', '2026-11-15 19:56:25'),
('7e5ae958-e115-11f1-8335-1413335814c8', 'Молочные продукты домашние', 'dairy', NULL, 'asset/img/image_for_cat/cat_17.png', '2026-11-15 19:56:25'),
('7e5aea42-e115-11f1-8335-1413335814c8', 'Супы', 'soups', NULL, 'asset/img/image_for_cat/cat_19.png', '2026-11-15 19:56:25'),
('7e5aeae0-e115-11f1-8335-1413335814c8', 'Заготовки на зиму', 'winter-prep', NULL, 'asset/img/image_for_cat/cat_20.png', '2026-11-15 19:56:25');

-- --------------------------------------------------------

--
-- Структура таблицы `comments`
--

CREATE TABLE `comments` (
  `id` char(36) NOT NULL DEFAULT (uuid()),
  `recipe_id` char(36) NOT NULL,
  `user_id` char(36) NOT NULL,
  `text` text NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `newsletter_subscribers`
--

CREATE TABLE `newsletter_subscribers` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT (uuid()),
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('active','unsubscribed','pending') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `subscribed_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `page_elements`
--

CREATE TABLE `page_elements` (
  `id` char(36) NOT NULL DEFAULT (uuid()),
  `page_id` char(36) NOT NULL,
  `type` enum('photo','text','sticker','tape','stamp','frame','card') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `source_id` char(36) DEFAULT NULL,
  `source_url` text,
  `position_x` double NOT NULL DEFAULT '0',
  `position_y` double NOT NULL DEFAULT '0',
  `width` double NOT NULL DEFAULT '100',
  `height` double NOT NULL DEFAULT '100',
  `rotation` double NOT NULL DEFAULT '0',
  `z_index` int NOT NULL DEFAULT '0',
  `data` json DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `page_elements`
--

INSERT INTO `page_elements` (`id`, `page_id`, `type`, `source_id`, `source_url`, `position_x`, `position_y`, `width`, `height`, `rotation`, `z_index`, `data`, `created_at`, `updated_at`) VALUES
('ccccf309-e275-11f1-b2f0-1413335814c8', 'f4f8a3d3-e26a-11f1-b2f0-1413335814c8', 'photo', '47fa4ffa-e26c-11f1-b2f0-1413335814c8', 'uploads/user_assets/5952ba0e129853b11b70ebbce0ae4878.png', 498.05883375869, 385.05882352941, 200, 99, 0, 2, '{\"text\": \"\", \"time\": \"\", \"title\": \"\", \"calories\": \"\", \"servings\": \"\", \"template\": \"\", \"cover_url\": \"\", \"recipe_id\": \"\"}', '2026-11-17 13:58:20', '2026-11-17 13:58:20');

-- --------------------------------------------------------

--
-- Структура таблицы `recipes`
--

CREATE TABLE `recipes` (
  `id` char(36) NOT NULL DEFAULT (uuid()),
  `title` varchar(255) NOT NULL,
  `description` text,
  `cover_url` text,
  `author_id` char(36) DEFAULT NULL,
  `cooking_time` int DEFAULT NULL,
  `servings` int DEFAULT NULL,
  `calories` int DEFAULT NULL,
  `status` enum('draft','published','hidden','moderation') NOT NULL DEFAULT 'draft',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `recipes`
--

INSERT INTO `recipes` (`id`, `title`, `description`, `cover_url`, `author_id`, `cooking_time`, `servings`, `calories`, `status`, `created_at`, `updated_at`, `deleted_at`) VALUES
('905d1903-bb28-4c21-8801-64a658039be0', 'gergergergerg', 'gerger', NULL, '2f3dd766-e13c-11f1-8335-1413335814c8', 231, 3123, 3123, 'published', '2026-11-17 02:09:29', '2026-11-17 02:19:04', '2026-11-17 02:19:04'),
('c7ea845a-56d0-4b75-b806-90bcb7b2c7a5', '<jho', '', NULL, '2f3dd766-e13c-11f1-8335-1413335814c8', NULL, NULL, NULL, 'moderation', '2026-11-16 22:17:53', '2026-11-17 00:09:24', NULL),
('d5704c75-d2f6-4ddc-9e00-4fa78328a726', 'пукпукпйук пйукпйппукпукпй укпйукпйппукпукпйукпйукпйп', 'пукпукпйукпйукпйп', 'asset/img/recipes/recipe_d5704c75-d2f6-4ddc-9e00-4fa78328a726.png', '2f3dd766-e13c-11f1-8335-1413335814c8', 1290, 4, 2509, 'published', '2026-11-16 18:49:04', '2026-11-16 22:14:30', '2026-11-16 22:14:30'),
('d947e018-e142-11f1-8335-1413335814c8', 'Курица в сливочном соусе', 'Нежная куриная грудка в ароматном сливочном соусе с чесноком и травами. Идеально подходит для семейного ужина.', NULL, NULL, 40, 4, 380, 'published', '2026-11-16 01:21:05', '2026-11-16 17:08:53', NULL),
('d947e6d3-e142-11f1-8335-1413335814c8', 'Паста Карбонара', 'Классическая итальянская паста с беконом, яйцом и пармезаном. Быстро, сытно и невероятно вкусно.', NULL, NULL, 25, 2, 520, 'published', '2026-11-16 01:21:05', '2026-11-16 17:08:56', NULL),
('d947e908-e142-11f1-8335-1413335814c8', 'Лосось с овощами гриль', 'Филе лосося, запечённое с сезонными овощами. Лёгкий и полезный ужин.', '', 'b73a496c-e142-11f1-8335-1413335814c8', 35, 2, 320, 'published', '2026-11-16 01:21:05', '2026-11-16 19:57:10', NULL),
('d947ea32-e142-11f1-8335-1413335814c8', 'Говядина по-строгановски', 'Традиционное блюдо из tender говядины в грибном соусе со сметаной.', '', NULL, 50, 4, 450, 'published', '2026-11-16 01:21:05', '2026-11-16 19:57:12', NULL),
('d947ec73-e142-11f1-8335-1413335814c8', 'Овсянка с ягодами и мёдом', 'Полезная каша с сезонными ягодами, мёдом и орехами. Идеальное начало дня.', '', 'b73a496c-e142-11f1-8335-1413335814c8', 10, 1, 280, 'published', '2026-11-16 01:21:05', '2026-11-16 19:57:11', NULL),
('d947ed6e-e142-11f1-8335-1413335814c8', 'Сырники со сметаной', 'Пышные творожные сырники с золотистой корочкой. Подаются со сметаной и вареньем.', '', NULL, 20, 4, 340, 'published', '2026-11-16 01:21:05', '2026-11-16 19:57:15', NULL),
('d947eec0-e142-11f1-8335-1413335814c8', 'Омлет с овощами', 'Пышный омлет с болгарским перцем, помидорами и сыром. Быстрый и сытный завтрак.', '', NULL, 15, 2, 290, 'published', '2026-11-16 01:21:05', '2026-11-16 19:57:14', NULL),
('d947efbb-e142-11f1-8335-1413335814c8', 'Гранола домашняя', 'Хрустящая гранола с орехами, мёдом и сухофруктами. Готовится один раз — хватает на неделю.', '', 'b73a496c-e142-11f1-8335-1413335814c8', 30, 6, 310, 'published', '2026-11-16 01:21:05', '2026-11-16 19:57:17', NULL),
('d947f0d8-e142-11f1-8335-1413335814c8', 'Тирамису классический', 'Итальянский десерт из печенья савоярди, маскарпоне и кофе. Нежный и ароматный.', '', NULL, 60, 8, 420, 'published', '2026-11-16 01:21:05', '2026-11-16 19:57:19', NULL),
('d947f1d7-e142-11f1-8335-1413335814c8', 'Панна-котта с ягодами', 'Сливочный итальянский десерт с ванилью и ягодным соусом.', '', 'b73a496c-e142-11f1-8335-1413335814c8', 20, 4, 380, 'published', '2026-11-16 01:21:05', '2026-11-16 19:57:18', NULL),
('d947f2d8-e142-11f1-8335-1413335814c8', 'Шоколадный фондан', 'Кекс с жидкой шоколадной серединкой. Подаётся с шариком мороженого.', '', NULL, 25, 2, 510, 'published', '2026-11-16 01:21:05', '2026-11-16 19:57:18', NULL),
('d947f3b5-e142-11f1-8335-1413335814c8', 'Чизкейк Нью-Йорк', 'Классический американский чизкейк с нежной текстурой и песочной основой.', 'asset/image_for_product/image-1.', NULL, 90, 10, 480, 'published', '2026-11-16 01:21:05', '2026-11-16 19:57:21', NULL),
('d947f4ba-e142-11f1-8335-1413335814c8', 'Борщ украинский', 'Наваристый борщ с говядиной, свёклой и пампушками с чесноком.', '', NULL, 120, 6, 220, 'published', '2026-11-16 01:21:05', '2026-11-16 19:57:23', NULL),
('d947f5ba-e142-11f1-8335-1413335814c8', 'Цезарь с курицей', 'Популярный салат с куриной грудкой, салатом романо, сухариками и соусом цезарь.', '', NULL, 25, 2, 350, 'published', '2026-11-16 01:21:05', '2026-11-16 19:57:22', NULL),
('d947f6c2-e142-11f1-8335-1413335814c8', 'Том Ям', 'Тайский острый суп с креветками, грибами и кокосовым молоком.', '', 'b73a496c-e142-11f1-8335-1413335814c8', 40, 4, 280, 'published', '2026-11-16 01:21:05', '2026-11-16 19:57:22', NULL),
('d947f7e9-e142-11f1-8335-1413335814c8', 'Брауни с грецким орехом', 'Шоколадный брауни с хрустящими грецкими орехами. Тает во рту!', '', NULL, 45, 9, 490, 'published', '2026-11-16 01:21:05', '2026-11-16 19:57:26', NULL),
('ed81ded0-901f-4515-bd6d-cda251d204f5', '124124124', '31231245141', NULL, '2f3dd766-e13c-11f1-8335-1413335814c8', 126, 123, 1234, 'published', '2026-11-16 19:01:22', '2026-11-16 19:37:07', '2026-11-16 19:37:07');

-- --------------------------------------------------------

--
-- Структура таблицы `recipe_categories`
--

CREATE TABLE `recipe_categories` (
  `recipe_id` char(36) NOT NULL,
  `category_id` char(36) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `recipe_categories`
--

INSERT INTO `recipe_categories` (`recipe_id`, `category_id`) VALUES
('905d1903-bb28-4c21-8801-64a658039be0', '7e5ad14b-e115-11f1-8335-1413335814c8'),
('905d1903-bb28-4c21-8801-64a658039be0', '7e5ae33d-e115-11f1-8335-1413335814c8'),
('905d1903-bb28-4c21-8801-64a658039be0', '7e5ae84b-e115-11f1-8335-1413335814c8'),
('905d1903-bb28-4c21-8801-64a658039be0', '7e5ae8b6-e115-11f1-8335-1413335814c8'),
('905d1903-bb28-4c21-8801-64a658039be0', '7e5aea42-e115-11f1-8335-1413335814c8'),
('c7ea845a-56d0-4b75-b806-90bcb7b2c7a5', '7e5ad14b-e115-11f1-8335-1413335814c8'),
('c7ea845a-56d0-4b75-b806-90bcb7b2c7a5', '7e5ae84b-e115-11f1-8335-1413335814c8'),
('d5704c75-d2f6-4ddc-9e00-4fa78328a726', '7e5ad14b-e115-11f1-8335-1413335814c8'),
('d947e018-e142-11f1-8335-1413335814c8', '7e5ae2c9-e115-11f1-8335-1413335814c8'),
('d947e6d3-e142-11f1-8335-1413335814c8', '7e5ae2c9-e115-11f1-8335-1413335814c8'),
('d947e908-e142-11f1-8335-1413335814c8', '7e5ae2c9-e115-11f1-8335-1413335814c8'),
('d947ea32-e142-11f1-8335-1413335814c8', '7e5ae2c9-e115-11f1-8335-1413335814c8'),
('d947ec73-e142-11f1-8335-1413335814c8', '7e5ae243-e115-11f1-8335-1413335814c8'),
('d947ed6e-e142-11f1-8335-1413335814c8', '7e5ae243-e115-11f1-8335-1413335814c8'),
('d947eec0-e142-11f1-8335-1413335814c8', '7e5ae243-e115-11f1-8335-1413335814c8'),
('d947efbb-e142-11f1-8335-1413335814c8', '7e5ae243-e115-11f1-8335-1413335814c8'),
('d947f0d8-e142-11f1-8335-1413335814c8', '7e5ae7e2-e115-11f1-8335-1413335814c8'),
('d947f1d7-e142-11f1-8335-1413335814c8', '7e5ae7e2-e115-11f1-8335-1413335814c8'),
('d947f2d8-e142-11f1-8335-1413335814c8', '7e5ae7e2-e115-11f1-8335-1413335814c8'),
('d947f3b5-e142-11f1-8335-1413335814c8', '7e5ae7e2-e115-11f1-8335-1413335814c8'),
('d947f4ba-e142-11f1-8335-1413335814c8', '7e5aea42-e115-11f1-8335-1413335814c8'),
('d947f6c2-e142-11f1-8335-1413335814c8', '7e5aea42-e115-11f1-8335-1413335814c8'),
('d947f7e9-e142-11f1-8335-1413335814c8', '7e5ae7e2-e115-11f1-8335-1413335814c8'),
('ed81ded0-901f-4515-bd6d-cda251d204f5', '7e5ad14b-e115-11f1-8335-1413335814c8'),
('ed81ded0-901f-4515-bd6d-cda251d204f5', '7e5ae84b-e115-11f1-8335-1413335814c8'),
('ed81ded0-901f-4515-bd6d-cda251d204f5', '7e5aeae0-e115-11f1-8335-1413335814c8');

-- --------------------------------------------------------

--
-- Структура таблицы `recipe_ingredients`
--

CREATE TABLE `recipe_ingredients` (
  `id` char(36) NOT NULL DEFAULT (uuid()),
  `recipe_id` char(36) NOT NULL,
  `name` varchar(255) NOT NULL,
  `amount` varchar(100) DEFAULT NULL,
  `order_index` int NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `recipe_ingredients`
--

INSERT INTO `recipe_ingredients` (`id`, `recipe_id`, `name`, `amount`, `order_index`) VALUES
('09e4b147-8312-4402-8011-92733f52e72e', 'd5704c75-d2f6-4ddc-9e00-4fa78328a726', 'Майонез', '41241', 2),
('ae7a56f8-0e1c-49bc-91ca-2d3dc15a2af8', 'ed81ded0-901f-4515-bd6d-cda251d204f5', 'ацуацуа', '231', 1),
('bdac10b0-6506-4ce6-9288-afe1c68dbc70', '905d1903-bb28-4c21-8801-64a658039be0', 'gergergwergwegr', '2312', 1),
('d949dc73-e142-11f1-8335-1413335814c8', 'd947e018-e142-11f1-8335-1413335814c8', 'Куриная грудка', '500 г', 1),
('d949eb07-e142-11f1-8335-1413335814c8', 'd947e018-e142-11f1-8335-1413335814c8', 'Сливки 20%', '200 мл', 2),
('d94a05a9-e142-11f1-8335-1413335814c8', 'd947e018-e142-11f1-8335-1413335814c8', 'Соль', 'по вкусу', 4),
('d94a14f1-e142-11f1-8335-1413335814c8', 'd947e018-e142-11f1-8335-1413335814c8', 'Перец чёрный', 'по вкусу', 5),
('d94a242c-e142-11f1-8335-1413335814c8', 'd947e018-e142-11f1-8335-1413335814c8', 'Зелень', 'для украшения', 6),
('d94a3133-e142-11f1-8335-1413335814c8', 'd947e6d3-e142-11f1-8335-1413335814c8', 'Спагетти', '200 г', 1),
('d94a3c93-e142-11f1-8335-1413335814c8', 'd947e6d3-e142-11f1-8335-1413335814c8', 'Бекон', '150 г', 2),
('d94a4988-e142-11f1-8335-1413335814c8', 'd947e6d3-e142-11f1-8335-1413335814c8', 'Яйца', '2 шт', 3),
('d94a569c-e142-11f1-8335-1413335814c8', 'd947e6d3-e142-11f1-8335-1413335814c8', 'Пармезан', '50 г', 4),
('d94a640e-e142-11f1-8335-1413335814c8', 'd947e6d3-e142-11f1-8335-1413335814c8', 'Чеснок', '2 зубчика', 5),
('d94a707c-e142-11f1-8335-1413335814c8', 'd947ec73-e142-11f1-8335-1413335814c8', 'Овсяные хлопья', '80 г', 1),
('d94a7e3c-e142-11f1-8335-1413335814c8', 'd947ec73-e142-11f1-8335-1413335814c8', 'Молоко', '200 мл', 2),
('d94a9852-e142-11f1-8335-1413335814c8', 'd947ec73-e142-11f1-8335-1413335814c8', 'Мёд', '1 ст.л.', 4),
('d94ab115-e142-11f1-8335-1413335814c8', 'd947f0d8-e142-11f1-8335-1413335814c8', 'Маскарпоне', '500 г', 1),
('d94abff6-e142-11f1-8335-1413335814c8', 'd947f0d8-e142-11f1-8335-1413335814c8', 'Печенье Савоярди', '200 г', 2),
('d94acdd5-e142-11f1-8335-1413335814c8', 'd947f0d8-e142-11f1-8335-1413335814c8', 'Яйца', '4 шт', 3),
('d94adb54-e142-11f1-8335-1413335814c8', 'd947f0d8-e142-11f1-8335-1413335814c8', 'Сахар', '100 г', 4),
('d94ae75b-e142-11f1-8335-1413335814c8', 'd947f0d8-e142-11f1-8335-1413335814c8', 'Кофе эспрессо', '300 мл', 5),
('d94af3ea-e142-11f1-8335-1413335814c8', 'd947f0d8-e142-11f1-8335-1413335814c8', 'Какао', '2 ст.л.', 6),
('d94b0090-e142-11f1-8335-1413335814c8', 'd947f4ba-e142-11f1-8335-1413335814c8', 'Говядина', '500 г', 1),
('d94b0f03-e142-11f1-8335-1413335814c8', 'd947f4ba-e142-11f1-8335-1413335814c8', 'Свёкла', '2 шт', 2),
('d94b1d36-e142-11f1-8335-1413335814c8', 'd947f4ba-e142-11f1-8335-1413335814c8', 'Капуста', '300 г', 3),
('d94b374d-e142-11f1-8335-1413335814c8', 'd947f4ba-e142-11f1-8335-1413335814c8', 'Морковь', '1 шт', 5),
('d94b4346-e142-11f1-8335-1413335814c8', 'd947f4ba-e142-11f1-8335-1413335814c8', 'Лук', '1 шт', 6),
('d94b5113-e142-11f1-8335-1413335814c8', 'd947f4ba-e142-11f1-8335-1413335814c8', 'Томатная паста', '2 ст.л.', 7),
('e7a34203-1fda-4077-ac3e-a069058597b3', 'd5704c75-d2f6-4ddc-9e00-4fa78328a726', 'Картофель', '234', 1);

-- --------------------------------------------------------

--
-- Структура таблицы `recipe_steps`
--

CREATE TABLE `recipe_steps` (
  `id` char(36) NOT NULL DEFAULT (uuid()),
  `recipe_id` char(36) NOT NULL,
  `step_number` int NOT NULL,
  `text` text NOT NULL,
  `image_url` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `recipe_steps`
--

INSERT INTO `recipe_steps` (`id`, `recipe_id`, `step_number`, `text`, `image_url`) VALUES
('5999f648-d08d-471d-bfd9-34256103508c', 'ed81ded0-901f-4515-bd6d-cda251d204f5', 1, 'шаг 2', 'asset/img/recipes/steps/step_ed81ded0-901f-4515-bd6d-cda251d204f5_1_1794844882.png'),
('5f0b0005-ef72-4d8a-b3bf-4e31c6df131e', '905d1903-bb28-4c21-8801-64a658039be0', 1, 'wegwergwergwerg', NULL),
('75f520a9-fbe5-4574-acbf-b02c9b607e66', 'd5704c75-d2f6-4ddc-9e00-4fa78328a726', 1, 'ШАГ 1', 'asset/img/recipes/steps/step_d5704c75-d2f6-4ddc-9e00-4fa78328a726_1_1794844144.png'),
('7bba4fd9-e143-11f1-8335-1413335814c8', 'd947e018-e142-11f1-8335-1413335814c8', 1, 'Куриное филе промойте, обсушите и нарежьте средними кусочками. Посолите и поперчите по вкусу.', NULL),
('7bbd835f-e143-11f1-8335-1413335814c8', 'd947e018-e142-11f1-8335-1413335814c8', 2, 'На сковороде разогрейте растительное масло. Обжарьте курицу на сильном огне до золотистой корочки со всех сторон (около 5-7 минут).', NULL),
('7bbfa155-e143-11f1-8335-1413335814c8', 'd947e018-e142-11f1-8335-1413335814c8', 3, 'Добавьте мелко нарезанный чеснок и обжаривайте ещё 1 минуту до появления аромата.', NULL),
('7bc1a381-e143-11f1-8335-1413335814c8', 'd947e018-e142-11f1-8335-1413335814c8', 4, 'Влейте сливки, убавьте огонь до минимума. Тушите под крышкой 15-20 минут до загустения соуса.', NULL),
('7bc3a3ed-e143-11f1-8335-1413335814c8', 'd947e018-e142-11f1-8335-1413335814c8', 5, 'В конце добавьте свежие или сушёные травы (укроп, петрушку, тимьян). Перемешайте и снимите с огня.', NULL),
('7bc5d0cb-e143-11f1-8335-1413335814c8', 'd947e018-e142-11f1-8335-1413335814c8', 6, 'Подавайте горячим с гарниром: рисом, пастой или картофельным пюре. Украсьте свежей зеленью.', NULL),
('9044215d-9e79-4ffa-b7c6-d9594793eaf6', 'd5704c75-d2f6-4ddc-9e00-4fa78328a726', 2, 'ШАГ 1', 'asset/img/recipes/steps/step_d5704c75-d2f6-4ddc-9e00-4fa78328a726_2_1794844144.png'),
('aee4b771-3bb0-43e7-bc9a-6032fa06d8c4', 'd5704c75-d2f6-4ddc-9e00-4fa78328a726', 3, 'ШАГ 1', 'asset/img/recipes/steps/step_d5704c75-d2f6-4ddc-9e00-4fa78328a726_3_1794844144.png');

-- --------------------------------------------------------

--
-- Структура таблицы `recipe_tags`
--

CREATE TABLE `recipe_tags` (
  `recipe_id` char(36) NOT NULL,
  `tag_id` char(36) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `recipe_tags`
--

INSERT INTO `recipe_tags` (`recipe_id`, `tag_id`) VALUES
('905d1903-bb28-4c21-8801-64a658039be0', '126ea97a-1484-416b-bae4-534d764b0043'),
('905d1903-bb28-4c21-8801-64a658039be0', '85cf01b0-ae48-486a-91b4-16a6d5e914bf'),
('905d1903-bb28-4c21-8801-64a658039be0', '9352714a-f574-4973-9500-43dc46841bef'),
('ed81ded0-901f-4515-bd6d-cda251d204f5', '1f7b31c7-366a-4968-9a16-d22484c57785'),
('ed81ded0-901f-4515-bd6d-cda251d204f5', '74dcf34f-8626-49ab-80ee-464d9b0d3d40'),
('ed81ded0-901f-4515-bd6d-cda251d204f5', 'be07c17b-efe0-45d7-9f1a-775214d71d73'),
('ed81ded0-901f-4515-bd6d-cda251d204f5', 'c53a48c1-430f-473e-ae11-0eccd2826749'),
('ed81ded0-901f-4515-bd6d-cda251d204f5', 'c772fe03-049a-4553-8268-3e5477a146f5'),
('ed81ded0-901f-4515-bd6d-cda251d204f5', 'f3ad408b-3c06-40fd-a359-1f186485b786');

-- --------------------------------------------------------

--
-- Структура таблицы `subscriptions`
--

CREATE TABLE `subscriptions` (
  `follower_id` char(36) NOT NULL,
  `following_id` char(36) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ;

--
-- Дамп данных таблицы `subscriptions`
--

INSERT INTO `subscriptions` (`follower_id`, `following_id`, `created_at`) VALUES
('2f3dd766-e13c-11f1-8335-1413335814c8', 'b73a496c-e142-11f1-8335-1413335814c8', '2026-11-17 02:05:57');

-- --------------------------------------------------------

--
-- Структура таблицы `tags`
--

CREATE TABLE `tags` (
  `id` char(36) NOT NULL DEFAULT (uuid()),
  `name` varchar(100) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `tags`
--

INSERT INTO `tags` (`id`, `name`, `created_at`) VALUES
('126ea97a-1484-416b-bae4-534d764b0043', 'fwefwe', '2026-11-17 02:09:29'),
('1dc871a6-e1aa-11f1-8335-1413335814c8', '2231', '2026-11-16 13:40:18'),
('1f7b31c7-366a-4968-9a16-d22484c57785', 'суп', '2026-11-16 19:01:22'),
('22623c5e-e1aa-11f1-8335-1413335814c8', 'n\'u 2', '2026-11-16 13:40:26'),
('71d1b13c-e1d7-11f1-8335-1413335814c8', 'рекрк', '2026-11-16 19:04:46'),
('74dcf34f-8626-49ab-80ee-464d9b0d3d40', 'ке', '2026-11-16 19:01:22'),
('74e5b609-e1d7-11f1-8335-1413335814c8', 'уйцуй', '2026-11-16 19:04:52'),
('85cf01b0-ae48-486a-91b4-16a6d5e914bf', 'kyuky', '2026-11-17 02:09:29'),
('9352714a-f574-4973-9500-43dc46841bef', 'jtyjt', '2026-11-17 02:09:29'),
('be07c17b-efe0-45d7-9f1a-775214d71d73', 'гш', '2026-11-16 19:01:22'),
('c53a48c1-430f-473e-ae11-0eccd2826749', 'ауц', '2026-11-16 19:01:22'),
('c772fe03-049a-4553-8268-3e5477a146f5', 'борщ', '2026-11-16 19:01:22'),
('f3ad408b-3c06-40fd-a359-1f186485b786', 'ьти', '2026-11-16 19:01:22');

-- --------------------------------------------------------

--
-- Структура таблицы `users`
--

CREATE TABLE `users` (
  `id` char(36) NOT NULL DEFAULT (uuid()),
  `email` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `username` varchar(50) NOT NULL,
  `avatar_url` text,
  `role` enum('user','admin','moderator') NOT NULL DEFAULT 'user',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `users`
--

INSERT INTO `users` (`id`, `email`, `password_hash`, `username`, `avatar_url`, `role`, `created_at`, `updated_at`) VALUES
('2f3dd766-e13c-11f1-8335-1413335814c8', 'admin@mail.ru', '$2y$10$aRhIkebfmAkwTOz6qf95/OcqemzQutuVEj4mhfTgilxliVswEbZFC', 'frea', 'asset/img/avatars/avatar_2f3dd766-e13c-11f1-8335-1413335814c8_1794853183.png', 'user', '2026-11-16 00:33:23', '2026-11-17 10:46:54'),
('b73a496c-e142-11f1-8335-1413335814c8', 'elena@test.com', '$2y$10$YourHashHere', 'Елена', NULL, 'moderator', '2026-11-16 01:20:08', '2026-11-16 01:20:08');

-- --------------------------------------------------------

--
-- Структура таблицы `user_assets`
--

CREATE TABLE `user_assets` (
  `id` char(36) NOT NULL DEFAULT (uuid()),
  `user_id` char(36) NOT NULL,
  `type` enum('photo','sticker','tape','stamp','frame') NOT NULL,
  `url` text NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `user_assets`
--

INSERT INTO `user_assets` (`id`, `user_id`, `type`, `url`, `name`, `created_at`) VALUES
('065a05d3-e275-11f1-b2f0-1413335814c8', '2f3dd766-e13c-11f1-8335-1413335814c8', 'photo', 'uploads/user_assets/4273d90a18c4fc629044a31aff3517d5.png', 'Снимок экрана 2026-11-15 111151.png', '2026-11-17 13:52:47'),
('47fa4ffa-e26c-11f1-b2f0-1413335814c8', '2f3dd766-e13c-11f1-8335-1413335814c8', 'photo', 'uploads/user_assets/5952ba0e129853b11b70ebbce0ae4878.png', 'Снимок экрана 2026-11-02 145413.png', '2026-11-17 12:50:11'),
('db3ec408-e26d-11f1-b2f0-1413335814c8', '2f3dd766-e13c-11f1-8335-1413335814c8', 'photo', 'uploads/user_assets/2d66c8a60fab980bfc805dd6831319b2.png', 'Снимок экрана 2026-11-02 150318.png', '2026-11-17 13:01:28'),
('dcd8d398-e26d-11f1-b2f0-1413335814c8', '2f3dd766-e13c-11f1-8335-1413335814c8', 'photo', 'uploads/user_assets/fc92efb0a1d636475b30bd7f66d7bde7.png', 'Снимок экрана 2026-11-02 153756.png', '2026-11-17 13:01:30'),
('de93d40f-e26d-11f1-b2f0-1413335814c8', '2f3dd766-e13c-11f1-8335-1413335814c8', 'photo', 'uploads/user_assets/faf8e3adb1c47a809fd5983c6360331f.png', 'Снимок экрана 2026-11-02 005236.png', '2026-11-17 13:01:33'),
('e04158d4-e26d-11f1-b2f0-1413335814c8', '2f3dd766-e13c-11f1-8335-1413335814c8', 'photo', 'uploads/user_assets/12dbdb831f6bb6c90db74ec4a395379b.png', 'Снимок экрана 2026-11-02 153033.png', '2026-11-17 13:01:36'),
('e41f52f7-e26d-11f1-b2f0-1413335814c8', '2f3dd766-e13c-11f1-8335-1413335814c8', 'photo', 'uploads/user_assets/be24b2f0ca5bde96d8ff0a23c5788b5e.png', 'Снимок экрана 2026-11-15 111101.png', '2026-11-17 13:01:43'),
('e6990939-e26d-11f1-b2f0-1413335814c8', '2f3dd766-e13c-11f1-8335-1413335814c8', 'photo', 'uploads/user_assets/388e5cbe426ffdda92ec3e83cbe7d770.png', 'Снимок экрана 2026-11-15 105534.png', '2026-11-17 13:01:47'),
('e7d0ec4f-e26c-11f1-b2f0-1413335814c8', '2f3dd766-e13c-11f1-8335-1413335814c8', 'photo', 'uploads/user_assets/118e61dc16c8c1b8510a3633d89eb361.png', 'Снимок экрана 2026-11-02 153756.png', '2026-11-17 12:54:39');

-- --------------------------------------------------------

--
-- Структура таблицы `user_favorites`
--

CREATE TABLE `user_favorites` (
  `id` char(36) NOT NULL DEFAULT (uuid()),
  `user_id` char(36) NOT NULL,
  `recipe_id` char(36) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `user_favorites`
--

INSERT INTO `user_favorites` (`id`, `user_id`, `recipe_id`, `created_at`) VALUES
('50fb3331-e1f6-11f1-8335-1413335814c8', '2f3dd766-e13c-11f1-8335-1413335814c8', 'd947eec0-e142-11f1-8335-1413335814c8', '2026-11-16 22:45:46');

--
-- Индексы сохранённых таблиц
--

--
-- Индексы таблицы `albums`
--
ALTER TABLE `albums`
  ADD PRIMARY KEY (`id`),
  ADD KEY `template_id` (`template_id`),
  ADD KEY `idx_albums_user_id` (`user_id`);

--
-- Индексы таблицы `album_pages`
--
ALTER TABLE `album_pages`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_album_page` (`album_id`,`page_number`),
  ADD KEY `idx_album_pages_album_id` (`album_id`,`page_number`);

--
-- Индексы таблицы `album_templates`
--
ALTER TABLE `album_templates`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `assets`
--
ALTER TABLE `assets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_assets_type` (`type`),
  ADD KEY `idx_assets_category` (`category`);

--
-- Индексы таблицы `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `idx_categories_slug` (`slug`);

--
-- Индексы таблицы `comments`
--
ALTER TABLE `comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_comments_recipe_id` (`recipe_id`,`created_at`),
  ADD KEY `idx_comments_user_id` (`user_id`);

--
-- Индексы таблицы `newsletter_subscribers`
--
ALTER TABLE `newsletter_subscribers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_newsletter_email` (`email`),
  ADD KEY `idx_newsletter_status` (`status`);

--
-- Индексы таблицы `page_elements`
--
ALTER TABLE `page_elements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_page_elements_page_id` (`page_id`,`z_index`),
  ADD KEY `idx_page_elements_type` (`page_id`,`type`);

--
-- Индексы таблицы `recipes`
--
ALTER TABLE `recipes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_recipes_author_id` (`author_id`),
  ADD KEY `idx_recipes_status` (`status`),
  ADD KEY `idx_recipes_created_at` (`created_at`),
  ADD KEY `idx_recipes_deleted_at` (`deleted_at`);

--
-- Индексы таблицы `recipe_categories`
--
ALTER TABLE `recipe_categories`
  ADD PRIMARY KEY (`recipe_id`,`category_id`),
  ADD KEY `idx_recipe_categories_recipe_id` (`recipe_id`),
  ADD KEY `idx_recipe_categories_category_id` (`category_id`);

--
-- Индексы таблицы `recipe_ingredients`
--
ALTER TABLE `recipe_ingredients`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_recipe_ingredients_recipe_id` (`recipe_id`);

--
-- Индексы таблицы `recipe_steps`
--
ALTER TABLE `recipe_steps`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_recipe_steps_recipe_id` (`recipe_id`);

--
-- Индексы таблицы `recipe_tags`
--
ALTER TABLE `recipe_tags`
  ADD PRIMARY KEY (`recipe_id`,`tag_id`),
  ADD KEY `idx_recipe_tags_recipe_id` (`recipe_id`),
  ADD KEY `idx_recipe_tags_tag_id` (`tag_id`);

--
-- Индексы таблицы `subscriptions`
--
ALTER TABLE `subscriptions`
  ADD PRIMARY KEY (`follower_id`,`following_id`),
  ADD KEY `idx_subscriptions_follower_id` (`follower_id`),
  ADD KEY `idx_subscriptions_following_id` (`following_id`);

--
-- Индексы таблицы `tags`
--
ALTER TABLE `tags`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `idx_users_email` (`email`),
  ADD KEY `idx_users_username` (`username`);

--
-- Индексы таблицы `user_assets`
--
ALTER TABLE `user_assets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_assets_user_id` (`user_id`);

--
-- Индексы таблицы `user_favorites`
--
ALTER TABLE `user_favorites`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_recipe` (`user_id`,`recipe_id`),
  ADD KEY `idx_user_favorites_user_id` (`user_id`),
  ADD KEY `idx_user_favorites_recipe_id` (`recipe_id`);

--
-- Ограничения внешнего ключа сохраненных таблиц
--

--
-- Ограничения внешнего ключа таблицы `albums`
--
ALTER TABLE `albums`
  ADD CONSTRAINT `albums_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `albums_ibfk_2` FOREIGN KEY (`template_id`) REFERENCES `album_templates` (`id`) ON DELETE SET NULL;

--
-- Ограничения внешнего ключа таблицы `album_pages`
--
ALTER TABLE `album_pages`
  ADD CONSTRAINT `album_pages_ibfk_1` FOREIGN KEY (`album_id`) REFERENCES `albums` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `comments`
--
ALTER TABLE `comments`
  ADD CONSTRAINT `comments_ibfk_1` FOREIGN KEY (`recipe_id`) REFERENCES `recipes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `comments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `page_elements`
--
ALTER TABLE `page_elements`
  ADD CONSTRAINT `page_elements_ibfk_1` FOREIGN KEY (`page_id`) REFERENCES `album_pages` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `recipes`
--
ALTER TABLE `recipes`
  ADD CONSTRAINT `recipes_ibfk_1` FOREIGN KEY (`author_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Ограничения внешнего ключа таблицы `recipe_categories`
--
ALTER TABLE `recipe_categories`
  ADD CONSTRAINT `recipe_categories_ibfk_1` FOREIGN KEY (`recipe_id`) REFERENCES `recipes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `recipe_categories_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `recipe_ingredients`
--
ALTER TABLE `recipe_ingredients`
  ADD CONSTRAINT `recipe_ingredients_ibfk_1` FOREIGN KEY (`recipe_id`) REFERENCES `recipes` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `recipe_steps`
--
ALTER TABLE `recipe_steps`
  ADD CONSTRAINT `recipe_steps_ibfk_1` FOREIGN KEY (`recipe_id`) REFERENCES `recipes` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `recipe_tags`
--
ALTER TABLE `recipe_tags`
  ADD CONSTRAINT `recipe_tags_ibfk_1` FOREIGN KEY (`recipe_id`) REFERENCES `recipes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `recipe_tags_ibfk_2` FOREIGN KEY (`tag_id`) REFERENCES `tags` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `subscriptions`
--
ALTER TABLE `subscriptions`
  ADD CONSTRAINT `subscriptions_ibfk_1` FOREIGN KEY (`follower_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `subscriptions_ibfk_2` FOREIGN KEY (`following_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `user_assets`
--
ALTER TABLE `user_assets`
  ADD CONSTRAINT `user_assets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `user_favorites`
--
ALTER TABLE `user_favorites`
  ADD CONSTRAINT `user_favorites_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_favorites_ibfk_2` FOREIGN KEY (`recipe_id`) REFERENCES `recipes` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
