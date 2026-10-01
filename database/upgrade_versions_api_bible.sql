-- BibliaFacil — upgrade_versions_api_bible.sql
-- Agrega la columna api_bible_id a versions y activa las versiones
-- licenciadas vía API.Bible (NTV, NBLA — servidas por API en runtime,
-- sin copia local del texto).
-- Correr UNA VEZ en phpMyAdmin. Si responde "Duplicate column name
-- 'api_bible_id'", la columna ya existe — es seguro continuar.

ALTER TABLE versions ADD COLUMN api_bible_id VARCHAR(40) NULL;

INSERT INTO migrations (name, batch) VALUES ('0005_versions_api_bible.php', 2)
ON DUPLICATE KEY UPDATE name = name;

-- NTV — Nueva Traducción Viviente (© Tyndale House Foundation)
UPDATE versions SET
    license_status = 'approved', active = 1,
    api_bible_id = '826f63861180e056-01',
    source_url = 'https://tyndale.com'
WHERE code = 'ntv';

-- NBLA — Nueva Biblia de las Américas (© The Lockman Foundation)
UPDATE versions SET
    license_status = 'approved', active = 1,
    api_bible_id = 'ce11b813f9a27e20-01',
    source_url = 'https://www.lockman.org'
WHERE code = 'nbla';
