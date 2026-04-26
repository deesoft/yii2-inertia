export interface TModel{
<?php foreach($modelType as $key => $type): ?>
    <?= "$key: $type;" ?> 
<?php endforeach; ?>
}