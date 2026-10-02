<section class="card notice">
    <h1><?= e(t('Error')) ?></h1>
    <p><?= e(t($message ?? 'Ocurrió un error interno.')) ?></p>
    <p><a href="<?= e(url('/')) ?>"><?= e(t('Ir al inicio')) ?></a></p>
</section>
