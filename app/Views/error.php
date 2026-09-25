<section class="card notice">
    <h1>Error</h1>
    <p><?= e($message ?? 'Ocurrió un error interno.') ?></p>
    <p><a href="<?= e(url('/')) ?>">Ir al inicio</a></p>
</section>
