<section class="card notice">
    <h1>404</h1>
    <p><?= e($message ?? 'Página no encontrada.') ?></p>
    <p><a href="<?= e(url('/')) ?>">Ir al inicio</a>
    <?php if (!empty($versions)): ?> · <a href="<?= e(url($versions[0]['code'])) ?>">Explorar la Biblia</a><?php endif; ?>
    </p>
</section>
