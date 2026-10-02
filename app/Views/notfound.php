<section class="card notice">
    <h1>404</h1>
    <p><?= e(t($message ?? 'Página no encontrada.')) ?></p>
    <p><a href="<?= e(url('/')) ?>"><?= e(t('Ir al inicio')) ?></a>
    <?php if (!empty($versions)): ?> · <a href="<?= e(url($versions[0]['code'])) ?>"><?= e(t('Explorar la Biblia')) ?></a><?php endif; ?>
    </p>
</section>
