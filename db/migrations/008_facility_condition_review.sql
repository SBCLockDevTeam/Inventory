-- Facility Condition Review data-driven schema
CREATE TABLE IF NOT EXISTS facility_review_categories (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(255) NOT NULL UNIQUE,
  description TEXT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_facility_review_categories_sort (sort_order)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS facility_review_items (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  category_id BIGINT UNSIGNED NOT NULL,
  code VARCHAR(64) NOT NULL,
  name VARCHAR(255) NOT NULL,
  description TEXT NULL,
  default_severity ENUM('good','fair','poor','critical') NOT NULL DEFAULT 'good',
  sort_order INT NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_facility_review_items_category
    FOREIGN KEY (category_id) REFERENCES facility_review_categories(id) ON DELETE CASCADE,
  UNIQUE KEY uq_facility_review_item_category_code (category_id, code),
  KEY idx_facility_review_items_active (is_active, category_id, sort_order)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS facility_review_inspections (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  inspection_date DATE NOT NULL,
  facility_name VARCHAR(255) NOT NULL,
  inspector_name VARCHAR(255) NOT NULL,
  notes TEXT NULL,
  created_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_facility_review_inspections_date (inspection_date),
  KEY idx_facility_review_inspections_facility (facility_name)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS facility_review_results (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  inspection_id BIGINT UNSIGNED NOT NULL,
  item_id BIGINT UNSIGNED NOT NULL,
  condition_rating ENUM('good','fair','poor','critical') NOT NULL,
  notes TEXT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_facility_review_results_inspection
    FOREIGN KEY (inspection_id) REFERENCES facility_review_inspections(id) ON DELETE CASCADE,
  CONSTRAINT fk_facility_review_results_item
    FOREIGN KEY (item_id) REFERENCES facility_review_items(id) ON DELETE CASCADE,
  UNIQUE KEY uq_facility_review_result (inspection_id, item_id)
) ENGINE=InnoDB;
