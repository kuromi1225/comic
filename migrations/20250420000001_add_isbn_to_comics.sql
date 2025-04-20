-- Add isbn column to comics table and make it unique
ALTER TABLE comics
ADD COLUMN isbn VARCHAR(13) NULL UNIQUE AFTER publisher;
-- Optional: Add index for faster lookups
-- CREATE INDEX idx_comics_isbn ON comics (isbn);