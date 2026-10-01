-- BibliaFacil — upgrade_versions_api_bible.sql
-- Agrega api_bible_id a versions y activa NTV + NBLA (servidas por
-- API.Bible en runtime — sin copia local del texto).
-- Correr UNA VEZ en phpMyAdmin. Si responde "Duplicate column name
-- 'api_bible_id'", la columna ya existe — es seguro continuar con los
-- INSERT/UPDATE de abajo.

ALTER TABLE versions ADD COLUMN api_bible_id VARCHAR(40) NULL;

INSERT INTO migrations (name, batch) VALUES ('0005_versions_api_bible.php', 2)
ON DUPLICATE KEY UPDATE name = name;

-- NTV — Nueva Traducción Viviente (© Tyndale House Foundation)
-- INSERT por si la fila no existe en esta BD; UPDATE si ya existe.
INSERT INTO versions (code, name, language, copyright, license, license_status, source_url, api_bible_id, active)
VALUES ('ntv', 'Nueva Traducción Viviente', 'es', '© Tyndale House Foundation', 'copyrighted', 'approved', 'https://tyndale.com', '826f63861180e056-01', 1)
ON DUPLICATE KEY UPDATE
    license_status = 'approved', active = 1,
    api_bible_id = '826f63861180e056-01', source_url = 'https://tyndale.com';

-- NBLA — Nueva Biblia de las Américas (© The Lockman Foundation)
INSERT INTO versions (code, name, language, copyright, license, license_status, source_url, api_bible_id, active)
VALUES ('nbla', 'Nueva Biblia de las Américas', 'es', '© The Lockman Foundation', 'copyrighted', 'approved', 'https://www.lockman.org', 'ce11b813f9a27e20-01', 1)
ON DUPLICATE KEY UPDATE
    license_status = 'approved', active = 1,
    api_bible_id = 'ce11b813f9a27e20-01', source_url = 'https://www.lockman.org';
