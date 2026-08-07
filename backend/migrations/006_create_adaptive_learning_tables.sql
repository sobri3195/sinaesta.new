SET NAMES utf8mb4;

CREATE TABLE adaptive_learning_settings (
  setting_key VARCHAR(80) PRIMARY KEY, value_json JSON NOT NULL, version INT UNSIGNED NOT NULL DEFAULT 1,
  updated_by BIGINT UNSIGNED NULL, updated_at DATETIME NOT NULL,
  FOREIGN KEY(updated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE user_topic_mastery (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, user_id BIGINT UNSIGNED NOT NULL,
  dimension_type ENUM('category','topic','subtopic','difficulty') NOT NULL, dimension_id VARCHAR(80) NOT NULL,
  dimension_name VARCHAR(160) NOT NULL, mastery_score DECIMAL(5,2) NOT NULL,
  mastery_level VARCHAR(40) NOT NULL, attempts_count INT UNSIGNED NOT NULL DEFAULT 0,
  correct_count INT UNSIGNED NOT NULL DEFAULT 0, unanswered_count INT UNSIGNED NOT NULL DEFAULT 0,
  repeated_error_count INT UNSIGNED NOT NULL DEFAULT 0, average_response_seconds DECIMAL(10,2) NULL,
  trend DECIMAL(6,2) NOT NULL DEFAULT 0, last_practiced_at DATETIME NULL, calculated_at DATETIME NOT NULL,
  UNIQUE(user_id,dimension_type,dimension_id), INDEX mastery_user_score_idx(user_id,mastery_score,last_practiced_at),
  FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE, CHECK(mastery_score BETWEEN 0 AND 100)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE mastery_snapshots (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, user_id BIGINT UNSIGNED NOT NULL,
  dimension_type ENUM('overall','category','topic','subtopic','difficulty') NOT NULL, dimension_id VARCHAR(80) NOT NULL,
  mastery_score DECIMAL(5,2) NOT NULL, mastery_level VARCHAR(40) NOT NULL, captured_at DATETIME NOT NULL,
  INDEX mastery_history_idx(user_id,dimension_type,dimension_id,captured_at),
  FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE review_schedule (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, schedule_key VARCHAR(40) NOT NULL UNIQUE,
  interval_days SMALLINT UNSIGNED NOT NULL, sequence_number TINYINT UNSIGNED NOT NULL UNIQUE, active BOOLEAN NOT NULL DEFAULT TRUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE user_review_queue (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, public_id CHAR(32) NOT NULL UNIQUE, user_id BIGINT UNSIGNED NOT NULL,
  question_id BIGINT UNSIGNED NOT NULL, topic_id BIGINT UNSIGNED NOT NULL, mastery_before DECIMAL(5,2) NOT NULL DEFAULT 0,
  mastery_after DECIMAL(5,2) NULL, review_count INT UNSIGNED NOT NULL DEFAULT 0, last_reviewed_at DATETIME NULL,
  next_review_at DATETIME NOT NULL, interval_days SMALLINT UNSIGNED NOT NULL DEFAULT 1, priority DECIMAL(5,2) NOT NULL,
  status ENUM('due','scheduled','snoozed','completed','archived') NOT NULL DEFAULT 'scheduled',
  recovery_stage ENUM('wrong','review','correct','reinforcement','recovered') NOT NULL DEFAULT 'wrong',
  failure_count INT UNSIGNED NOT NULL DEFAULT 1, snooze_count TINYINT UNSIGNED NOT NULL DEFAULT 0,
  snoozed_until DATETIME NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL,
  UNIQUE(user_id,question_id), INDEX review_due_idx(user_id,status,next_review_at,priority),
  INDEX review_recent_failure_idx(user_id,failure_count,last_reviewed_at),
  FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE, FOREIGN KEY(question_id) REFERENCES questions(id),
  FOREIGN KEY(topic_id) REFERENCES question_topics(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE review_histories (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, queue_id BIGINT UNSIGNED NOT NULL, user_id BIGINT UNSIGNED NOT NULL,
  question_id BIGINT UNSIGNED NOT NULL, was_correct BOOLEAN NOT NULL, response_seconds INT UNSIGNED NULL,
  confidence TINYINT UNSIGNED NULL, mastery_before DECIMAL(5,2) NOT NULL, mastery_after DECIMAL(5,2) NOT NULL,
  interval_before SMALLINT UNSIGNED NOT NULL, interval_after SMALLINT UNSIGNED NOT NULL,
  stage_before VARCHAR(24) NOT NULL, stage_after VARCHAR(24) NOT NULL, reviewed_at DATETIME NOT NULL,
  INDEX review_history_owner_idx(user_id,reviewed_at), FOREIGN KEY(queue_id) REFERENCES user_review_queue(id) ON DELETE CASCADE,
  FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE, FOREIGN KEY(question_id) REFERENCES questions(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE adaptive_recommendations (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, public_id CHAR(32) NOT NULL UNIQUE, user_id BIGINT UNSIGNED NOT NULL,
  topic_id BIGINT UNSIGNED NOT NULL, mode ENUM('recommended','weak_topic','mastery_builder','wrong_recovery','mixed','difficulty_progression','quick_recovery') NOT NULL,
  mastery_score DECIMAL(5,2) NOT NULL, priority_score DECIMAL(5,2) NOT NULL, reason_json JSON NOT NULL,
  recommended_question_count SMALLINT UNSIGNED NOT NULL, expires_at DATETIME NOT NULL, created_at DATETIME NOT NULL,
  INDEX recommendation_active_idx(user_id,mode,expires_at,priority_score), FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY(topic_id) REFERENCES question_topics(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE adaptive_recommendation_logs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, recommendation_id BIGINT UNSIGNED NULL, user_id BIGINT UNSIGNED NOT NULL,
  event_type ENUM('generated','shown','started','dismissed','completed') NOT NULL, context_json JSON NULL, created_at DATETIME NOT NULL,
  INDEX recommendation_log_idx(user_id,created_at), FOREIGN KEY(recommendation_id) REFERENCES adaptive_recommendations(id) ON DELETE SET NULL,
  FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO review_schedule(schedule_key,interval_days,sequence_number) VALUES ('day_1',1,1),('day_3',3,2),('day_7',7,3),('day_14',14,4),('day_30',30,5),('day_60',60,6);
INSERT INTO adaptive_learning_settings(setting_key,value_json,updated_at) VALUES
('mastery_weights',JSON_OBJECT('accuracy',.38,'difficulty',.14,'evidence',.12,'recency',.10,'response_time',.08,'confidence',.05,'consistency',.13,'repeated_error_penalty',4),UTC_TIMESTAMP()),
('mastery_levels',JSON_ARRAY(JSON_OBJECT('min',0,'max',39,'key','needs_review','label','Needs Review'),JSON_OBJECT('min',40,'max',59,'key','developing','label','Developing'),JSON_OBJECT('min',60,'max',74,'key','intermediate','label','Intermediate'),JSON_OBJECT('min',75,'max',89,'key','strong','label','Strong'),JSON_OBJECT('min',90,'max',100,'key','mastered','label','Mastered')),UTC_TIMESTAMP()),
('recommendation_rules',JSON_OBJECT('minimum_attempts',5,'maximum_repetition',3,'default_size',15,'stale_days',7,'minimum_difficulty_evidence',5),UTC_TIMESTAMP()),
('review_intervals',JSON_ARRAY(1,3,7,14,30,60),UTC_TIMESTAMP());

INSERT INTO plan_features(public_id,entitlement_key,name,sort_order,created_at) VALUES
(LOWER(HEX(RANDOM_BYTES(16))),'adaptive_practice','Adaptive Practice',160,UTC_TIMESTAMP()),
(LOWER(HEX(RANDOM_BYTES(16))),'spaced_repetition','Spaced Repetition',170,UTC_TIMESTAMP()),
(LOWER(HEX(RANDOM_BYTES(16))),'mastery_map','Mastery Map',180,UTC_TIMESTAMP()),
(LOWER(HEX(RANDOM_BYTES(16))),'smart_review_queue','Smart Review Queue',190,UTC_TIMESTAMP()),
(LOWER(HEX(RANDOM_BYTES(16))),'advanced_recommendation','Advanced Recommendation',200,UTC_TIMESTAMP());
INSERT INTO plan_feature_values(plan_id,feature_id,value_type,boolean_value,created_at)
SELECT p.id,f.id,'boolean',p.slug='pro-90',UTC_TIMESTAMP() FROM subscription_plans p CROSS JOIN plan_features f
WHERE p.plan_type='subscription' AND f.entitlement_key IN ('adaptive_practice','spaced_repetition','mastery_map','smart_review_queue','advanced_recommendation');
