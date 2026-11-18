-- BATAVIAS DEMO DATABASE
-- For GitHub/public portfolio demonstration only.
-- Import into a NEW database. This script drops existing tables in batavias_demo.

CREATE DATABASE IF NOT EXISTS batavias_demo CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE batavias_demo;

SET FOREIGN_KEY_CHECKS=0;
DROP TABLE IF EXISTS api_logs,system_logs,check_ins,tickets,payments,order_items,orders,ticket_types,reviews,notifications,coupons,events,categories,organizations,users;
SET FOREIGN_KEY_CHECKS=1;

CREATE TABLE users(
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 name VARCHAR(120) NOT NULL,
 email VARCHAR(190) NOT NULL UNIQUE,
 password VARCHAR(255) NOT NULL,
 role ENUM('user','admin','developer','organizer') NOT NULL DEFAULT 'user',
 status ENUM('active','inactive','suspended') NOT NULL DEFAULT 'active',
 created_at DATETIME NOT NULL,
 updated_at DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE organizations(
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 name VARCHAR(160) NOT NULL,
 slug VARCHAR(180) NOT NULL UNIQUE,
 description TEXT NULL,
 owner_id BIGINT UNSIGNED NOT NULL,
 created_at DATETIME NOT NULL,
 FOREIGN KEY(owner_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE categories(
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 name VARCHAR(100) NOT NULL UNIQUE,
 slug VARCHAR(120) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE events(
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 organizer_id BIGINT UNSIGNED NOT NULL,
 organization_id BIGINT UNSIGNED NULL,
 category_id BIGINT UNSIGNED NULL,
 title VARCHAR(180) NOT NULL,
 slug VARCHAR(200) NOT NULL UNIQUE,
 description TEXT NOT NULL,
 city VARCHAR(120) NOT NULL,
 venue VARCHAR(180) NOT NULL,
 cover_image VARCHAR(255) NULL,
 start_date DATETIME NOT NULL,
 end_date DATETIME NOT NULL,
 registration_deadline DATETIME NOT NULL,
 capacity INT UNSIGNED NOT NULL DEFAULT 100,
 status ENUM('draft','pending','published','rejected','cancelled','completed') NOT NULL DEFAULT 'draft',
 rejection_reason VARCHAR(500) NULL,
 created_at DATETIME NOT NULL,
 updated_at DATETIME NULL,
 FOREIGN KEY(organizer_id) REFERENCES users(id),
 FOREIGN KEY(organization_id) REFERENCES organizations(id) ON DELETE SET NULL,
 FOREIGN KEY(category_id) REFERENCES categories(id) ON DELETE SET NULL,
 INDEX(status,start_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE ticket_types(
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 event_id BIGINT UNSIGNED NOT NULL,
 name VARCHAR(100) NOT NULL,
 price DECIMAL(12,2) NOT NULL DEFAULT 0,
 quota INT UNSIGNED NOT NULL,
 sale_start DATETIME NULL,
 sale_end DATETIME NULL,
 is_active TINYINT(1) NOT NULL DEFAULT 1,
 FOREIGN KEY(event_id) REFERENCES events(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE orders(
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 user_id BIGINT UNSIGNED NOT NULL,
 order_number VARCHAR(40) NOT NULL UNIQUE,
 total_amount DECIMAL(12,2) NOT NULL,
 status ENUM('pending','paid','expired','cancelled','refunded') NOT NULL DEFAULT 'pending',
 expires_at DATETIME NULL,
 created_at DATETIME NOT NULL,
 FOREIGN KEY(user_id) REFERENCES users(id),
 INDEX(user_id,status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE order_items(
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 order_id BIGINT UNSIGNED NOT NULL,
 ticket_type_id BIGINT UNSIGNED NOT NULL,
 quantity INT UNSIGNED NOT NULL,
 unit_price DECIMAL(12,2) NOT NULL,
 subtotal DECIMAL(12,2) NOT NULL,
 FOREIGN KEY(order_id) REFERENCES orders(id) ON DELETE CASCADE,
 FOREIGN KEY(ticket_type_id) REFERENCES ticket_types(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE payments(
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 order_id BIGINT UNSIGNED NOT NULL,
 method ENUM('bank_transfer','ewallet','card_simulation') NOT NULL,
 status ENUM('pending','success','failed','refunded') NOT NULL DEFAULT 'pending',
 amount DECIMAL(12,2) NOT NULL,
 reference VARCHAR(80) NOT NULL UNIQUE,
 paid_at DATETIME NULL,
 created_at DATETIME NOT NULL,
 FOREIGN KEY(order_id) REFERENCES orders(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE tickets(
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 order_item_id BIGINT UNSIGNED NOT NULL,
 ticket_number VARCHAR(50) NOT NULL UNIQUE,
 qr_token_hash CHAR(64) NOT NULL UNIQUE,
 status ENUM('valid','used','cancelled') NOT NULL DEFAULT 'valid',
 created_at DATETIME NOT NULL,
 FOREIGN KEY(order_item_id) REFERENCES order_items(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE check_ins(
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 ticket_id BIGINT UNSIGNED NOT NULL UNIQUE,
 checked_by BIGINT UNSIGNED NOT NULL,
 checked_at DATETIME NOT NULL,
 FOREIGN KEY(ticket_id) REFERENCES tickets(id),
 FOREIGN KEY(checked_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE coupons(
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 code VARCHAR(40) NOT NULL UNIQUE,
 discount_type ENUM('percent','fixed') NOT NULL,
 discount_value DECIMAL(10,2) NOT NULL,
 usage_limit INT UNSIGNED NULL,
 expires_at DATETIME NULL,
 is_active TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE notifications(
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 user_id BIGINT UNSIGNED NOT NULL,
 title VARCHAR(160) NOT NULL,
 message TEXT NOT NULL,
 read_at DATETIME NULL,
 created_at DATETIME NOT NULL,
 FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE reviews(
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 event_id BIGINT UNSIGNED NOT NULL,
 user_id BIGINT UNSIGNED NOT NULL,
 rating TINYINT UNSIGNED NOT NULL,
 comment TEXT NULL,
 created_at DATETIME NOT NULL,
 FOREIGN KEY(event_id) REFERENCES events(id),
 FOREIGN KEY(user_id) REFERENCES users(id),
 UNIQUE(event_id,user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE api_logs(
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 method VARCHAR(10) NOT NULL,
 path VARCHAR(255) NOT NULL,
 status_code SMALLINT NOT NULL,
 duration_ms INT NOT NULL DEFAULT 0,
 created_at DATETIME NOT NULL,
 INDEX(created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE system_logs(
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 level ENUM('info','warning','error','security') NOT NULL,
 category VARCHAR(60) NOT NULL,
 message VARCHAR(500) NOT NULL,
 user_id BIGINT UNSIGNED NULL,
 created_at DATETIME NOT NULL,
 FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE SET NULL,
 INDEX(level,created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seeded accounts are placeholders; set your own passwords locally before use.
INSERT INTO users(name,email,password,role,status,created_at) VALUES
('Batavias Admin','admin@batavias.test','$2y$12$3w10Vgp37dV.2iwC/OXF1uZmsBxhemPQx/j3NqgyiRoH/Qu4AWdfe','admin','active',NOW()),
('Batavias Developer','developer@batavias.test','$2y$12$3w10Vgp37dV.2iwC/OXF1uZmsBxhemPQx/j3NqgyiRoH/Qu4AWdfe','developer','active',NOW()),
('Jakarta Event Organizer','organizer@batavias.test','$2y$12$3w10Vgp37dV.2iwC/OXF1uZmsBxhemPQx/j3NqgyiRoH/Qu4AWdfe','organizer','active',NOW()),
('Batavias Demo User','user@batavias.test','$2y$12$3w10Vgp37dV.2iwC/OXF1uZmsBxhemPQx/j3NqgyiRoH/Qu4AWdfe','user','active',NOW());

INSERT INTO categories(name,slug) VALUES
('Technology','technology'),('Business','business'),('Design','design'),('Music','music'),
('Education','education'),('Workshop','workshop'),('Lifestyle','lifestyle'),('Community','community');

INSERT INTO organizations(name,slug,description,owner_id,created_at) VALUES
('Jakarta Digital Community','jakarta-digital-community','Komunitas teknologi dan inovasi.',3,NOW());

INSERT INTO events(organizer_id,organization_id,category_id,title,slug,description,city,venue,start_date,end_date,registration_deadline,capacity,status,created_at) VALUES
(3,1,1,'Jakarta Technology Conference','jakarta-technology-conference','Konferensi teknologi, cloud, API, dan AI untuk profesional Indonesia.','Jakarta','Jakarta Convention Center',DATE_ADD(NOW(),INTERVAL 20 DAY),DATE_ADD(NOW(),INTERVAL 20 DAY) + INTERVAL 8 HOUR,DATE_ADD(NOW(),INTERVAL 18 DAY),500,'published',NOW()),
(3,1,3,'UI/UX Design Workshop','ui-ux-design-workshop','Workshop praktik riset pengguna dan desain produk digital.','Jakarta','M Bloc Space',DATE_ADD(NOW(),INTERVAL 30 DAY),DATE_ADD(NOW(),INTERVAL 30 DAY) + INTERVAL 5 HOUR,DATE_ADD(NOW(),INTERVAL 28 DAY),100,'published',NOW()),
(3,1,2,'Startup Founders Meetup','startup-founders-meetup','Diskusi dan networking pendiri startup.','Tangerang Selatan','The Breeze BSD',DATE_ADD(NOW(),INTERVAL 40 DAY),DATE_ADD(NOW(),INTERVAL 40 DAY) + INTERVAL 4 HOUR,DATE_ADD(NOW(),INTERVAL 38 DAY),150,'pending',NOW());

INSERT INTO ticket_types(event_id,name,price,quota,is_active) VALUES
(1,'Regular',150000,300,1),(1,'VIP',350000,50,1),(2,'Workshop Pass',75000,80,1),(3,'Early Bird',50000,100,1);

INSERT INTO coupons(code,discount_type,discount_value,usage_limit,is_active) VALUES
('BATAVIAS10','percent',10,100,1);

-- Sample completed order for dashboard demonstration.
INSERT INTO orders(user_id,order_number,total_amount,status,created_at) VALUES
(4,'BTV-DEMO-0001',150000,'paid',NOW() - INTERVAL 2 DAY);
INSERT INTO order_items(order_id,ticket_type_id,quantity,unit_price,subtotal) VALUES
(1,1,1,150000,150000);
INSERT INTO payments(order_id,method,status,amount,reference,paid_at,created_at) VALUES
(1,'ewallet','success',150000,'PAY-BTV-DEMO-0001',NOW() - INTERVAL 2 DAY,NOW() - INTERVAL 2 DAY);
INSERT INTO tickets(order_item_id,ticket_number,qr_token_hash,status,created_at) VALUES
(1,'BTV-TICKET-DEMO-0001',SHA2('BATAVIAS-DEMO-TICKET-0001',256),'valid',NOW() - INTERVAL 2 DAY);
INSERT INTO notifications(user_id,title,message,created_at) VALUES
(4,'Booking berhasil','Pesanan demo kamu berhasil dibuat. Tiket tersedia di halaman My Tickets.',NOW() - INTERVAL 2 DAY);
INSERT INTO reviews(event_id,user_id,rating,comment,created_at) VALUES
(1,4,5,'Acara demo yang informatif dan menarik.',NOW() - INTERVAL 1 DAY);
INSERT INTO api_logs(method,path,status_code,duration_ms,created_at) VALUES
('GET','/api/events',200,42,NOW() - INTERVAL 1 HOUR),
('POST','/api/orders',201,86,NOW() - INTERVAL 2 HOUR),
('GET','/api/tickets',200,31,NOW() - INTERVAL 3 HOUR);
INSERT INTO system_logs(level,category,message,user_id,created_at) VALUES
('info','authentication','Demo admin session initialized.',1,NOW() - INTERVAL 1 HOUR),
('warning','payment','Demo payment simulation recorded.',4,NOW() - INTERVAL 2 HOUR),
('info','ticketing','Demo ticket generated successfully.',1,NOW() - INTERVAL 2 DAY);
