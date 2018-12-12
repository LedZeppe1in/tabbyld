<?php
/* @var $column_heading_class_query_results app\modules\main\controllers\DefaultController */
/* @var $column_heading_concept_query_results app\modules\main\controllers\DefaultController */
/* @var $column_heading_property_query_results app\modules\main\controllers\DefaultController */
/* @var $all_column_heading_class_query_runtime app\modules\main\controllers\DefaultController */
/* @var $all_column_heading_concept_query_runtime app\modules\main\controllers\DefaultController */
/* @var $all_column_heading_property_query_runtime app\modules\main\controllers\DefaultController */
/* @var $parent_column_heading_class_candidates app\modules\main\controllers\DefaultController */
?>

<?php if($column_heading_class_query_results): ?>
    <h2><?= Yii::t('app', 'TABLE_ANNOTATION_PAGE_COLUMN_HEADING_CLASS_QUERY_RESULTS') .
        ' (' . round($all_column_heading_class_query_runtime, 3) . ')' ?></h2>
    <table class="table table-striped table-bordered">
        <tr>
            <?php foreach($column_heading_class_query_results[0]['result']['variables'] as $variable): ?>
                <td><b><?php printf('%-20.20s', $variable); ?></b></td>
            <?php endforeach; ?>
        </tr>
        <?php foreach($column_heading_class_query_results as $class_query_result): ?>
            <?php foreach($class_query_result['result']['rows'] as $row): ?>
                <tr>
                    <?php foreach($class_query_result['result']['variables'] as $variable): ?>
                        <td><?= $row[$variable]; ?></td>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
        <?php endforeach; ?>
    </table>
<?php endif; ?>

<?php if($column_heading_concept_query_results): ?>
    <h2><?= Yii::t('app', 'TABLE_ANNOTATION_PAGE_COLUMN_HEADING_CONCEPT_QUERY_RESULTS') .
        ' (' . round($all_column_heading_concept_query_runtime, 3) . ')' ?></h2>
    <table class="table table-striped table-bordered">
        <tr>
            <?php foreach($column_heading_concept_query_results[0]['result']['variables'] as $variable): ?>
                <td><b><?php printf('%-20.20s', $variable); ?></b></td>
            <?php endforeach; ?>
        </tr>
        <?php foreach($column_heading_concept_query_results as $concept_query_result): ?>
            <?php foreach($concept_query_result['result']['rows'] as $row): ?>
                <tr>
                    <?php foreach($concept_query_result['result']['variables'] as $variable): ?>
                        <td><?= $row[$variable]; ?></td>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
        <?php endforeach; ?>
    </table>
<?php endif; ?>

<?php if($column_heading_property_query_results): ?>
    <h2><?= Yii::t('app', 'TABLE_ANNOTATION_PAGE_COLUMN_HEADING_PROPERTY_QUERY_RESULTS') .
        ' (' . round($all_column_heading_property_query_runtime, 3) . ')' ?></h2>
    <table class="table table-striped table-bordered">
        <tr>
            <?php foreach($column_heading_property_query_results[0]['result']['variables'] as $variable): ?>
                <td><b><?php printf('%-20.20s', $variable); ?></b></td>
            <?php endforeach; ?>
        </tr>
        <?php foreach($column_heading_property_query_results as $property_query_result): ?>
            <?php foreach($property_query_result['result']['rows'] as $row): ?>
                <tr>
                    <?php foreach($property_query_result['result']['variables'] as $variable): ?>
                        <td><?= $row[$variable]; ?></td>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
        <?php endforeach; ?>
    </table>
<?php endif; ?>

<?php if($parent_column_heading_class_candidates): ?>
    <?php foreach($parent_column_heading_class_candidates as $parent_column_heading_class_candidate): ?>
        <h2><?= Yii::t('app', 'TABLE_ANNOTATION_PAGE_PARENT_CLASSES_FOR_ENTITY'); ?>
            <a href="<?= $parent_column_heading_class_candidate['result']['rows'][0]['callret-0']; ?>">
                <?= $parent_column_heading_class_candidate['result']['rows'][0]['callret-0']; ?>
            </a>
        </h2>
        <table class="table table-striped table-bordered">
            <tr>
                <td><b>Concept</b></td>
                <td><b>Property</b></td>
                <td><b>Class</b></td>
            </tr>
            <?php foreach($parent_column_heading_class_candidate['result']['rows'] as $row): ?>
                <tr>
                    <?php foreach($parent_column_heading_class_candidate['result']['variables'] as $variable): ?>
                        <td><?= $row[$variable]; ?></td>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php endforeach; ?>
<?php endif; ?>