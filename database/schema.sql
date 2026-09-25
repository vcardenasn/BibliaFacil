-- BibliaFacil — schema.sql (MySQL, importable por phpMyAdmin)
-- Estructura + seeds de versions/books. Los versículos se cargan con
-- scripts/import_bible.php (fuentes en database/sources/).

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS migrations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL UNIQUE,
    batch INT NOT NULL,
    executed_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO migrations (name, batch) VALUES ('0001_create_biblia.php', 1)
ON DUPLICATE KEY UPDATE name = name;

CREATE TABLE IF NOT EXISTS versions (
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(20) NOT NULL,
    name VARCHAR(160) NOT NULL,
    language CHAR(3) NOT NULL DEFAULT 'es',
    copyright VARCHAR(500) NULL,
    license VARCHAR(60) NOT NULL DEFAULT 'unknown',
    license_status VARCHAR(20) NOT NULL DEFAULT 'open',
    source_url VARCHAR(500) NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_versions_code (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS books (
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    ord INT NOT NULL,
    osis VARCHAR(8) NOT NULL,
    name VARCHAR(60) NOT NULL,
    slug VARCHAR(60) NOT NULL,
    aliases VARCHAR(500) NOT NULL DEFAULT '',
    testament VARCHAR(2) NOT NULL,
    chapters INT NOT NULL,
    UNIQUE KEY uq_books_ord (ord),
    UNIQUE KEY uq_books_osis (osis),
    UNIQUE KEY uq_books_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS verses (
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    version_id INT NOT NULL,
    book_id INT NOT NULL,
    chapter INT NOT NULL,
    verse INT NOT NULL,
    text TEXT NOT NULL,
    UNIQUE KEY uq_verses_ref (version_id, book_id, chapter, verse),
    KEY idx_verses_chapter (version_id, book_id, chapter),
    FULLTEXT KEY ft_verses_text (text),
    CONSTRAINT fk_verses_version FOREIGN KEY (version_id) REFERENCES versions(id),
    CONSTRAINT fk_verses_book FOREIGN KEY (book_id) REFERENCES books(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seeds: versions
INSERT INTO versions (code, name, language, copyright, license, license_status, source_url, active) VALUES
('rvr1909', 'Reina-Valera 1909', 'es', 'Dominio público', 'public_domain', 'open', 'https://ebible.org/sparvr/', 1),
('kjv', 'King James Version', 'en', 'Public Domain', 'public_domain', 'open', 'https://ebible.org/eng-kjv2006/', 1),
('rvr1960', 'Reina-Valera 1960', 'es', '© 1960 Sociedades Bíblicas en América Latina; © renovado 1988 Sociedades Bíblicas Unidas', 'copyrighted', 'requested', NULL, 0),
('nvi', 'Nueva Versión Internacional', 'es', '© Biblica, Inc.', 'copyrighted', 'requested', NULL, 0),
('ntv', 'Nueva Traducción Viviente', 'es', '© Tyndale House Foundation', 'copyrighted', 'requested', NULL, 0),
('nbla', 'Nueva Biblia de las Américas', 'es', '© The Lockman Foundation', 'copyrighted', 'requested', NULL, 0),
('lbla', 'La Biblia de las Américas', 'es', '© The Lockman Foundation', 'copyrighted', 'requested', NULL, 0),
('dhh', 'Dios Habla Hoy', 'es', '© Sociedades Bíblicas Unidas', 'copyrighted', 'requested', NULL, 0),
('tla', 'Traducción en Lenguaje Actual', 'es', '© United Bible Societies', 'copyrighted', 'requested', NULL, 0),
('pdt', 'Palabra de Dios para Todos', 'es', '© Centro Mundial de Traducción de la Biblia / Bible League International', 'copyrighted', 'requested', NULL, 0)
ON DUPLICATE KEY UPDATE name = VALUES(name), license_status = VALUES(license_status);

-- Seeds: books (66, canon protestante)
INSERT INTO books (ord, osis, name, slug, aliases, testament, chapters) VALUES
(1, 'GEN', 'Génesis', 'genesis', 'génesis,genesis,gen,gn', 'AT', 50),
(2, 'EXO', 'Éxodo', 'exodo', 'éxodo,exodo,exo,ex', 'AT', 40),
(3, 'LEV', 'Levítico', 'levitico', 'levítico,levitico,lev,lv', 'AT', 27),
(4, 'NUM', 'Números', 'numeros', 'números,numeros,num,nm', 'AT', 36),
(5, 'DEU', 'Deuteronomio', 'deuteronomio', 'deuteronomio,deut,dt', 'AT', 34),
(6, 'JOS', 'Josué', 'josue', 'josué,josue,jos', 'AT', 24),
(7, 'JDG', 'Jueces', 'jueces', 'jueces,jue,juec', 'AT', 21),
(8, 'RUT', 'Rut', 'rut', 'rut,ruth,rt', 'AT', 4),
(9, '1SA', '1 Samuel', '1-samuel', '1 samuel,primer samuel,primera samuel,1 sam,1sa,1 sa', 'AT', 31),
(10, '2SA', '2 Samuel', '2-samuel', '2 samuel,segundo samuel,segunda samuel,2 sam,2sa,2 sa', 'AT', 24),
(11, '1KI', '1 Reyes', '1-reyes', '1 reyes,primer reyes,primera reyes,1 rey,1re,1 re', 'AT', 22),
(12, '2KI', '2 Reyes', '2-reyes', '2 reyes,segundo reyes,segunda reyes,2 rey,2re,2 re', 'AT', 25),
(13, '1CH', '1 Crónicas', '1-cronicas', '1 crónicas,1 cronicas,primer crónicas,1 cro,1 cr,1ch', 'AT', 29),
(14, '2CH', '2 Crónicas', '2-cronicas', '2 crónicas,2 cronicas,segundo crónicas,2 cro,2 cr,2ch', 'AT', 36),
(15, 'ESD', 'Esdras', 'esdras', 'esdras,esd', 'AT', 10),
(16, 'NEH', 'Nehemías', 'nehemias', 'nehemías,nehemias,neh', 'AT', 13),
(17, 'EST', 'Ester', 'ester', 'ester,est', 'AT', 10),
(18, 'JOB', 'Job', 'job', 'job,jb', 'AT', 42),
(19, 'PSA', 'Salmos', 'salmos', 'salmos,salmo,sal,sl,salm,ps', 'AT', 150),
(20, 'PRO', 'Proverbios', 'proverbios', 'proverbios,proverbio,prov,pro,pr', 'AT', 31),
(21, 'ECC', 'Eclesiastés', 'eclesiastes', 'eclesiastés,eclesiastes,ecl,ec', 'AT', 12),
(22, 'SNG', 'Cantares', 'cantares', 'cantares,cantar de los cantares,cantar,cnt', 'AT', 8),
(23, 'ISA', 'Isaías', 'isaias', 'isaías,isaias,isa,is', 'AT', 66),
(24, 'JER', 'Jeremías', 'jeremias', 'jeremías,jeremias,jer,jr', 'AT', 52),
(25, 'LAM', 'Lamentaciones', 'lamentaciones', 'lamentaciones,lam,lm', 'AT', 5),
(26, 'EZK', 'Ezequiel', 'ezequiel', 'ezequiel,eze,ezq', 'AT', 48),
(27, 'DAN', 'Daniel', 'daniel', 'daniel,dan,dn', 'AT', 12),
(28, 'HOS', 'Oseas', 'oseas', 'oseas,os', 'AT', 14),
(29, 'JOL', 'Joel', 'joel', 'joel,jl', 'AT', 3),
(30, 'AMO', 'Amós', 'amos', 'amós,amos,am', 'AT', 9),
(31, 'OBA', 'Abdías', 'abdias', 'abdías,abdias,abd', 'AT', 1),
(32, 'JON', 'Jonás', 'jonas', 'jonás,jonas,jon', 'AT', 4),
(33, 'MIC', 'Miqueas', 'miqueas', 'miqueas,miq', 'AT', 7),
(34, 'NAH', 'Nahúm', 'nahum', 'nahúm,nahum,nah', 'AT', 3),
(35, 'HAB', 'Habacuc', 'habacuc', 'habacuc,hab', 'AT', 3),
(36, 'ZEP', 'Sofonías', 'sofonias', 'sofonías,sofonias,sof', 'AT', 3),
(37, 'HAG', 'Hageo', 'hageo', 'hageo,hag', 'AT', 2),
(38, 'ZEC', 'Zacarías', 'zacarias', 'zacarías,zacarias,zac', 'AT', 14),
(39, 'MAL', 'Malaquías', 'malaquias', 'malaquías,malaquias,mal', 'AT', 4),
(40, 'MAT', 'Mateo', 'mateo', 'mateo,mat,mt,evangelio de mateo', 'NT', 28),
(41, 'MRK', 'Marcos', 'marcos', 'marcos,mar,mr,mc,evangelio de marcos', 'NT', 16),
(42, 'LUK', 'Lucas', 'lucas', 'lucas,luc,lc,evangelio de lucas', 'NT', 24),
(43, 'JHN', 'Juan', 'juan', 'juan,jn,evangelio de juan,evangelio según juan', 'NT', 21),
(44, 'ACT', 'Hechos', 'hechos', 'hechos,hech,hch,act,hechos de los apóstoles', 'NT', 28),
(45, 'ROM', 'Romanos', 'romanos', 'romanos,rom,ro', 'NT', 16),
(46, '1CO', '1 Corintios', '1-corintios', '1 corintios,primera corintios,primer corintios,1 cor,1co,1 co', 'NT', 16),
(47, '2CO', '2 Corintios', '2-corintios', '2 corintios,segunda corintios,segundo corintios,2 cor,2co,2 co', 'NT', 13),
(48, 'GAL', 'Gálatas', 'galatas', 'gálatas,galatas,gal,ga', 'NT', 6),
(49, 'EPH', 'Efesios', 'efesios', 'efesios,efe,ef', 'NT', 6),
(50, 'PHP', 'Filipenses', 'filipenses', 'filipenses,fil,flp', 'NT', 4),
(51, 'COL', 'Colosenses', 'colosenses', 'colosenses,col', 'NT', 4),
(52, '1TH', '1 Tesalonicenses', '1-tesalonicenses', '1 tesalonicenses,primera tesalonicenses,1 tes,1th,1 ts', 'NT', 5),
(53, '2TH', '2 Tesalonicenses', '2-tesalonicenses', '2 tesalonicenses,segunda tesalonicenses,2 tes,2th,2 ts', 'NT', 3),
(54, '1TI', '1 Timoteo', '1-timoteo', '1 timoteo,primera timoteo,primer timoteo,1 tim,1ti', 'NT', 6),
(55, '2TI', '2 Timoteo', '2-timoteo', '2 timoteo,segunda timoteo,segundo timoteo,2 tim,2ti', 'NT', 4),
(56, 'TIT', 'Tito', 'tito', 'tito,tit', 'NT', 3),
(57, 'PHM', 'Filemón', 'filemon', 'filemón,filemon,flm', 'NT', 1),
(58, 'HEB', 'Hebreos', 'hebreos', 'hebreos,heb', 'NT', 13),
(59, 'JAS', 'Santiago', 'santiago', 'santiago,sant,stg', 'NT', 5),
(60, '1PE', '1 Pedro', '1-pedro', '1 pedro,primera pedro,primer pedro,1 pe,1pe,1 ped', 'NT', 5),
(61, '2PE', '2 Pedro', '2-pedro', '2 pedro,segunda pedro,segundo pedro,2 pe,2pe,2 ped', 'NT', 3),
(62, '1JN', '1 Juan', '1-juan', '1 juan,primera juan,primer juan,1 jn,1jn', 'NT', 5),
(63, '2JN', '2 Juan', '2-juan', '2 juan,segunda juan,segundo juan,2 jn,2jn', 'NT', 1),
(64, '3JN', '3 Juan', '3-juan', '3 juan,tercera juan,tercer juan,3 jn,3jn', 'NT', 1),
(65, 'JUD', 'Judas', 'judas', 'judas,jud', 'NT', 1),
(66, 'REV', 'Apocalipsis', 'apocalipsis', 'apocalipsis,apo,apoc,rev,revelación', 'NT', 22)
ON DUPLICATE KEY UPDATE osis = VALUES(osis), name = VALUES(name), slug = VALUES(slug), aliases = VALUES(aliases), chapters = VALUES(chapters);
