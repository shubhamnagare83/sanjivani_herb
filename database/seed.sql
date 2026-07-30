-- ============================================
-- Seed Data for Campus Plant Diversity Mapper
-- Realistic demo data for Sanjivani University
-- ============================================

USE plant_mapper;

-- 1. Institution
INSERT INTO institutions (id, name, slug, campus_center_lat, campus_center_lng, default_zoom) VALUES
('inst-0001-0001-0001-000000000001', 'Sanjivani University', 'sanjivani-university', 19.8762, 74.5981, 17);

-- 2. Admin + sample users (password = 'password123' hashed with PHP password_hash)
INSERT INTO users (id, institution_id, email, password_hash, full_name, role) VALUES
('user-0001-0001-0001-000000000001', 'inst-0001-0001-0001-000000000001', 'admin@sanjivani.edu', '$2y$10$fz3ca6XJj/wVwIuS4IaJB.NTaFW3ex6cTsYfxbwM5fTGFrJb5CiEm', 'Dr. Priya Sharma', 'admin'),
('user-0001-0001-0001-000000000002', 'inst-0001-0001-0001-000000000001', 'verifier@sanjivani.edu', '$2y$10$fz3ca6XJj/wVwIuS4IaJB.NTaFW3ex6cTsYfxbwM5fTGFrJb5CiEm', 'Prof. Rajesh Patil', 'verifier'),
('user-0001-0001-0001-000000000003', 'inst-0001-0001-0001-000000000001', 'student@sanjivani.edu', '$2y$10$fz3ca6XJj/wVwIuS4IaJB.NTaFW3ex6cTsYfxbwM5fTGFrJb5CiEm', 'Shubham Nagare', 'contributor');

-- 3. Campus Zones
INSERT INTO zones (id, institution_id, name, description, center_lat, center_lng, color_hex) VALUES
('zone-0001-0001-0001-000000000001', 'inst-0001-0001-0001-000000000001', 'Botanical Garden', 'Main botanical garden near the science block', 19.8770, 74.5975, '#22c55e'),
('zone-0001-0001-0001-000000000002', 'inst-0001-0001-0001-000000000001', 'Main Entrance Avenue', 'Tree-lined avenue from main gate to admin building', 19.8755, 74.5990, '#3b82f6'),
('zone-0001-0001-0001-000000000003', 'inst-0001-0001-0001-000000000001', 'Sports Ground Perimeter', 'Green belt around the sports ground', 19.8745, 74.5970, '#f59e0b'),
('zone-0001-0001-0001-000000000004', 'inst-0001-0001-0001-000000000001', 'Library Courtyard', 'Courtyard garden between library and canteen', 19.8760, 74.5985, '#8b5cf6'),
('zone-0001-0001-0001-000000000005', 'inst-0001-0001-0001-000000000001', 'Hostel Garden', 'Garden area near the hostel blocks', 19.8780, 74.5965, '#ef4444');

-- 4. Species
INSERT INTO species (id, scientific_name, common_name, family, native_status, description, medicinal_uses) VALUES
('spec-0001-0001-0001-000000000001', 'Ficus benghalensis', 'Banyan Tree', 'Moraceae', 'native', 'The national tree of India, known for its vast canopy and aerial roots.', 'Bark decoction used for diabetes management, latex applied to skin disorders.'),
('spec-0001-0001-0001-000000000002', 'Ficus religiosa', 'Peepal Tree', 'Moraceae', 'native', 'Sacred fig tree revered across South Asia. Produces oxygen 24/7.', 'Leaves used for asthma, bark for skin diseases and inflammation.'),
('spec-0001-0001-0001-000000000003', 'Azadirachta indica', 'Neem', 'Meliaceae', 'native', 'Known as the village pharmacy. Every part of the tree has medicinal value.', 'Antifungal, antibacterial. Leaves for skin disorders, twigs as toothbrush.'),
('spec-0001-0001-0001-000000000004', 'Mangifera indica', 'Mango', 'Anacardiaceae', 'native', 'National fruit of India. Tropical evergreen tree prized for its fruit.', 'Bark and leaves used in traditional remedies for diarrhea and ulcers.'),
('spec-0001-0001-0001-000000000005', 'Aloe barbadensis', 'Aloe Vera', 'Asphodelaceae', 'introduced', 'Succulent plant widely cultivated for cosmetic and medicinal use.', 'Gel for burns, wound healing, skin hydration, digestive health.'),
('spec-0001-0001-0001-000000000006', 'Ocimum tenuiflorum', 'Tulsi (Holy Basil)', 'Lamiaceae', 'native', 'Sacred herb in Hindu tradition. Strong aromatic with many medicinal properties.', 'Immunity booster, cold/cough remedy, stress relief, anti-inflammatory.'),
('spec-0001-0001-0001-000000000007', 'Moringa oleifera', 'Drumstick Tree', 'Moringaceae', 'native', 'Fast-growing tree known as the miracle tree for its nutritional density.', 'Leaves rich in vitamins A, C, calcium. Used for malnutrition and water purification.'),
('spec-0001-0001-0001-000000000008', 'Lantana camara', 'Lantana', 'Verbenaceae', 'invasive', 'Aggressive invasive species forming dense thickets. Toxic to livestock.', 'Despite toxicity, leaf extracts studied for antimicrobial properties.'),
('spec-0001-0001-0001-000000000009', 'Saraca asoca', 'Ashoka Tree', 'Fabaceae', 'native', 'Sacred tree associated with love and fertility. Beautiful orange-yellow flowers.', 'Bark used in Ayurveda for uterine health, menstrual disorders, skin conditions.'),
('spec-0001-0001-0001-000000000010', 'Terminalia arjuna', 'Arjuna', 'Combretaceae', 'native', 'Large deciduous tree with smooth bark, used extensively in Ayurvedic medicine.', 'Bark powder for cardiovascular health, blood pressure regulation.'),
('spec-0001-0001-0001-000000000011', 'Catharanthus roseus', 'Periwinkle', 'Apocynaceae', 'introduced', 'Ornamental plant that is also a source of important cancer-fighting alkaloids.', 'Vincristine and vinblastine extracted for leukemia and lymphoma treatment.'),
('spec-0001-0001-0001-000000000012', 'Curcuma longa', 'Turmeric', 'Zingiberaceae', 'native', 'Golden spice known as the Indian saffron. Powerful anti-inflammatory.', 'Curcumin for inflammation, antioxidant, wound healing, liver protection.'),
('spec-0001-0001-0001-000000000013', 'Santalum album', 'Sandalwood', 'Santalaceae', 'native', 'Precious aromatic tree known for its heartwood and essential oil.', 'Oil for skin care, meditation, urinary tract infections, anxiety relief.'),
('spec-0001-0001-0001-000000000014', 'Parthenium hysterophorus', 'Congress Grass', 'Asteraceae', 'invasive', 'Highly invasive weed causing severe allergies and displacing native flora.', 'No medicinal use. Major allergen and agricultural pest.'),
('spec-0001-0001-0001-000000000015', 'Withania somnifera', 'Ashwagandha', 'Solanaceae', 'native', 'Ancient adaptogen herb used in Ayurveda for over 3000 years.', 'Stress relief, energy boost, immunity, anti-anxiety, muscle strength.');

-- 5. Plant Records (spread across campus zones with varying statuses)
INSERT INTO plant_records (id, institution_id, species_id, zone_id, latitude, longitude, status, ai_confidence, notes, submitted_by, verified_by, verified_at) VALUES
('plnt-0001-0001-0001-000000000001', 'inst-0001-0001-0001-000000000001', 'spec-0001-0001-0001-000000000001', 'zone-0001-0001-0001-000000000001', 19.87720, 74.59730, 'verified', 95.50, 'Ancient banyan tree near the garden entrance, estimated 100+ years old', 'user-0001-0001-0001-000000000003', 'user-0001-0001-0001-000000000002', NOW()),
('plnt-0001-0001-0001-000000000002', 'inst-0001-0001-0001-000000000001', 'spec-0001-0001-0001-000000000002', 'zone-0001-0001-0001-000000000002', 19.87560, 74.59920, 'verified', 91.20, 'Large peepal tree providing shade along the entrance road', 'user-0001-0001-0001-000000000003', 'user-0001-0001-0001-000000000002', NOW()),
('plnt-0001-0001-0001-000000000003', 'inst-0001-0001-0001-000000000001', 'spec-0001-0001-0001-000000000003', 'zone-0001-0001-0001-000000000001', 19.87690, 74.59760, 'verified', 97.80, 'Healthy neem tree, used by students for shade during breaks', 'user-0001-0001-0001-000000000001', NULL, NULL),
('plnt-0001-0001-0001-000000000004', 'inst-0001-0001-0001-000000000001', 'spec-0001-0001-0001-000000000004', 'zone-0001-0001-0001-000000000003', 19.87430, 74.59710, 'verified', 89.30, 'Mango tree near sports ground, fruits in summer', 'user-0001-0001-0001-000000000003', 'user-0001-0001-0001-000000000002', NOW()),
('plnt-0001-0001-0001-000000000005', 'inst-0001-0001-0001-000000000001', 'spec-0001-0001-0001-000000000005', 'zone-0001-0001-0001-000000000004', 19.87610, 74.59870, 'pending_verification', 72.10, 'Aloe vera cluster in the library courtyard planter', 'user-0001-0001-0001-000000000003', NULL, NULL),
('plnt-0001-0001-0001-000000000006', 'inst-0001-0001-0001-000000000001', 'spec-0001-0001-0001-000000000006', 'zone-0001-0001-0001-000000000001', 19.87750, 74.59740, 'verified', 93.60, 'Tulsi plants maintained by the botany department', 'user-0001-0001-0001-000000000001', 'user-0001-0001-0001-000000000001', NOW()),
('plnt-0001-0001-0001-000000000007', 'inst-0001-0001-0001-000000000001', 'spec-0001-0001-0001-000000000007', 'zone-0001-0001-0001-000000000005', 19.87810, 74.59660, 'pending_verification', 85.40, 'Moringa tree near hostel mess, leaves harvested for food', 'user-0001-0001-0001-000000000003', NULL, NULL),
('plnt-0001-0001-0001-000000000008', 'inst-0001-0001-0001-000000000001', 'spec-0001-0001-0001-000000000008', 'zone-0001-0001-0001-000000000003', 19.87460, 74.59680, 'verified', 88.90, 'Invasive lantana cluster — needs removal by grounds team', 'user-0001-0001-0001-000000000002', 'user-0001-0001-0001-000000000002', NOW()),
('plnt-0001-0001-0001-000000000009', 'inst-0001-0001-0001-000000000001', 'spec-0001-0001-0001-000000000009', 'zone-0001-0001-0001-000000000004', 19.87590, 74.59850, 'verified', 78.50, 'Beautiful ashoka tree in full bloom near library steps', 'user-0001-0001-0001-000000000003', 'user-0001-0001-0001-000000000001', NOW()),
('plnt-0001-0001-0001-000000000010', 'inst-0001-0001-0001-000000000001', 'spec-0001-0001-0001-000000000010', 'zone-0001-0001-0001-000000000002', 19.87540, 74.59900, 'pending_verification', 67.20, 'Arjuna tree along the entrance walkway', 'user-0001-0001-0001-000000000003', NULL, NULL),
('plnt-0001-0001-0001-000000000011', 'inst-0001-0001-0001-000000000001', 'spec-0001-0001-0001-000000000011', 'zone-0001-0001-0001-000000000001', 19.87710, 74.59780, 'verified', 82.30, 'Pink periwinkle flowers in the garden border', 'user-0001-0001-0001-000000000001', 'user-0001-0001-0001-000000000001', NOW()),
('plnt-0001-0001-0001-000000000012', 'inst-0001-0001-0001-000000000001', 'spec-0001-0001-0001-000000000012', 'zone-0001-0001-0001-000000000001', 19.87730, 74.59770, 'verified', 94.10, 'Turmeric patch in the medicinal plants section', 'user-0001-0001-0001-000000000002', 'user-0001-0001-0001-000000000001', NOW()),
('plnt-0001-0001-0001-000000000013', 'inst-0001-0001-0001-000000000001', 'spec-0001-0001-0001-000000000013', 'zone-0001-0001-0001-000000000004', 19.87580, 74.59890, 'pending_verification', 55.80, 'Young sandalwood sapling planted last year', 'user-0001-0001-0001-000000000003', NULL, NULL),
('plnt-0001-0001-0001-000000000014', 'inst-0001-0001-0001-000000000001', 'spec-0001-0001-0001-000000000014', 'zone-0001-0001-0001-000000000003', 19.87480, 74.59700, 'rejected', 91.70, 'Congress grass weed — flagged for removal', 'user-0001-0001-0001-000000000003', 'user-0001-0001-0001-000000000002', NOW()),
('plnt-0001-0001-0001-000000000015', 'inst-0001-0001-0001-000000000001', 'spec-0001-0001-0001-000000000015', 'zone-0001-0001-0001-000000000001', 19.87740, 74.59750, 'verified', 86.90, 'Ashwagandha plant in the Ayurvedic herb section', 'user-0001-0001-0001-000000000001', 'user-0001-0001-0001-000000000001', NOW()),
('plnt-0001-0001-0001-000000000016', 'inst-0001-0001-0001-000000000001', 'spec-0001-0001-0001-000000000003', 'zone-0001-0001-0001-000000000002', 19.87530, 74.59930, 'verified', 96.40, 'Row of neem trees lining the entrance road', 'user-0001-0001-0001-000000000003', 'user-0001-0001-0001-000000000002', NOW()),
('plnt-0001-0001-0001-000000000017', 'inst-0001-0001-0001-000000000001', 'spec-0001-0001-0001-000000000004', 'zone-0001-0001-0001-000000000005', 19.87790, 74.59650, 'pending_verification', 79.50, 'Mango tree near hostel Block B', 'user-0001-0001-0001-000000000003', NULL, NULL),
('plnt-0001-0001-0001-000000000018', 'inst-0001-0001-0001-000000000001', 'spec-0001-0001-0001-000000000006', 'zone-0001-0001-0001-000000000005', 19.87800, 74.59640, 'verified', 90.20, 'Tulsi pot garden maintained by hostel students', 'user-0001-0001-0001-000000000003', 'user-0001-0001-0001-000000000001', NOW()),
('plnt-0001-0001-0001-000000000019', 'inst-0001-0001-0001-000000000001', 'spec-0001-0001-0001-000000000001', 'zone-0001-0001-0001-000000000004', 19.87600, 74.59860, 'verified', 93.80, 'Young banyan tree planted during tree plantation drive', 'user-0001-0001-0001-000000000001', 'user-0001-0001-0001-000000000002', NOW()),
('plnt-0001-0001-0001-000000000020', 'inst-0001-0001-0001-000000000001', 'spec-0001-0001-0001-000000000009', 'zone-0001-0001-0001-000000000002', 19.87550, 74.59910, 'pending_verification', 62.30, 'Possible ashoka tree near main gate — needs verification', 'user-0001-0001-0001-000000000003', NULL, NULL);

-- 6. QR Codes for verified plants
INSERT INTO qr_codes (id, plant_record_id, public_slug, scan_count) VALUES
('qr00-0001-0001-0001-000000000001', 'plnt-0001-0001-0001-000000000001', 'BAN001', 42),
('qr00-0001-0001-0001-000000000002', 'plnt-0001-0001-0001-000000000002', 'PEP002', 28),
('qr00-0001-0001-0001-000000000003', 'plnt-0001-0001-0001-000000000003', 'NEM003', 15),
('qr00-0001-0001-0001-000000000004', 'plnt-0001-0001-0001-000000000004', 'MNG004', 33),
('qr00-0001-0001-0001-000000000006', 'plnt-0001-0001-0001-000000000006', 'TUL006', 57),
('qr00-0001-0001-0001-000000000009', 'plnt-0001-0001-0001-000000000009', 'ASH009', 19),
('qr00-0001-0001-0001-000000000012', 'plnt-0001-0001-0001-000000000012', 'TUR012', 8),
('qr00-0001-0001-0001-000000000015', 'plnt-0001-0001-0001-000000000015', 'AWG015', 24);

-- 7. Some verification audit entries
INSERT INTO verifications (id, plant_record_id, action, reason, performed_by) VALUES
('verf-0001-0001-0001-000000000001', 'plnt-0001-0001-0001-000000000001', 'approved', 'Confirmed as Ficus benghalensis — distinctive aerial roots visible', 'user-0001-0001-0001-000000000002'),
('verf-0001-0001-0001-000000000002', 'plnt-0001-0001-0001-000000000002', 'approved', 'Peepal tree confirmed by leaf shape', 'user-0001-0001-0001-000000000002'),
('verf-0001-0001-0001-000000000003', 'plnt-0001-0001-0001-000000000014', 'rejected', 'Invasive weed — flagged for grounds team removal', 'user-0001-0001-0001-000000000002');

-- 8. Activity log entries
INSERT INTO activity_log (institution_id, user_id, action_type, entity_type, entity_id, description) VALUES
('inst-0001-0001-0001-000000000001', 'user-0001-0001-0001-000000000003', 'create', 'plant_record', 'plnt-0001-0001-0001-000000000001', 'Submitted a new Banyan Tree observation in Botanical Garden'),
('inst-0001-0001-0001-000000000001', 'user-0001-0001-0001-000000000002', 'verify', 'plant_record', 'plnt-0001-0001-0001-000000000001', 'Verified Banyan Tree identification'),
('inst-0001-0001-0001-000000000001', 'user-0001-0001-0001-000000000003', 'create', 'plant_record', 'plnt-0001-0001-0001-000000000005', 'Submitted Aloe Vera observation in Library Courtyard'),
('inst-0001-0001-0001-000000000001', 'user-0001-0001-0001-000000000001', 'create', 'zone', 'zone-0001-0001-0001-000000000001', 'Created Botanical Garden zone');
