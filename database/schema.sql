-- ============================================
-- Campus Plant Diversity Mapper - MySQL Schema
-- Sanjivani University Hackathon
-- ============================================

CREATE DATABASE IF NOT EXISTS plant_mapper
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE plant_mapper;

-- 1. Institutions (multi-tenant root)
CREATE TABLE institutions (
    id CHAR(36) PRIMARY KEY DEFAULT (UUID()),
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(100) UNIQUE NOT NULL,
    campus_center_lat DOUBLE DEFAULT NULL,
    campus_center_lng DOUBLE DEFAULT NULL,
    default_zoom INT DEFAULT 17,
    logo_url VARCHAR(500) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 2. Users
CREATE TABLE users (
    id CHAR(36) PRIMARY KEY DEFAULT (UUID()),
    institution_id CHAR(36) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(255) NOT NULL,
    role ENUM('contributor','verifier','admin') DEFAULT 'contributor',
    avatar_url VARCHAR(500) DEFAULT NULL,
    is_active TINYINT(1) DEFAULT 1,
    last_login TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (institution_id) REFERENCES institutions(id) ON DELETE CASCADE,
    INDEX idx_users_institution (institution_id),
    INDEX idx_users_email (email)
) ENGINE=InnoDB;

-- 3. Zones (campus sub-areas for analytics)
CREATE TABLE zones (
    id CHAR(36) PRIMARY KEY DEFAULT (UUID()),
    institution_id CHAR(36) NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT DEFAULT NULL,
    boundary_json JSON DEFAULT NULL,
    center_lat DOUBLE DEFAULT NULL,
    center_lng DOUBLE DEFAULT NULL,
    color_hex VARCHAR(7) DEFAULT '#22c55e',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (institution_id) REFERENCES institutions(id) ON DELETE CASCADE,
    INDEX idx_zones_institution (institution_id)
) ENGINE=InnoDB;

-- 4. Species (master dictionary)
CREATE TABLE species (
    id CHAR(36) PRIMARY KEY DEFAULT (UUID()),
    scientific_name VARCHAR(255) NOT NULL UNIQUE,
    common_name VARCHAR(255) DEFAULT NULL,
    family VARCHAR(255) DEFAULT NULL,
    native_status ENUM('native','introduced','invasive','unknown') DEFAULT 'unknown',
    description TEXT DEFAULT NULL,
    medicinal_uses TEXT DEFAULT NULL,
    reference_image_url VARCHAR(500) DEFAULT NULL,
    ai_source VARCHAR(50) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_species_family (family),
    INDEX idx_species_native (native_status),
    FULLTEXT INDEX ft_species_search (scientific_name, common_name, family)
) ENGINE=InnoDB;

-- 5. Plant Records (core table - one row per physical plant instance)
CREATE TABLE plant_records (
    id CHAR(36) PRIMARY KEY DEFAULT (UUID()),
    institution_id CHAR(36) NOT NULL,
    species_id CHAR(36) DEFAULT NULL,
    zone_id CHAR(36) DEFAULT NULL,
    latitude DOUBLE NOT NULL,
    longitude DOUBLE NOT NULL,
    location_accuracy_m FLOAT DEFAULT NULL,
    status ENUM('pending_verification','verified','rejected') DEFAULT 'pending_verification',
    ai_confidence DECIMAL(5,2) DEFAULT NULL,
    ai_candidates JSON DEFAULT NULL,
    notes TEXT DEFAULT NULL,
    submitted_by CHAR(36) NOT NULL,
    verified_by CHAR(36) DEFAULT NULL,
    verified_at TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (institution_id) REFERENCES institutions(id) ON DELETE CASCADE,
    FOREIGN KEY (species_id) REFERENCES species(id) ON DELETE SET NULL,
    FOREIGN KEY (zone_id) REFERENCES zones(id) ON DELETE SET NULL,
    FOREIGN KEY (submitted_by) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (verified_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_plants_institution (institution_id),
    INDEX idx_plants_species (species_id),
    INDEX idx_plants_status (status),
    INDEX idx_plants_location (latitude, longitude),
    INDEX idx_plants_submitted (submitted_by),
    INDEX idx_plants_created (created_at)
) ENGINE=InnoDB;

-- 6. Plant Photos (multiple photos per record)
CREATE TABLE plant_photos (
    id CHAR(36) PRIMARY KEY DEFAULT (UUID()),
    plant_record_id CHAR(36) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    original_name VARCHAR(255) DEFAULT NULL,
    file_size INT DEFAULT NULL,
    is_primary TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (plant_record_id) REFERENCES plant_records(id) ON DELETE CASCADE,
    INDEX idx_photos_plant (plant_record_id)
) ENGINE=InnoDB;

-- 7. Verifications (audit trail)
CREATE TABLE verifications (
    id CHAR(36) PRIMARY KEY DEFAULT (UUID()),
    plant_record_id CHAR(36) NOT NULL,
    action ENUM('approved','rejected','edited','merged') NOT NULL,
    reason TEXT DEFAULT NULL,
    old_species_id CHAR(36) DEFAULT NULL,
    new_species_id CHAR(36) DEFAULT NULL,
    performed_by CHAR(36) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (plant_record_id) REFERENCES plant_records(id) ON DELETE CASCADE,
    FOREIGN KEY (performed_by) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_verif_plant (plant_record_id),
    INDEX idx_verif_performer (performed_by)
) ENGINE=InnoDB;

-- 8. QR Codes
CREATE TABLE qr_codes (
    id CHAR(36) PRIMARY KEY DEFAULT (UUID()),
    plant_record_id CHAR(36) UNIQUE NOT NULL,
    public_slug VARCHAR(10) UNIQUE NOT NULL,
    qr_image_path VARCHAR(500) DEFAULT NULL,
    scan_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (plant_record_id) REFERENCES plant_records(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 9. Activity Log (for live feed / analytics)
CREATE TABLE activity_log (
    id CHAR(36) PRIMARY KEY DEFAULT (UUID()),
    institution_id CHAR(36) NOT NULL,
    user_id CHAR(36) DEFAULT NULL,
    action_type VARCHAR(50) NOT NULL,
    entity_type VARCHAR(50) NOT NULL,
    entity_id CHAR(36) DEFAULT NULL,
    description TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (institution_id) REFERENCES institutions(id) ON DELETE CASCADE,
    INDEX idx_activity_institution (institution_id),
    INDEX idx_activity_created (created_at)
) ENGINE=InnoDB;

-- 10. Sessions table for PHP session management
CREATE TABLE sessions (
    id VARCHAR(128) PRIMARY KEY,
    user_id CHAR(36) NOT NULL,
    token VARCHAR(255) UNIQUE NOT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    user_agent TEXT DEFAULT NULL,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_sessions_token (token),
    INDEX idx_sessions_expires (expires_at)
) ENGINE=InnoDB;

-- ============================================
-- Utility: Haversine distance function (replaces PostGIS)
-- Returns distance in meters between two lat/lng pairs
-- ============================================
DELIMITER //
CREATE FUNCTION haversine_distance(
    lat1 DOUBLE, lng1 DOUBLE,
    lat2 DOUBLE, lng2 DOUBLE
) RETURNS DOUBLE DETERMINISTIC
BEGIN
    DECLARE R DOUBLE DEFAULT 6371000;
    DECLARE dLat DOUBLE;
    DECLARE dLng DOUBLE;
    DECLARE a DOUBLE;
    DECLARE c DOUBLE;
    
    SET dLat = RADIANS(lat2 - lat1);
    SET dLng = RADIANS(lng2 - lng1);
    SET a = SIN(dLat/2) * SIN(dLat/2) + COS(RADIANS(lat1)) * COS(RADIANS(lat2)) * SIN(dLng/2) * SIN(dLng/2);
    SET c = 2 * ATAN2(SQRT(a), SQRT(1-a));
    
    RETURN R * c;
END //
DELIMITER ;

-- ============================================
-- Views for common queries
-- ============================================

-- Full plant detail view (joins species, user, zone info)
CREATE VIEW v_plant_details AS
SELECT 
    pr.id,
    pr.institution_id,
    pr.latitude,
    pr.longitude,
    pr.status,
    pr.ai_confidence,
    pr.notes,
    pr.created_at,
    pr.updated_at,
    s.scientific_name,
    s.common_name,
    s.family,
    s.native_status,
    s.description AS species_description,
    s.medicinal_uses,
    s.reference_image_url,
    z.name AS zone_name,
    z.color_hex AS zone_color,
    u.full_name AS submitted_by_name,
    u.avatar_url AS submitted_by_avatar,
    v.full_name AS verified_by_name,
    pp.file_path AS primary_photo,
    qr.public_slug AS qr_slug
FROM plant_records pr
LEFT JOIN species s ON pr.species_id = s.id
LEFT JOIN zones z ON pr.zone_id = z.id
LEFT JOIN users u ON pr.submitted_by = u.id
LEFT JOIN users v ON pr.verified_by = v.id
LEFT JOIN plant_photos pp ON pp.plant_record_id = pr.id AND pp.is_primary = 1
LEFT JOIN qr_codes qr ON qr.plant_record_id = pr.id;

-- Analytics summary view
CREATE VIEW v_analytics_summary AS
SELECT 
    pr.institution_id,
    COUNT(DISTINCT pr.id) AS total_plants,
    COUNT(DISTINCT pr.species_id) AS total_species,
    COUNT(DISTINCT pr.submitted_by) AS total_contributors,
    SUM(CASE WHEN pr.status = 'verified' THEN 1 ELSE 0 END) AS verified_count,
    SUM(CASE WHEN pr.status = 'pending_verification' THEN 1 ELSE 0 END) AS pending_count,
    SUM(CASE WHEN pr.status = 'rejected' THEN 1 ELSE 0 END) AS rejected_count,
    SUM(CASE WHEN s.native_status = 'native' THEN 1 ELSE 0 END) AS native_count,
    SUM(CASE WHEN s.native_status = 'introduced' THEN 1 ELSE 0 END) AS introduced_count,
    SUM(CASE WHEN s.native_status = 'invasive' THEN 1 ELSE 0 END) AS invasive_count,
    SUM(CASE WHEN s.native_status = 'unknown' THEN 1 ELSE 0 END) AS unknown_status_count
FROM plant_records pr
LEFT JOIN species s ON pr.species_id = s.id
GROUP BY pr.institution_id;
