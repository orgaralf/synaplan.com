<div>
    <h3 class="text-lg font-bold mb-4">Available Models:</h3>

    <?php
    $models = $groq->models()->list();
    ?>

    <ul class="gap-4">
        <?php foreach ($models['data'] as $model): ?>
            <li class="flex flex-col leading-tight mb-2">
                <strong><?php echo $model['id'] ?></strong>
                <small>by <?php echo $model['owned_by'] ?></small>
                <small>context window: <?php echo $model['context_window'] ?></small>
            </li>
        <?php endforeach; ?>
    </ul>
</div>