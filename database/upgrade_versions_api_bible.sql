-- BibliaFacil — upgrade_versions_api_bible.sql
-- Agrega la columna api_bible_id a versions (versiones servidas vía API.Bible).
-- Correr UNA VEZ en phpMyAdmin. Si responde "Duplicate column name
-- 'api_bible_id'", la columna ya existe — es seguro continuar.

ALTER TABLE versions ADD COLUMN api_bible_id VARCHAR(40) NULL;

INSERT INTO migrations (name, batch) VALUES ('0005_versions_api_bible.php', 2)
ON DUPLICATE KEY UPDATE name = name;
