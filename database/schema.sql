-- schema.sql
-- Manufacturing ERP Database Architecture

CREATE DATABASE IF NOT EXISTS manufacturing_erp;
USE manufacturing_erp;

-- 1. Roles
CREATE TABLE IF NOT EXISTS roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    role_name VARCHAR(100) NOT NULL UNIQUE
);

-- Pre-populate 11 roles (Estimated based on standard ERP structure)
INSERT INTO roles (role_name) VALUES 
('Super Admin'),
('Purchase Order Manager'),
('Moulding Supervisor'),
('Brasspart Supervisor'),
('Packaging Supervisor'),
('Inventory Manager'),
('Production Floor Manager'),
('Quality Control Inspector'),
('Dispatch Officer'),
('Accounts Manager'),
('Sales Executive')
ON DUPLICATE KEY UPDATE role_name=VALUES(role_name);

-- 2. Users
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role_id INT NOT NULL,
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE
);

-- 3. Clients
CREATE TABLE IF NOT EXISTS clients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_name VARCHAR(255) NOT NULL,
    email_address VARCHAR(255),
    phone_number VARCHAR(50)
);

-- 4. Products
CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_code VARCHAR(100) NOT NULL UNIQUE,
    current_name VARCHAR(255) NOT NULL
);

-- 5. Purchase Orders
CREATE TABLE IF NOT EXISTS purchase_orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    order_date DATE NOT NULL,
    deadline_date DATE,
    total_boxes INT DEFAULT 0,
    total_pieces INT DEFAULT 0,
    is_urgent BOOLEAN DEFAULT FALSE,
    status VARCHAR(50) DEFAULT 'Pending',
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE
);

-- 6. PO Items
CREATE TABLE IF NOT EXISTS po_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    po_id INT NOT NULL,
    product_id INT NOT NULL,
    boxes INT DEFAULT 0,
    pieces INT DEFAULT 0,
    is_item_urgent BOOLEAN DEFAULT FALSE,
    lazer_print VARCHAR(255) DEFAULT NULL,
    lazer_print_amount INT DEFAULT 0,
    FOREIGN KEY (po_id) REFERENCES purchase_orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- 7. Raw Material Conversion
CREATE TABLE IF NOT EXISTS raw_material_conversion (
    id INT AUTO_INCREMENT PRIMARY KEY,
    parent_product_code VARCHAR(100) NOT NULL,
    process_type ENUM('moulding', 'brasspart', 'packaging') NOT NULL,
    component_name VARCHAR(255) NOT NULL,
    exact_multiplier_qty DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (parent_product_code) REFERENCES products(item_code) ON DELETE CASCADE
);

-- 8. Department Queues
CREATE TABLE IF NOT EXISTS department_queues (
    id INT AUTO_INCREMENT PRIMARY KEY,
    po_id INT NOT NULL,
    department_name VARCHAR(100) NOT NULL,
    item_code VARCHAR(100) NOT NULL,
    item_name VARCHAR(255) NOT NULL,
    quantity_required INT NOT NULL,
    status VARCHAR(50) DEFAULT 'Pending',
    transaction_timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (po_id) REFERENCES purchase_orders(id) ON DELETE CASCADE
);

-- 9. Full Transaction History
CREATE TABLE IF NOT EXISTS full_transaction_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    action_type VARCHAR(100) NOT NULL,
    department_name VARCHAR(100) NOT NULL,
    details TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Triggers for explicitly logging department actions
DELIMITER //

CREATE TRIGGER after_department_queue_insert
AFTER INSERT ON department_queues
FOR EACH ROW
BEGIN
    INSERT INTO full_transaction_history (action_type, department_name, details)
    VALUES ('INSERT', NEW.department_name, CONCAT('Added ', NEW.quantity_required, ' of ', NEW.item_code, ' for PO ', NEW.po_id));
END;
//

CREATE TRIGGER after_department_queue_update
AFTER UPDATE ON department_queues
FOR EACH ROW
BEGIN
    IF OLD.status != NEW.status THEN
        INSERT INTO full_transaction_history (action_type, department_name, details)
        VALUES ('STATUS_CHANGE', NEW.department_name, CONCAT('Status changed to ', NEW.status, ' for PO ', NEW.po_id, ' Item: ', NEW.item_code));
    END IF;
END;
//

DELIMITER ;
