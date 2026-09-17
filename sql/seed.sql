-- Create impact factors table if missing
CREATE TABLE IF NOT EXISTS impact_factors (
  id INT AUTO_INCREMENT PRIMARY KEY,
  skill_category VARCHAR(100) NOT NULL UNIQUE,
  co2_saved_per_participant_kg DECIMAL(10,2) NOT NULL
);

-- Ensure new time columns exist before inserts
ALTER TABLE sessions
  ADD COLUMN IF NOT EXISTS start_time TIME DEFAULT NULL AFTER event_date,
  ADD COLUMN IF NOT EXISTS end_time TIME DEFAULT NULL AFTER start_time;

-- USERS (demo credentials: all use password "password")
INSERT IGNORE INTO users (name, email, password_hash, role, city) VALUES
('Admin User', 'admin@skillshare.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'Limassol'),
('Nico Instructor', 'instructor@skillshare.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'instructor', 'Nicosia'),
('Test Learner', 'learner@skillshare.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'learner', 'Limassol');

-- IMPACT FACTORS (kg CO2 saved per participant)
INSERT IGNORE INTO impact_factors (skill_category, co2_saved_per_participant_kg) VALUES
('Gardening', 26),
('Mobility', 45),
('DIY', 18),
('Green Tech', 100),
('Community', 22),
('Outdoors', 30),
('Crafts', 15),
('Recycling', 20),
('Food', 24),
('Water', 28);

-- SESSIONS (core examples)
INSERT IGNORE INTO sessions (
  id, instructor_id, title, category, description, duration, fee, location,
  sustainability_impact, capacity, event_date, start_time, end_time, status, photo
) VALUES
(1, 2, 'Home Composting Basics', 'Gardening',
 'Learn how to compost household waste and reduce landfill impact.',
 '1 hour', 0, 'Limassol',
 'Teaches food waste-reduction technique', 10, '2025-12-01', '09:00:00', '10:00:00', 'active',
 'https://images.unsplash.com/photo-1501004318641-b39e6451bec6?auto=format&fit=crop&w=1200&q=80'),

(2, 2, 'Solar Panel Installation 101', 'Green Tech',
 'Introduction to small-scale solar setups at home.',
 '2 hours', 50, 'Online (Zoom)',
 'Teaches renewable energy basics', 8, '2025-12-10', '17:00:00', '19:00:00', 'active',
 'https://images.unsplash.com/photo-1509395176047-4a66953fd231?auto=format&fit=crop&w=1200&q=80'),

(3, 2, 'DIY Natural Cleaning Products', 'DIY',
 'Create eco-friendly cleaning solutions using natural ingredients.',
 '1.5 hours', 20, 'Nicosia',
 'Reduces chemical waste in households', 12, '2025-12-05', '14:00:00', '15:30:00', 'active',
 'https://images.unsplash.com/photo-1489515217757-5fd1be406fef?auto=format&fit=crop&w=1200&q=80');

-- Extra sample sessions (10)
INSERT IGNORE INTO sessions (
  instructor_id, title, category, description, duration, fee, location,
  sustainability_impact, capacity, event_date, start_time, end_time, status, photo
) VALUES
(2, 'Urban Balcony Gardens', 'Gardening', 'Turn tiny balconies into herb and pollinator havens.', '1.5 hours', 0, 'Online (Zoom)', 'Boosts urban biodiversity and reduces heat island effects.', 15, '2025-12-12', '09:00:00', '10:30:00', 'active', 'https://images.unsplash.com/photo-1501004318641-b39e6451bec6?auto=format&fit=crop&w=1200&q=80'),
(2, 'Beginner Bike Maintenance', 'Mobility', 'Keep your bicycle tuned for safer, greener commutes.', '2 hours', 10, 'Nicosia', 'Encourages low-carbon transport by extending bike life.', 18, '2025-12-08', '10:00:00', '12:00:00', 'active', 'https://images.unsplash.com/photo-1508606572321-901ea443707f?auto=format&fit=crop&w=1200&q=80'),
(2, 'Zero-Waste Meal Prep', 'Food', 'Batch cook, store smartly, and slash kitchen waste.', '1 hour', 0, 'Limassol', 'Cuts food waste and packaging through smart planning.', 20, '2025-12-06', '18:00:00', '19:00:00', 'active', 'https://images.unsplash.com/photo-1467003909585-2f8a72700288?auto=format&fit=crop&w=1200&q=80'),
(2, 'Rainwater Harvesting Basics', 'Water', 'Design a simple rain barrel system for your home.', '1.5 hours', 25, 'Online (Zoom)', 'Reduces municipal water demand and runoff.', 12, '2025-12-15', '16:00:00', '17:30:00', 'active', 'https://images.unsplash.com/photo-1505735381214-16d6c5e0be12?auto=format&fit=crop&w=1200&q=80'),
(2, 'Upcycled Furniture Makeover', 'DIY', 'Give old furniture new life with low-VOC finishes.', '2 hours', 30, 'Limassol', 'Keeps bulky items out of landfills and reduces new material use.', 10, '2025-12-20', '13:00:00', '15:00:00', 'active', 'https://images.unsplash.com/photo-1523419400520-2231777d5e4c?auto=format&fit=crop&w=1200&q=80'),
(2, 'Intro to Home Energy Audits', 'Green Tech', 'Spot energy leaks, measure usage, and prioritize fixes.', '1 hour', 15, 'Nicosia', 'Lowers household energy demand and emissions.', 14, '2025-12-09', '11:00:00', '12:30:00', 'active', 'https://images.unsplash.com/photo-1509395062183-67c5ad6faff9?auto=format&fit=crop&w=1200&q=80'),
(2, 'Community Clean-Up Toolkit', 'Community', 'Plan, recruit, and run a neighborhood clean-up.', '1 hour', 0, 'Limassol', 'Reduces local litter and builds community stewardship.', 25, '2025-12-07', '09:30:00', '10:30:00', 'active', 'https://images.unsplash.com/photo-1523906834658-6e24ef2386f9?auto=format&fit=crop&w=1200&q=80'),
(2, 'Low-Impact Camping Skills', 'Outdoors', 'Pack light, cook clean, and leave no trace on trails.', '2 hours', 20, 'Online (Zoom)', 'Protects ecosystems by teaching responsible recreation.', 16, '2025-12-14', '17:00:00', '19:00:00', 'active', 'https://images.unsplash.com/photo-1470246973918-29a93221c455?auto=format&fit=crop&w=1200&q=80'),
(2, 'Natural Dye Workshop', 'Crafts', 'Create plant-based dyes for fabric and paper.', '1.5 hours', 18, 'Nicosia', 'Avoids synthetic dyes and supports regenerative materials.', 12, '2025-12-11', '15:00:00', '16:30:00', 'active', 'https://images.unsplash.com/photo-1523419400520-2231777d5e4c?auto=format&fit=crop&w=1200&q=80'),
(2, 'Smart Recycling at Home', 'Recycling', 'Demystify plastics, metals, and e-waste drop-offs.', '1 hour', 0, 'Limassol', 'Increases diversion rates and reduces contamination.', 30, '2025-12-13', '10:00:00', '11:00:00', 'active', 'https://images.unsplash.com/photo-1501004318641-b39e6451bec6?auto=format&fit=crop&w=1200&q=80');

-- BOOKINGS
INSERT IGNORE INTO bookings (id, session_id, learner_id, status) VALUES
(1, 1, 3, 'confirmed'),
(2, 2, 3, 'pending');

-- RATINGS (only for past sessions once dates elapse)
INSERT IGNORE INTO ratings (id, session_id, learner_id, rating, feedback) VALUES
(1, 1, 3, 5, 'Really useful and practical session!'),
(2, 1, 3, 4, 'Loved the composting tips.');
