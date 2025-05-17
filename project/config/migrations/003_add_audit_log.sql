-- Create audit log table
CREATE TABLE IF NOT EXISTS audit_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ticket_id INT NOT NULL,
    user_id INT NOT NULL,
    action VARCHAR(50) NOT NULL,
    details TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ticket_id) REFERENCES tickets(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_ticket_id (ticket_id),
    INDEX idx_user_id (user_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add indexes for better performance on commonly queried fields
ALTER TABLE tickets ADD INDEX idx_status_priority (status, priority);
ALTER TABLE tickets ADD INDEX idx_agency_id (agency_id);
ALTER TABLE tickets ADD INDEX idx_updated_at (updated_at);

-- Add fulltext search capability
ALTER TABLE tickets ADD FULLTEXT INDEX ft_title_description (title, description); 