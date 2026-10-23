-- Create database
CREATE DATABASE IF NOT EXISTS pos_system_fixed;
USE pos_system_fixed;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin','cashier') NOT NULL,
    status ENUM('active','inactive') DEFAULT 'active'
);

-- Products table
CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    quantity INT NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1  -- 0 = archived (hidden from the POS, history kept)
);

-- Cashier sessions
CREATE TABLE IF NOT EXISTS sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cashier_id INT NOT NULL,
    start_time DATETIME NOT NULL,
    end_time DATETIME DEFAULT NULL,
    total_amount DECIMAL(10,2) DEFAULT 0.00,
    total_items INT DEFAULT 0,
    FOREIGN KEY (cashier_id) REFERENCES users(id)
);

-- Completed sales
CREATE TABLE IF NOT EXISTS sales (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cashier_id INT NOT NULL,
    session_id INT NOT NULL,
    sale_date DATETIME NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (cashier_id) REFERENCES users(id),
    FOREIGN KEY (session_id) REFERENCES sessions(id)
);

-- Items inside each sale
CREATE TABLE IF NOT EXISTS sale_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sale_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (sale_id) REFERENCES sales(id),
    FOREIGN KEY (product_id) REFERENCES products(id)
);

-- Default admin (password: admin123)
INSERT INTO users (username, password, role) VALUES
('admin', '$2y$10$Qpyp.m9JFBBOya2VwSfa7uPtbbFxPAChx5sAY9P6aSjpN9miyGsnC', 'admin');

-- 100 diverse supermarket products
INSERT INTO products (name, price, quantity) VALUES
-- Stationery (10 items)
('Ballpoint Pen (Blue)', 1.49, 200),
('HB Pencil (Pack of 5)', 2.99, 150),
('A4 Notebook (100 pages)', 3.99, 80),
('Eraser (2-pack)', 1.29, 100),
('Sharpener', 0.99, 120),
('Glue Stick (40g)', 2.49, 60),
('Scotch Tape', 2.99, 70),
('Highlighter (Yellow)', 1.79, 90),
('Sticky Notes (Pack)', 2.29, 85),
('Ruler (30cm)', 1.19, 110),

-- Detergents & Cleaning (10 items)
('Laundry Detergent (1.5L)', 12.99, 40),
('Dish Soap (500ml)', 4.99, 55),
('All-Purpose Cleaner (750ml)', 5.99, 45),
('Glass Cleaner (750ml)', 4.49, 50),
('Bleach (3.78L)', 5.99, 30),
('Fabric Softener (1.5L)', 8.99, 35),
('Bathroom Cleaner (750ml)', 5.49, 40),
('Floor Cleaner (1L)', 6.99, 38),
('Hand Soap (500ml)', 3.99, 60),
('Sponges (Pack of 4)', 2.49, 70),

-- Fruits (per KG - 12 items)
('Apples (per KG)', 3.99, 80),
('Bananas (per KG)', 2.49, 100),
('Oranges (per KG)', 3.49, 70),
('Grapes (per KG)', 8.99, 40),
('Strawberries (per KG)', 12.99, 25),
('Avocados (per KG)', 9.99, 30),
('Lemons (per KG)', 4.49, 60),
('Watermelon (per KG)', 1.99, 20),
('Pineapple (each)', 4.99, 35),
('Mangoes (per KG)', 5.99, 45),
('Blueberries (125g punnet)', 4.49, 50),
('Raspberries (125g punnet)', 5.49, 40),

-- Vegetables (per KG or each - 10 items)
('Tomatoes (per KG)', 4.99, 70),
('Potatoes (per KG)', 2.99, 120),
('Onions (per KG)', 2.49, 90),
('Carrots (per KG)', 2.99, 80),
('Broccoli (each)', 2.99, 55),
('Lettuce (each)', 2.49, 45),
('Cucumber (each)', 1.99, 60),
('Bell Peppers (per KG)', 7.49, 40),
('Spinach (bunch)', 3.49, 35),
('Mushrooms (per KG)', 11.99, 30),

-- Pastry & Bakery (10 items)
('White Bread (500g loaf)', 3.49, 50),
('Whole Wheat Bread (500g loaf)', 4.49, 45),
('Croissant (each)', 1.99, 60),
('Chocolate Muffin', 2.49, 40),
('Blueberry Danish', 2.99, 35),
('Bagels (6-pack)', 5.49, 30),
('Dinner Rolls (12-pack)', 4.49, 40),
('Sourdough Bread (600g)', 5.99, 25),
('Apple Pie (small)', 6.99, 20),
('Cinnamon Roll', 3.49, 30),

-- Snacks & Confectionery (12 items)
('Potato Chips (200g)', 4.99, 80),
('Tortilla Chips (350g)', 4.49, 65),
('Pretzels (400g)', 3.99, 55),
('Microwave Popcorn (6pk)', 5.99, 40),
('Chocolate Bar (100g)', 2.99, 90),
('Cookies (350g)', 4.99, 60),
('Granola Bars (8pk)', 5.49, 50),
('Trail Mix (350g)', 6.99, 35),
('Mixed Candy (350g)', 3.99, 70),
('Gummy Bears (200g)', 2.99, 55),
('Rice Cakes (150g)', 2.49, 45),
('Beef Jerky (100g)', 7.99, 30),

-- Dairy & Eggs (8 items)
('Whole Milk (2L)', 4.99, 60),
('Butter (454g)', 5.99, 50),
('Yogurt (750g tub)', 4.49, 45),
('Eggs (12pk)', 5.99, 70),
('Cheddar Cheese (per KG)', 13.99, 30),
('Mozzarella Cheese (per KG)', 12.99, 28),
('Sour Cream (500ml)', 3.49, 40),
('Cream Cheese (250g)', 4.99, 45),

-- Beverages (10 items)
('Coca-Cola (12pk cans)', 7.99, 70),
('Orange Juice (1.89L)', 4.99, 55),
('Bottled Water (24pk)', 5.99, 100),
('Ground Coffee (340g)', 9.99, 35),
('Green Tea (20 bags)', 4.99, 50),
('Apple Juice (1.89L)', 4.49, 45),
('Sports Drink (710ml)', 2.49, 60),
('Lemonade (1.89L)', 2.99, 50),
('Sparkling Water (1L)', 1.79, 80),
('Energy Drink (473ml)', 3.49, 65),

-- Frozen Foods (8 items)
('Frozen Pizza (500g)', 5.99, 45),
('Vanilla Ice Cream (1.5L)', 4.99, 40),
('Mixed Vegetables (1kg)', 2.99, 55),
('Chicken Nuggets (1kg)', 6.99, 35),
('Frozen French Fries (1kg)', 3.49, 50),
('Frozen Berries (500g)', 4.99, 30),
('Frozen Fish Sticks (500g)', 5.49, 25),
('Ice Cream Sandwiches (6pk)', 5.49, 38),

-- Meat & Seafood (per KG - 6 items)
('Chicken Breast (per KG)', 12.99, 35),
('Ground Beef (per KG)', 14.99, 40),
('Salmon Fillet (per KG)', 29.99, 15),
('Bacon (per KG)', 16.99, 30),
('Sausages (per KG)', 10.99, 45),
('Shrimp (per KG)', 22.99, 20),

-- Household & Miscellaneous (4 items to total 100)
('Aluminum Foil (30m)', 4.99, 55),
('Plastic Wrap (100m)', 3.99, 50),
('Ziploc Bags (50pk)', 5.49, 45),
('Paper Towels (6pk)', 9.99, 60);