USE lms_smk_karya_teladan;

CREATE TABLE IF NOT EXISTS password_reset_otps (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  otp_hash VARCHAR(255) NOT NULL,
  expired_at DATETIME NOT NULL,
  is_used TINYINT(1) NOT NULL DEFAULT 0,
  attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_password_reset_otps_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE,
  INDEX idx_password_reset_otps_user (user_id),
  INDEX idx_password_reset_otps_expired_at (expired_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
