SET NAMES utf8mb4;

CREATE TABLE user_learning_goals (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, user_id BIGINT UNSIGNED NOT NULL,
  daily_question_target SMALLINT UNSIGNED NOT NULL, weekly_active_days TINYINT UNSIGNED NOT NULL,
  daily_minutes_target SMALLINT UNSIGNED NOT NULL, monthly_tryout_target SMALLINT UNSIGNED NOT NULL,
  timezone VARCHAR(64) NOT NULL, effective_from DATE NOT NULL, effective_until DATE NULL,
  created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL,
  INDEX learning_goals_effective_idx(user_id,effective_from,effective_until),
  FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
  CHECK(weekly_active_days BETWEEN 1 AND 7)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE daily_learning_stats (
  user_id BIGINT UNSIGNED NOT NULL, local_date DATE NOT NULL, timezone VARCHAR(64) NOT NULL,
  questions_completed INT UNSIGNED NOT NULL DEFAULT 0, correct_answers INT UNSIGNED NOT NULL DEFAULT 0,
  study_seconds INT UNSIGNED NOT NULL DEFAULT 0, tryouts_completed SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  goal_snapshot_json JSON NOT NULL, updated_at DATETIME NOT NULL,
  PRIMARY KEY(user_id,local_date), INDEX daily_learning_calendar_idx(user_id,local_date),
  FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE weekly_learning_stats (
  user_id BIGINT UNSIGNED NOT NULL, week_start DATE NOT NULL, timezone VARCHAR(64) NOT NULL,
  active_days TINYINT UNSIGNED NOT NULL DEFAULT 0, questions_completed INT UNSIGNED NOT NULL DEFAULT 0,
  study_seconds INT UNSIGNED NOT NULL DEFAULT 0, consistency_score DECIMAL(5,2) NOT NULL DEFAULT 0,
  updated_at DATETIME NOT NULL, PRIMARY KEY(user_id,week_start),
  FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE learning_streaks (
  user_id BIGINT UNSIGNED PRIMARY KEY, current_streak INT UNSIGNED NOT NULL DEFAULT 0,
  longest_streak INT UNSIGNED NOT NULL DEFAULT 0, last_active_date DATE NULL,
  timezone VARCHAR(64) NOT NULL, updated_at DATETIME NOT NULL,
  FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE learning_achievements (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, public_id CHAR(36) NOT NULL UNIQUE,
  user_id BIGINT UNSIGNED NOT NULL, achievement_key VARCHAR(80) NOT NULL,
  progress_value INT UNSIGNED NOT NULL DEFAULT 0, earned_at DATETIME NULL, created_at DATETIME NOT NULL,
  UNIQUE(user_id,achievement_key), FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Supports recommendation and analytics scans on large histories.
CREATE INDEX question_history_recommendation_idx ON user_question_histories(user_id,incorrect_count,last_answered_at);
CREATE INDEX attempt_answer_timing_idx ON attempt_answers(attempt_id,answered_at);
