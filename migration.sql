-- Step 1: Add REGISTERED status to items table
ALTER TABLE items MODIFY COLUMN status ENUM('registered','lost','pending','claimed') DEFAULT 'registered';

-- Step 2: Create found_reports table
CREATE TABLE IF NOT EXISTS found_reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    finder_id INT NOT NULL,
    image_path VARCHAR(255) DEFAULT NULL,
    description TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (finder_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Step 3: Existing items with status 'found' → treat as 'lost' for migration
UPDATE items SET status = 'lost' WHERE status = 'found';