-- BibliaFacil — upgrade_verses_wj.sql
-- Agrega la columna wj (rangos JSON de palabras de Jesús) a verses.
-- Correr UNA VEZ en phpMyAdmin antes de importar verses_onbv.sql,
-- verses_pddpt.sql, verses_v1602p.sql o verses_sbl.sql.
-- Si phpMyAdmin responde "Duplicate column name 'wj'", la columna ya
-- existe — es seguro continuar con los archivos de versículos.

ALTER TABLE verses ADD COLUMN wj TEXT NULL;

INSERT INTO migrations (name, batch) VALUES ('0002_verses_wj.php', 2)
ON DUPLICATE KEY UPDATE name = name;
