-- DriveNow: token-based password reset (run once on databases created before this table existed).
-- Fresh installs do NOT need this file; carrental.sql already contains it.

CREATE TABLE IF NOT EXISTS `tblpasswordresets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `EmailId` varchar(100) NOT NULL,
  `TokenHash` char(64) NOT NULL,
  `ExpiresAt` datetime NOT NULL,
  `UsedAt` datetime DEFAULT NULL,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_reset_token` (`TokenHash`),
  KEY `idx_reset_email` (`EmailId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
