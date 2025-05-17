-- Add new columns to tickets table for admin functionality
ALTER TABLE tickets
ADD COLUMN IF NOT EXISTS updated_by INT NULL,
ADD COLUMN IF NOT EXISTS admin_comment TEXT NULL,
ADD COLUMN IF NOT EXISTS admin_action_at TIMESTAMP NULL,
ADD FOREIGN KEY (updated_by) REFERENCES users(id);

-- Update status enum to include new statuses
ALTER TABLE tickets
MODIFY COLUMN status ENUM('pending', 'in_progress', 'under_review', 'resolved', 'closed', 'approved', 'rejected') NOT NULL DEFAULT 'pending'; 