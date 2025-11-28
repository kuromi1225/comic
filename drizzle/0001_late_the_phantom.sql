CREATE TABLE `comics` (
	`id` int AUTO_INCREMENT NOT NULL,
	`isbn` varchar(20) NOT NULL,
	`title` text NOT NULL,
	`author` text,
	`publisher` text,
	`series` text,
	`imageUrl` text,
	`status` enum('unread','read') NOT NULL DEFAULT 'unread',
	`userId` int NOT NULL,
	`createdAt` timestamp NOT NULL DEFAULT (now()),
	`updatedAt` timestamp NOT NULL DEFAULT (now()) ON UPDATE CURRENT_TIMESTAMP,
	CONSTRAINT `comics_id` PRIMARY KEY(`id`),
	CONSTRAINT `comics_isbn_unique` UNIQUE(`isbn`)
);
--> statement-breakpoint
CREATE TABLE `newReleases` (
	`id` int AUTO_INCREMENT NOT NULL,
	`isbn` varchar(20) NOT NULL,
	`title` text NOT NULL,
	`author` text,
	`publisher` text,
	`series` text,
	`imageUrl` text,
	`releaseDate` timestamp,
	`purchased` int NOT NULL DEFAULT 0,
	`userId` int NOT NULL,
	`createdAt` timestamp NOT NULL DEFAULT (now()),
	CONSTRAINT `newReleases_id` PRIMARY KEY(`id`),
	CONSTRAINT `newReleases_isbn_unique` UNIQUE(`isbn`)
);
