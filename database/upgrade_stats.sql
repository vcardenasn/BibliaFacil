-- BibliaFacil — upgrade_stats.sql
-- Contador de visitas (precursor de EPIC 16). Correr UNA VEZ en phpMyAdmin.
-- Si "migrations" aún no existe (schema viejo), la última línea puede fallar
-- sin problema: lo importante es el CREATE TABLE.

CREATE TABLE IF NOT EXISTS stats (
    metric VARCHAR(40) NOT NULL,
    d DATE NOT NULL,
    n INT NOT NULL DEFAULT 0,
    PRIMARY KEY (metric, d)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO migrations (name, batch) VALUES ('0003_stats.php', 3)
ON DUPLICATE KEY UPDATE name = name;
