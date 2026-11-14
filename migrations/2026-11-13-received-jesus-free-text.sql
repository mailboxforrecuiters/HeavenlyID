-- Heavenly ID: run ONCE on production MySQL before deploying the free-text update.
-- Back up the database first. Existing DATE values remain as text.
ALTER TABLE `card_designs_v2`
  MODIFY COLUMN `received_jesus_date` VARCHAR(255) NULL DEFAULT NULL;

SHOW COLUMNS FROM `card_designs_v2` LIKE 'received_jesus_date';
