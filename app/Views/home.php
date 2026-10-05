<section class="home-hero" aria-labelledby="home-title">
    <div class="home-hero-copy">
        <span class="home-eyebrow"><?= e(t('Un espacio para leer, explorar y aprender')) ?></span>
        <h1 id="home-title"><?= e(t('La Biblia, fácil de leer y compartir.')) ?></h1>
        <p><?= e(t('Encuentra un versículo, explora un tema o aprende jugando. Gratis, sin anuncios y sin crear una cuenta.')) ?></p>
        <div class="home-actions">
            <a class="home-button primary" href="<?= e(url(($startVersion['code'] ?? 'rvr1909') . '/juan/1')) ?>"><?= e(t('Empezar a leer')) ?> <span aria-hidden="true">→</span></a>
            <a class="home-button secondary" href="<?= e(url('temas')) ?>"><?= e(t('Explorar temas')) ?></a>
            <a class="home-wa" href="https://wa.me/?text=<?= rawurlencode(t('Te comparto Palabra Fiel — la Biblia en español, gratis y sin anuncios:') . ' ' . \Biblia\Core\Seo::abs()) ?>" target="_blank" rel="noopener"><?= e(t('Compartir')) ?></a>
        </div>
        <?php if ($continue): ?>
        <a class="home-continue" href="<?= e(url($continue['path'])) ?>"><?= e(t('Continuar leyendo:')) ?> <strong><?= e($continue['label']) ?></strong><span> · <?= e($continue['version']) ?></span> <span aria-hidden="true">→</span></a>
        <?php endif; ?>
    </div>
    <?php if ($votd): ?>
    <article class="home-quote" aria-label="<?= e(t('Versículo del día')) ?>">
        <span class="home-quote-label"><?= e(t('Para hoy')) ?> · <?= e($votdVersion['name']) ?></span>
        <blockquote><?= \Biblia\Bible\VerseText::render($votd['text'], $votd['wj'] ?? null) ?></blockquote>
        <a href="<?= e(url('versiculo-del-dia')) ?>"><?= e(t($votd['book_name']) . ' ' . $votd['chapter'] . ':' . $votd['verse']) ?> · <?= e(t('Ver versículo del día')) ?> <span aria-hidden="true">→</span></a>
    </article>
    <?php endif; ?>
</section>

<section class="home-discovery" aria-labelledby="home-discovery-title">
    <div class="home-section-heading">
        <h2 id="home-discovery-title"><?= e(t('¿Qué buscas hoy?')) ?></h2>
        <p><?= e(t('Una palabra, una frase o una referencia: hay varias formas de empezar.')) ?></p>
    </div>
    <div class="home-searches">
        <form class="home-search-form" action="<?= e(url('buscar')) ?>" method="get" role="search" aria-label="<?= e(t('Buscar palabras en la Biblia')) ?>">
            <label for="home-search"><?= e(t('Busca una palabra o frase')) ?></label>
            <div class="home-input-row"><input id="home-search" name="q" type="search" placeholder="<?= e(t('Por ejemplo: esperanza')) ?>" required><button type="submit"><?= e(t('Buscar')) ?></button></div>
        </form>
        <form class="home-search-form" action="<?= e(url('ir')) ?>" method="get" role="search" aria-label="<?= e(t('Ir a una referencia bíblica')) ?>">
            <label for="home-reference"><?= e(t('¿Conoces la referencia?')) ?></label>
            <div class="home-input-row"><input id="home-reference" name="q" type="text" placeholder="<?= e(t('Por ejemplo: Juan 3:16')) ?>" required><button type="submit"><?= e(t('Ir')) ?></button></div>
        </form>
    </div>
    <div class="home-starters" aria-label="<?= e(t('Ideas para empezar')) ?>">
        <span><?= e(t('Prueba también:')) ?></span>
        <a href="<?= e(url('planes')) ?>"><?= e(t('Planes de lectura')) ?></a>
        <a href="<?= e(url('guias/como-leer')) ?>"><?= e(t('No sé por dónde empezar')) ?></a>
        <a href="<?= e(url('versiculo-del-dia')) ?>"><?= e(t('Versículo del día')) ?></a>
        <a href="<?= e(url('guias/que-version')) ?>"><?= e(t('¿Qué versión elegir?')) ?></a>
    </div>
</section>

<section class="home-section" aria-labelledby="home-themes-title">
    <div class="home-section-heading home-section-row">
        <div><h2 id="home-themes-title"><?= e(t('Explora lo que necesitas')) ?></h2><p><?= e(t('Versículos reunidos por tema, para leer a tu ritmo.')) ?></p></div>
        <a href="<?= e(url('temas')) ?>"><?= e(t('Ver todos los temas')) ?> <span aria-hidden="true">→</span></a>
    </div>
    <div class="home-card-grid">
        <?php foreach ($featuredThemes as $slug => $theme): ?>
        <a class="home-topic-card" href="<?= e(url('temas/' . $slug)) ?>">
            <span class="home-topic-icon" aria-hidden="true"><?= e($theme['emoji']) ?></span>
            <strong><?= e(t($theme['name'])) ?></strong>
            <span><?= e(t($theme['desc'])) ?></span>
        </a>
        <?php endforeach; ?>
    </div>
</section>

<section class="home-split" aria-label="<?= e(t('Otras formas de descubrir la Biblia')) ?>">
    <div class="home-feature">
        <span class="home-feature-icon" aria-hidden="true">📖</span>
        <h2><?= e(t('Lee a tu manera')) ?></h2>
        <p><?= e(t('Explora los 66 libros, elige una versión y ajusta el tamaño del texto. Tus notas y favoritos quedan en este dispositivo.')) ?></p>
        <a href="<?= e(url($votdVersion['code'] ?? 'rvr1909')) ?>"><?= e(t('Explorar los libros')) ?> <span aria-hidden="true">→</span></a>
    </div>
    <div class="home-feature home-feature-play">
        <span class="home-feature-icon" aria-hidden="true">✦</span>
        <h2><?= e(t('Aprende jugando')) ?></h2>
        <p><?= e(t('Trivia, memoria y versículos para completar: juegos bíblicos que se disfrutan en familia, sin registro.')) ?></p>
        <div class="home-game-links">
            <?php foreach ($featuredGames as $slug => $game): ?>
            <?php if (!empty($game['ready'])): ?><a href="<?= e(url('juegos/' . $slug)) ?>"><?= e(t($game['name'])) ?> <span aria-hidden="true">→</span></a><?php endif; ?>
            <?php endforeach; ?>
        </div>
        <a class="home-all-games" href="<?= e(url('juegos')) ?>"><?= e(t('Ver los juegos')) ?> <span aria-hidden="true">→</span></a>
    </div>
</section>

<section class="home-trust" aria-label="<?= e(t('Por qué Palabra Fiel')) ?>">
    <span><?= e(t('Sin anuncios')) ?></span><span><?= e(t('Sin crear cuenta')) ?></span><span><?= e(t('Lectura y juegos gratis')) ?></span>
</section>
