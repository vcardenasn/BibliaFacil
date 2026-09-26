-- BibliaFacil — UPGRADE métricas (EPIC 16)
-- Ejecutar UNA vez en phpMyAdmin sobre la BD existente.
-- Agregados anónimos por día + hashes de sesión rotativos (nunca IP cruda).

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS metrics_daily (
    metric VARCHAR(40) NOT NULL,
    dim VARCHAR(120) NOT NULL DEFAULT '',
    d DATE NOT NULL,
    n INT NOT NULL DEFAULT 0,
    PRIMARY KEY (metric, dim, d)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS metrics_dau (
    d DATE NOT NULL,
    h CHAR(64) NOT NULL,
    PRIMARY KEY (d, h)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Marcar la migración como ejecutada (por si luego corres migrate.php en prod)
INSERT IGNORE INTO migrations (name, batch) VALUES ('0004_metrics.php', 4);
