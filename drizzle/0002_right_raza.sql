ALTER TABLE `comics` ADD `volume` int;--> statement-breakpoint
ALTER TABLE `comics` ADD `imageData` text;--> statement-breakpoint
ALTER TABLE `comics` ADD `isRead` int DEFAULT false NOT NULL;--> statement-breakpoint
ALTER TABLE `comics` DROP COLUMN `status`;