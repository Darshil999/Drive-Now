-- DriveNow: upgrade an existing `carrental` database created from the ORIGINAL dump.
-- Fresh installs do NOT need this file; carrental.sql already contains these changes.
--
-- Run once (phpMyAdmin > carrental > SQL, or: mysql -u root carrental < 001_reliability_upgrade.sql).
-- Back up your database first. If a UNIQUE index fails, remove the duplicate rows it reports and re-run.

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";

-- 1. Zero-date default breaks on MySQL 5.7+/MariaDB strict mode.
ALTER TABLE `admin`
  MODIFY `updationDate` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp();

-- 2. Unicode-safe tables, transactional engine everywhere.
ALTER TABLE `tblpages` ENGINE=InnoDB;
ALTER TABLE `admin`             CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE `tblbooking`        CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE `tblbrands`         CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE `tblcontactusinfo`  CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE `tblcontactusquery` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE `tblpages`          CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE `tblsubscribers`    CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE `tbltestimonial`    CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE `tblusers`          CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE `tblvehicles`       CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

-- 3. Booking dates become real DATE columns (values are stored as YYYY-MM-DD).
UPDATE `tblbooking` SET `Status` = 0 WHERE `Status` IS NULL;
ALTER TABLE `tblbooking`
  MODIFY `FromDate` date NOT NULL,
  MODIFY `ToDate` date NOT NULL,
  MODIFY `Status` int(11) NOT NULL DEFAULT 0;

-- 4. Integrity + performance indexes.
ALTER TABLE `tblbooking`
  ADD UNIQUE KEY `uq_booking_number` (`BookingNumber`),
  ADD KEY `idx_booking_vehicle_dates` (`VehicleId`,`FromDate`,`ToDate`),
  ADD KEY `idx_booking_user` (`userEmail`);

ALTER TABLE `tblusers`
  DROP INDEX `EmailId`,
  ADD UNIQUE KEY `uq_users_email` (`EmailId`);

ALTER TABLE `tblsubscribers` ADD UNIQUE KEY `uq_subscriber_email` (`SubscriberEmail`);
ALTER TABLE `tblvehicles`    ADD KEY `idx_vehicles_brand` (`VehiclesBrand`);
ALTER TABLE `tblpages`       ADD UNIQUE KEY `uq_pages_type` (`type`);

-- Existing MD5 passwords keep working: they are upgraded to bcrypt automatically on the next login.
