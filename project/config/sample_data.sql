-- Insert admin user (password: admin123)
INSERT INTO users (name, email, password, role) VALUES
('Admin User', 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Insert sample agencies
INSERT INTO agencies (name, description, contact_email, contact_phone) VALUES
('Department of Health', 'Public health services and medical facilities', 'health@gov.local', '123-456-7890'),
('Department of Education', 'Educational services and school management', 'education@gov.local', '123-456-7891'),
('Department of Transportation', 'Public transportation and road infrastructure', 'transport@gov.local', '123-456-7892'),
('Department of Housing', 'Housing and urban development services', 'housing@gov.local', '123-456-7893');

-- Insert sample categories for Department of Health
INSERT INTO categories (name, description, agency_id) VALUES
('Medical Services', 'Issues related to medical services and healthcare', 1),
('Public Health', 'Public health concerns and initiatives', 1),
('Healthcare Facilities', 'Issues with hospitals and clinics', 1);

-- Insert sample categories for Department of Education
INSERT INTO categories (name, description, agency_id) VALUES
('School Administration', 'Issues related to school management', 2),
('Curriculum', 'Concerns about educational programs', 2),
('Student Services', 'Student-related issues and support', 2);

-- Insert sample categories for Department of Transportation
INSERT INTO categories (name, description, agency_id) VALUES
('Public Transit', 'Issues with buses and trains', 3),
('Road Maintenance', 'Road repair and maintenance issues', 3),
('Traffic Management', 'Traffic signals and flow concerns', 3);

-- Insert sample categories for Department of Housing
INSERT INTO categories (name, description, agency_id) VALUES
('Housing Assistance', 'Housing aid and support programs', 4),
('Property Maintenance', 'Building maintenance and repairs', 4),
('Community Development', 'Neighborhood improvement projects', 4);

-- Insert sample citizens
INSERT INTO users (name, email, password, role) VALUES
('John Doe', 'john@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'citizen'),
('Jane Smith', 'jane@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'citizen');

-- Insert sample tickets
INSERT INTO tickets (title, description, user_id, category_id, agency_id, status, priority) VALUES
('Pothole on Main Street', 'Large pothole causing traffic issues near 123 Main St', 2, 1, 1, 'pending', 'high'),
('Broken Street Light', 'Street light not working at Park Avenue', 2, 2, 1, 'in_progress', 'medium'),
('Garbage Collection Issue', 'Missed garbage collection at Oak Lane', 3, 3, 2, 'pending', 'medium');

-- Insert sample responses
INSERT INTO responses (ticket_id, user_id, content) VALUES
(1, 1, 'Thank you for reporting. We will inspect the location within 24 hours.'),
(2, 1, 'Maintenance team has been dispatched to fix the street light.'); 