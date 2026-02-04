-- Coffee Management System Database Initialization
-- SQLite Database Schema

-- Create users table
CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    phone TEXT NOT NULL UNIQUE,
    location TEXT NOT NULL,
    password TEXT NOT NULL,
    role TEXT NOT NULL CHECK (role IN ('admin', 'seller')),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Create coffee_types table
CREATE TABLE IF NOT EXISTS coffee_types (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL UNIQUE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Create stocks table
CREATE TABLE IF NOT EXISTS stocks (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    seller_id INTEGER NOT NULL,
    coffee_type_id INTEGER NOT NULL,
    kilos DECIMAL(10,2) NOT NULL DEFAULT 0,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (seller_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (coffee_type_id) REFERENCES coffee_types(id) ON DELETE CASCADE,
    UNIQUE(seller_id, coffee_type_id)
);

-- Insert default coffee types
INSERT OR IGNORE INTO coffee_types (name) VALUES 
('Arabica'),
('Robusta'),
('Liberica'),
('Excelsa'),
('Typica'),
('Bourbon'),
('Geisha'),
('Catimor'),
('Maragogipe'),
('Pacamara');

-- Create default admin user (password: admin123)
INSERT OR IGNORE INTO users (name, phone, location, password, role) 
VALUES ('Admin', '0000000000', 'Office', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Create indexes for better performance
CREATE INDEX IF NOT EXISTS idx_stocks_seller_id ON stocks(seller_id);
CREATE INDEX IF NOT EXISTS idx_stocks_coffee_type_id ON stocks(coffee_type_id);
CREATE INDEX IF NOT EXISTS idx_users_role ON users(role);
CREATE INDEX IF NOT EXISTS idx_users_phone ON users(phone);
