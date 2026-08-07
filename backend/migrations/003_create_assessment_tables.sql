SET NAMES utf8mb4;

ALTER TABLE users ADD COLUMN quiz_tokens INT UNSIGNED NOT NULL DEFAULT 0;

CREATE TABLE quiz_templates (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, public_id CHAR(36) NOT NULL UNIQUE,
  code VARCHAR(80) NOT NULL UNIQUE, name VARCHAR(160) NOT NULL,
  mode ENUM('topic','category','difficulty','random','daily','wrong','bookmark','mini_tryout','full_tryout','cbt') NOT NULL,
  question_count SMALLINT UNSIGNED NOT NULL, duration_seconds INT UNSIGNED NOT NULL,
  explanation_policy ENUM('after_answer','after_quiz','after_attempt','after_tryout_period','package_only','unavailable') NOT NULL DEFAULT 'after_attempt',
  active BOOLEAN NOT NULL DEFAULT TRUE, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE quiz_template_rules (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, template_id BIGINT UNSIGNED NOT NULL,
  rule_key VARCHAR(80) NOT NULL, rule_value JSON NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL,
  UNIQUE(template_id,rule_key), FOREIGN KEY(template_id) REFERENCES quiz_templates(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE tryouts (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, public_id CHAR(36) NOT NULL UNIQUE, template_id BIGINT UNSIGNED NOT NULL,
  title VARCHAR(200) NOT NULL, description TEXT NULL, starts_at DATETIME NOT NULL, ends_at DATETIME NOT NULL,
  result_available_at DATETIME NULL, max_attempts SMALLINT UNSIGNED NOT NULL DEFAULT 1, active BOOLEAN NOT NULL DEFAULT TRUE,
  created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, INDEX tryouts_availability_idx(active,starts_at,ends_at),
  FOREIGN KEY(template_id) REFERENCES quiz_templates(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE tryout_questions (
  tryout_id BIGINT UNSIGNED NOT NULL, question_id BIGINT UNSIGNED NOT NULL, question_order SMALLINT UNSIGNED NOT NULL,
  points DECIMAL(8,2) NOT NULL DEFAULT 1, PRIMARY KEY(tryout_id,question_id), UNIQUE(tryout_id,question_order),
  FOREIGN KEY(tryout_id) REFERENCES tryouts(id) ON DELETE CASCADE, FOREIGN KEY(question_id) REFERENCES questions(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE attempts (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, public_id CHAR(36) NOT NULL UNIQUE, user_id BIGINT UNSIGNED NOT NULL,
  template_id BIGINT UNSIGNED NOT NULL, tryout_id BIGINT UNSIGNED NULL,
  status ENUM('created','active','submitted','expired','auto_submitted','cancelled','invalidated') NOT NULL DEFAULT 'created',
  random_seed CHAR(64) NOT NULL, question_count SMALLINT UNSIGNED NOT NULL, duration_seconds INT UNSIGNED NOT NULL,
  token_cost INT UNSIGNED NOT NULL DEFAULT 0, started_at DATETIME NULL, expires_at DATETIME NULL, submitted_at DATETIME NULL,
  created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL,
  INDEX attempts_owner_idx(user_id,created_at), INDEX attempts_expiry_idx(status,expires_at),
  FOREIGN KEY(user_id) REFERENCES users(id), FOREIGN KEY(template_id) REFERENCES quiz_templates(id),
  FOREIGN KEY(tryout_id) REFERENCES tryouts(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE attempt_question_snapshots (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, public_id CHAR(36) NOT NULL UNIQUE, attempt_id BIGINT UNSIGNED NOT NULL,
  question_id BIGINT UNSIGNED NOT NULL, question_version INT UNSIGNED NOT NULL, question_order SMALLINT UNSIGNED NOT NULL,
  stem TEXT NOT NULL, clinical_vignette TEXT NULL, explanation TEXT NULL, learning_objective TEXT NOT NULL,
  category_id CHAR(36) NOT NULL, category_name VARCHAR(160) NOT NULL, topic_id CHAR(36) NOT NULL, topic_name VARCHAR(160) NOT NULL,
  difficulty ENUM('easy','medium','hard') NOT NULL, correct_option_public_id CHAR(36) NOT NULL,
  UNIQUE(attempt_id,question_id), UNIQUE(attempt_id,question_order), FOREIGN KEY(attempt_id) REFERENCES attempts(id) ON DELETE CASCADE,
  FOREIGN KEY(question_id) REFERENCES questions(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE attempt_option_snapshots (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, public_id CHAR(36) NOT NULL UNIQUE,
  attempt_question_snapshot_id BIGINT UNSIGNED NOT NULL, source_option_public_id CHAR(36) NOT NULL,
  option_order SMALLINT UNSIGNED NOT NULL, content TEXT NOT NULL, explanation TEXT NULL,
  UNIQUE(attempt_question_snapshot_id,source_option_public_id), UNIQUE(attempt_question_snapshot_id,option_order),
  FOREIGN KEY(attempt_question_snapshot_id) REFERENCES attempt_question_snapshots(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE attempt_answers (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, attempt_id BIGINT UNSIGNED NOT NULL,
  attempt_question_snapshot_id BIGINT UNSIGNED NOT NULL, option_snapshot_id BIGINT UNSIGNED NOT NULL,
  client_answer_id CHAR(36) NOT NULL, client_timestamp DATETIME NULL, answered_at DATETIME NOT NULL,
  UNIQUE(attempt_id,attempt_question_snapshot_id), UNIQUE(attempt_id,client_answer_id),
  FOREIGN KEY(attempt_id) REFERENCES attempts(id) ON DELETE CASCADE,
  FOREIGN KEY(attempt_question_snapshot_id) REFERENCES attempt_question_snapshots(id), FOREIGN KEY(option_snapshot_id) REFERENCES attempt_option_snapshots(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE attempt_flags (
  attempt_id BIGINT UNSIGNED NOT NULL, attempt_question_snapshot_id BIGINT UNSIGNED NOT NULL, created_at DATETIME NOT NULL,
  PRIMARY KEY(attempt_id,attempt_question_snapshot_id), FOREIGN KEY(attempt_id) REFERENCES attempts(id) ON DELETE CASCADE,
  FOREIGN KEY(attempt_question_snapshot_id) REFERENCES attempt_question_snapshots(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE attempt_events (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, public_id CHAR(36) NOT NULL UNIQUE, attempt_id BIGINT UNSIGNED NOT NULL,
  user_id BIGINT UNSIGNED NULL, event_type VARCHAR(80) NOT NULL, payload_json JSON NULL, occurred_at DATETIME NOT NULL,
  INDEX attempt_events_idx(attempt_id,occurred_at), FOREIGN KEY(attempt_id) REFERENCES attempts(id) ON DELETE CASCADE,
  FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE attempt_results (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, attempt_id BIGINT UNSIGNED NOT NULL UNIQUE,
  total_questions SMALLINT UNSIGNED NOT NULL, answered SMALLINT UNSIGNED NOT NULL, unanswered SMALLINT UNSIGNED NOT NULL,
  correct SMALLINT UNSIGNED NOT NULL, incorrect SMALLINT UNSIGNED NOT NULL, raw_score DECIMAL(8,2) NOT NULL,
  percentage_score DECIMAL(5,2) NOT NULL, duration_seconds INT UNSIGNED NOT NULL, average_time_seconds DECIMAL(10,2) NOT NULL,
  result_json JSON NOT NULL, calculated_at DATETIME NOT NULL, FOREIGN KEY(attempt_id) REFERENCES attempts(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE attempt_analytics (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, attempt_id BIGINT UNSIGNED NOT NULL,
  dimension_type ENUM('topic','category','difficulty') NOT NULL, dimension_key VARCHAR(160) NOT NULL,
  total SMALLINT UNSIGNED NOT NULL, correct SMALLINT UNSIGNED NOT NULL, incorrect SMALLINT UNSIGNED NOT NULL,
  unanswered SMALLINT UNSIGNED NOT NULL, percentage DECIMAL(5,2) NOT NULL,
  UNIQUE(attempt_id,dimension_type,dimension_key), FOREIGN KEY(attempt_id) REFERENCES attempts(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE user_question_histories (
  user_id BIGINT UNSIGNED NOT NULL, question_id BIGINT UNSIGNED NOT NULL, attempts_count INT UNSIGNED NOT NULL DEFAULT 0,
  correct_count INT UNSIGNED NOT NULL DEFAULT 0, incorrect_count INT UNSIGNED NOT NULL DEFAULT 0,
  last_answered_at DATETIME NOT NULL, PRIMARY KEY(user_id,question_id), FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY(question_id) REFERENCES questions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE user_bookmarks (
  user_id BIGINT UNSIGNED NOT NULL, question_id BIGINT UNSIGNED NOT NULL, created_at DATETIME NOT NULL,
  PRIMARY KEY(user_id,question_id), FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY(question_id) REFERENCES questions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE daily_quiz_assignments (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, public_id CHAR(36) NOT NULL UNIQUE, user_id BIGINT UNSIGNED NOT NULL,
  template_id BIGINT UNSIGNED NOT NULL, assignment_date DATE NOT NULL, attempt_id BIGINT UNSIGNED NULL, created_at DATETIME NOT NULL,
  UNIQUE(user_id,assignment_date), FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY(template_id) REFERENCES quiz_templates(id), FOREIGN KEY(attempt_id) REFERENCES attempts(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Results and snapshots are immutable by application convention; production DB roles should deny UPDATE/DELETE on them.
