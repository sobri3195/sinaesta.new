SET NAMES utf8mb4;

CREATE TABLE question_categories (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, public_id CHAR(36) NOT NULL UNIQUE,
  name VARCHAR(160) NOT NULL, slug VARCHAR(160) NOT NULL UNIQUE, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE question_topics (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, public_id CHAR(36) NOT NULL UNIQUE,
  category_id BIGINT UNSIGNED NOT NULL, name VARCHAR(160) NOT NULL, slug VARCHAR(160) NOT NULL,
  created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, UNIQUE(category_id,slug),
  CONSTRAINT question_topics_category_fk FOREIGN KEY(category_id) REFERENCES question_categories(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE question_tags (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, public_id CHAR(36) NOT NULL UNIQUE,
  name VARCHAR(100) NOT NULL, slug VARCHAR(100) NOT NULL UNIQUE, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE questions (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, public_id CHAR(36) NOT NULL UNIQUE,
  question_type ENUM('single_best_answer','multiple_response','true_false') NOT NULL DEFAULT 'single_best_answer',
  stem TEXT NOT NULL, clinical_vignette TEXT NULL, main_explanation TEXT NULL, learning_objective TEXT NOT NULL,
  category_id BIGINT UNSIGNED NOT NULL, topic_id BIGINT UNSIGNED NOT NULL,
  difficulty ENUM('easy','medium','hard') NOT NULL, author_user_id BIGINT UNSIGNED NOT NULL,
  reviewer_user_id BIGINT UNSIGNED NULL, current_version INT UNSIGNED NOT NULL DEFAULT 1,
  status ENUM('draft','in_review','revision','approved','published','archived') NOT NULL DEFAULT 'draft',
  published_at DATETIME NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL,
  INDEX questions_list_idx(status,category_id,topic_id,updated_at), INDEX questions_author_idx(author_user_id,status),
  CONSTRAINT questions_category_fk FOREIGN KEY(category_id) REFERENCES question_categories(id),
  CONSTRAINT questions_topic_fk FOREIGN KEY(topic_id) REFERENCES question_topics(id),
  CONSTRAINT questions_author_fk FOREIGN KEY(author_user_id) REFERENCES users(id),
  CONSTRAINT questions_reviewer_fk FOREIGN KEY(reviewer_user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE question_options (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, public_id CHAR(36) NOT NULL UNIQUE, question_id BIGINT UNSIGNED NOT NULL,
  option_order SMALLINT UNSIGNED NOT NULL, content TEXT NOT NULL, is_correct BOOLEAN NOT NULL DEFAULT FALSE,
  explanation TEXT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL,
  UNIQUE(question_id,option_order), CONSTRAINT question_options_question_fk FOREIGN KEY(question_id) REFERENCES questions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE question_tag_relations (
  question_id BIGINT UNSIGNED NOT NULL, tag_id BIGINT UNSIGNED NOT NULL, created_at DATETIME NOT NULL,
  PRIMARY KEY(question_id,tag_id), FOREIGN KEY(question_id) REFERENCES questions(id) ON DELETE CASCADE,
  FOREIGN KEY(tag_id) REFERENCES question_tags(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE question_explanations (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, question_id BIGINT UNSIGNED NOT NULL, locale VARCHAR(12) NOT NULL DEFAULT 'id',
  content TEXT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, UNIQUE(question_id,locale),
  FOREIGN KEY(question_id) REFERENCES questions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE question_references (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, public_id CHAR(36) NOT NULL UNIQUE, question_id BIGINT UNSIGNED NOT NULL,
  citation TEXT NOT NULL, reference_year SMALLINT UNSIGNED NOT NULL, url VARCHAR(2048) NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL,
  INDEX question_references_question_idx(question_id), FOREIGN KEY(question_id) REFERENCES questions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE question_media (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, public_id CHAR(36) NOT NULL UNIQUE, question_id BIGINT UNSIGNED NOT NULL,
  storage_key VARCHAR(512) NOT NULL, mime_type VARCHAR(100) NOT NULL, alt_text VARCHAR(500) NOT NULL,
  created_at DATETIME NOT NULL, FOREIGN KEY(question_id) REFERENCES questions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE question_versions (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, public_id CHAR(36) NOT NULL UNIQUE, question_id BIGINT UNSIGNED NOT NULL,
  version_number INT UNSIGNED NOT NULL, snapshot_json JSON NOT NULL,
  status ENUM('draft','in_review','revision','approved','published','archived') NOT NULL DEFAULT 'draft', published_at DATETIME NULL,
  created_by BIGINT UNSIGNED NOT NULL, created_at DATETIME NOT NULL,
  UNIQUE(question_id,version_number), FOREIGN KEY(question_id) REFERENCES questions(id), FOREIGN KEY(created_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE question_reviews (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, public_id CHAR(36) NOT NULL UNIQUE, question_id BIGINT UNSIGNED NOT NULL,
  version_number INT UNSIGNED NOT NULL, reviewer_user_id BIGINT UNSIGNED NOT NULL,
  decision ENUM('revision','approved') NOT NULL, note TEXT NULL, created_at DATETIME NOT NULL,
  INDEX question_reviews_idx(question_id,created_at), FOREIGN KEY(question_id) REFERENCES questions(id), FOREIGN KEY(reviewer_user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE question_status_histories (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, public_id CHAR(36) NOT NULL UNIQUE, question_id BIGINT UNSIGNED NOT NULL,
  from_status ENUM('draft','in_review','revision','approved','published','archived') NULL,
  to_status ENUM('draft','in_review','revision','approved','published','archived') NOT NULL,
  actor_user_id BIGINT UNSIGNED NOT NULL, note TEXT NULL, created_at DATETIME NOT NULL,
  INDEX question_status_history_idx(question_id,created_at), FOREIGN KEY(question_id) REFERENCES questions(id), FOREIGN KEY(actor_user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE question_import_batches (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, public_id CHAR(36) NOT NULL UNIQUE, uploaded_by BIGINT UNSIGNED NOT NULL,
  original_filename VARCHAR(255) NOT NULL, header_json JSON NOT NULL, status ENUM('preview','confirmed','imported','failed') NOT NULL,
  atomic_mode BOOLEAN NOT NULL DEFAULT TRUE, total_rows INT UNSIGNED NOT NULL DEFAULT 0, valid_rows INT UNSIGNED NOT NULL DEFAULT 0,
  error_rows INT UNSIGNED NOT NULL DEFAULT 0, confirmed_at DATETIME NULL, imported_at DATETIME NULL, created_at DATETIME NOT NULL,
  FOREIGN KEY(uploaded_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE question_import_rows (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, batch_id BIGINT UNSIGNED NOT NULL, row_number INT UNSIGNED NOT NULL,
  payload_json JSON NOT NULL, is_valid BOOLEAN NOT NULL, imported_question_id BIGINT UNSIGNED NULL, created_at DATETIME NOT NULL,
  UNIQUE(batch_id,row_number), FOREIGN KEY(batch_id) REFERENCES question_import_batches(id) ON DELETE CASCADE,
  FOREIGN KEY(imported_question_id) REFERENCES questions(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE question_import_errors (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, import_row_id BIGINT UNSIGNED NOT NULL, field_name VARCHAR(100) NOT NULL,
  error_code VARCHAR(100) NOT NULL, message VARCHAR(500) NOT NULL, created_at DATETIME NOT NULL,
  INDEX question_import_errors_row_idx(import_row_id), FOREIGN KEY(import_row_id) REFERENCES question_import_rows(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Status history is append-only. Application users should receive no DELETE privilege on this table.
