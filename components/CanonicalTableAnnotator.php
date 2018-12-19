<?php

namespace app\components;

use yii\bootstrap\Html;
use BorderCloud\SPARQL\SparqlClient;

/**
 * Class CanonicalTableAnnotator.
 *
 * @package app\components
 */
class CanonicalTableAnnotator
{
    const ENDPOINT_NAME = 'http://dbpedia.org/sparql'; // Название точки доступа SPARQL
    const DATA_TITLE = 'DATA';                         // Имя первого заголовка столбца канонической таблицы
    const ROW_HEADING_TITLE = 'RowHeading1';           // Имя второго заголовка столбца канонической таблицы
    const COLUMN_HEADING_TITLE = 'ColumnHeading';      // Имя третьего заголовка столбца канонической таблицы

    public $data_entities = array();              // Массив найденных концептов для столбца с данными "DATA"
    public $row_heading_entities = array();       // Массив найденных сущностей для столбца "RowHeading"
    public $column_heading_entities = array();    // Массив найденных сущностей для столбца "ColumnHeading"
    // Массив кандидатов родительских классов для концептов в "DATA"
    public $parent_data_class_candidates = array();
    // Массив кандидатов родительских классов для сущностей в "RowHeading"
    public $parent_row_heading_class_candidates = array();
    // Массив кандидатов родительских классов для сущностей в "ColumnHeading"
    public $parent_column_heading_class_candidates = array();
    // Массив с определенными родительскими классами для концептов в "DATA"
    public $parent_data_classes = array();
    // Массив с определенными родительскими классами для сущностей в "RowHeading"
    public $parent_row_heading_classes = array();
    // Массив сс определенными родительскими классами для сущностей в "ColumnHeading"
    public $parent_column_heading_classes = array();

    /**
     * Аннотирование столбцов "RowHeading" и "ColumnHeading" содержащих значения заголовков исходной таблицы.
     *
     * @param $data - данные каноничечкой таблицы
     * @param $heading_title - имя столбца
     * @return array - массив с результатми поиска сущностей в онтологии DBpedia
     */
    public function annotateTableHeading($data, $heading_title)
    {
        $formed_concepts = array();
        $formed_properties = array();
        // Формирование массивов неповторяющихся корректных значений столбцов для поиска концептов и свойств в отнологии
        foreach ($data as $item)
            foreach ($item as $heading => $value)
                if ($heading == $heading_title) {
                    $string_array = explode(" | ", $value);
                    foreach ($string_array as $string) {
                        // Формирование массива корректных значений для поиска концептов (классов)
                        $str = ucwords(strtolower($string));
                        $correct_string = str_replace(' ', '', $str);
                        $formed_concepts[$string] = $correct_string;
                        // Формирование массива корректных значений для поиска свойств классов (отношений)
                        $str = lcfirst(ucwords(strtolower($string)));
                        $correct_string = str_replace(' ', '', $str);
                        $formed_properties[$string] = $correct_string;
                    }
                }
        $formed_entities = array();
        $class_query_results = array();
        $concept_query_results = array();
        $property_query_results = array();
        // Обход массива корректных значений столбцов для поиска классов и концептов
        foreach ($formed_concepts as $fc_key => $fc_value) {
            // Подключение к DBpedia
            $sparql_client = new SparqlClient();
            $sparql_client->setEndpointRead(self::ENDPOINT_NAME);
            // SPARQL-запрос к DBpedia ontology для поиска классов
            $query = "PREFIX dbo: <http://dbpedia.org/ontology/>
                SELECT dbo:$fc_value ?property ?object
                WHERE { dbo:$fc_value ?property ?object }
                LIMIT 1";
            $rows = $sparql_client->query($query, 'rows');
            $error = $sparql_client->getErrors();
            // Если нет ошибок при запросе и есть результат запроса
            if (!$error && $rows['result']['rows']) {
                array_push($class_query_results, $rows);
                $formed_entities[$fc_key] = 'http://dbpedia.org/ontology/' . $fc_value;
                // Определение возможных родительских классов для найденного класса
                $this->searchParentClasses($sparql_client, $formed_entities[$fc_key], $heading_title);
            }
            else {
                // SPARQL-запрос к DBpedia resource для поиска концептов
                $query = "PREFIX db: <http://dbpedia.org/resource/>
                    SELECT db:$fc_value ?property ?object
                    WHERE { db:$fc_value ?property ?object }
                    LIMIT 1";
                $rows = $sparql_client->query($query, 'rows');
                $error = $sparql_client->getErrors();
                // Если нет ошибок при запросе и есть результат запроса
                if (!$error && $rows['result']['rows']) {
                    array_push($concept_query_results, $rows);
                    $formed_entities[$fc_key] = 'http://dbpedia.org/resource/' . $fc_value;
                    // Определение возможных родительских классов для найденного концепта
                    $this->searchParentClasses($sparql_client, $formed_entities[$fc_key], $heading_title);
                }
                else {
                    // Обход массива корректных знаечний столбцов для поиска свойств класса (отношений)
                    foreach ($formed_properties as $fp_key => $fp_value) {
                        if ($fp_key == $fc_key) {
                            // SPARQL-запрос к DBpedia ontology для поиска свойств классов (отношений)
                            $query = "PREFIX dbo: <http://dbpedia.org/ontology/>
                                SELECT ?concept dbo:$fp_value ?object
                                WHERE { ?concept dbo:$fp_value ?object }
                                LIMIT 1";
                            $rows = $sparql_client->query($query, 'rows');
                            $error = $sparql_client->getErrors();
                            // Если нет ошибок при запросе и есть результат запроса
                            if (!$error && $rows['result']['rows']) {
                                array_push($property_query_results, $rows);
                                $formed_entities[$fp_key] = 'http://dbpedia.org/ontology/' . $fp_value;
                            }
                            else {
                                // SPARQL-запрос к DBpedia property для поиска свойств классов (отношений)
                                $query = "PREFIX dbp: <http://dbpedia.org/property/>
                                    SELECT ?concept dbp:$fp_value ?object
                                    WHERE { ?concept dbp:$fp_value ?object }
                                    LIMIT 1";
                                $rows = $sparql_client->query($query, 'rows');
                                $error = $sparql_client->getErrors();
                                // Если нет ошибок при запросе и есть результат запроса
                                if (!$error && $rows['result']['rows']) {
                                    array_push($property_query_results, $rows);
                                    $formed_entities[$fp_key] = 'http://dbpedia.org/property/' . $fp_value;
                                }
                            }
                        }
                    }
                }
            }
        }
        // Сохранение результатов аннотирования для столбцов с заголовками
        if ($heading_title == self::ROW_HEADING_TITLE)
            $this->row_heading_entities = $formed_entities;
        if ($heading_title == self::COLUMN_HEADING_TITLE)
            $this->column_heading_entities = $formed_entities;

        return array($class_query_results, $concept_query_results, $property_query_results);
    }

    /**
     * Аннотирование содержимого столбца "DATA".
     *
     * @param $data - данные каноничечкой таблицы
     * @return array - массив с результатми поиска концептов в онтологии DBpedia
     */
    public function annotateTableData($data)
    {
        $formed_concepts = array();
        // Формирование массива неповторяющихся корректных значений столбца с данными для поиска концептов в отнологии
        foreach ($data as $item)
            foreach ($item as $key => $value)
                if ($key == self::DATA_TITLE) {
                    // Формирование массива корректных значений для поиска концептов (классов)
                    $str = ucwords(strtolower($value));
                    $correct_string = str_replace(' ', '', $str);
                    $formed_concepts[$value] = $correct_string;
                }
        $concept_query_results = array();
        // Обход массива корректных значений столбца с данными для поиска подходящих концептов
        foreach ($formed_concepts as $key => $value) {
            // Подключение к DBpedia
            $sparql_client = new SparqlClient();
            $sparql_client->setEndpointRead(self::ENDPOINT_NAME);
            // SPARQL-запрос к DBpedia resource для поиска концептов
            $query = "PREFIX db: <http://dbpedia.org/resource/>
                SELECT db:$value ?property ?object
                WHERE { db:$value ?property ?object }
                LIMIT 1";
            $rows = $sparql_client->query($query, 'rows');
            $error = $sparql_client->getErrors();
            // Если нет ошибок при запросе и есть результат запроса
            if (!$error && $rows['result']['rows']) {
                array_push($concept_query_results, $rows);
                $this->data_entities[$key] = 'http://dbpedia.org/resource/' . $value;
                // Определение возможных родительских классов для найденного концепта
                $this->searchParentClasses($sparql_client, $this->data_entities[$key], self::DATA_TITLE);
            }
        }

        return $concept_query_results;
    }

    /**
     * Определение списка кадидатов родительских классов и определение родительского класса из списка по умолчанию.
     *
     * @param $sparql_client - объект клиента подключения к DBpedia
     * @param $entity - сущность (концепт или класс) для которой необходимо найти родительские классы
     * @param $heading_title - название заголовка столбца канонической таблицы
     */
    public function searchParentClasses($sparql_client, $entity, $heading_title)
    {
        // SPARQL-запрос к DBpedia ontology для поиска родительских классов
        $query = "SELECT <$entity> ?property ?object
            FROM <http://dbpedia.org>
            WHERE {
                <$entity> ?property ?object
                FILTER (strstarts(str(?object), 'http://dbpedia.org/ontology/'))
            }
            LIMIT 100";
        $rows = $sparql_client->query($query, 'rows');
        if (isset($rows['result']) && $rows['result']['rows']) {
            if ($heading_title == self::DATA_TITLE) {
                $this->parent_data_class_candidates[$entity] =
                    self::rankingParentClassCandidates($sparql_client, $rows['result']['rows']);
                $this->parent_data_classes[$entity] = $this->parent_data_class_candidates[$entity][0][0];
            }
            if ($heading_title == self::ROW_HEADING_TITLE) {
                $this->parent_row_heading_class_candidates[$entity] =
                    self::rankingParentClassCandidates($sparql_client, $rows['result']['rows']);
                $this->parent_row_heading_classes[$entity] = $this->parent_row_heading_class_candidates[$entity][0][0];
            }
            if ($heading_title == self::COLUMN_HEADING_TITLE) {
                $this->parent_column_heading_class_candidates[$entity] =
                    self::rankingParentClassCandidates($sparql_client, $rows['result']['rows']);
                $this->parent_column_heading_classes[$entity] =
                    $this->parent_column_heading_class_candidates[$entity][0][0];
            }
        }
    }

    /**
     * Ранжирование списка кадидатов родительских классов.
     *
     * @param $sparql_client - объект клиента подключения к DBpedia
     * @param $parent_class_candidates - массив кадидатов родительских классов
     * @return array - массив ранжированных кадидатов родительских классов
     */
    public function rankingParentClassCandidates($sparql_client, $parent_class_candidates)
    {
        // Массив для ранжированных кандидатов родительских классов
        $ranked_parent_class_candidates = array();
        // Цикл по кандидатам родительских классов
        foreach($parent_class_candidates as $parent_data_class_candidate) {
            $parent_class = $parent_data_class_candidate['object'];
            // SPARQL-запрос к DBpedia для поиска эквивалентных классов
            $query = "SELECT ?class
                FROM <http://dbpedia.org>
                WHERE {
                    ?class owl:equivalentClass <$parent_class>
                    FILTER (strstarts(str(?class), 'http://dbpedia.org/ontology/'))
                }
                LIMIT 100";
            $rows = $sparql_client->query($query, 'rows');
            // Запоминание эквивалентного класса, если он был найден
            if (isset($rows['result']) && $rows['result']['rows'])
                $parent_class = $rows['result']['rows'][0]['class'];
            // SPARQL-запрос к DBpedia для определения кол-ва потомков данного класса до глобального класса owl:Thing
            $query = "SELECT COUNT(*) as ?Triples
                FROM <http://dbpedia.org>
                WHERE {
                    <$parent_class> rdfs:subClassOf+ ?class
                }
                LIMIT 100";
            $rows = $sparql_client->query($query, 'rows');
            // Добавление ранжированного кандидата родительского класса в массив
            array_push($ranked_parent_class_candidates,
                [$parent_data_class_candidate['object'], $rows['result']['rows'][0]['Triples']]);
        }
        // Сортировка массива ранжированных кандидатов родительских классов по убыванию их ранга
        for ($i = 0; $i < count($ranked_parent_class_candidates); $i++)
            for ($j = $i + 1; $j < count($ranked_parent_class_candidates); $j++)
                if ($ranked_parent_class_candidates[$i][1] <= $ranked_parent_class_candidates[$j][1]) {
                    $temp = $ranked_parent_class_candidates[$j];
                    $ranked_parent_class_candidates[$j] = $ranked_parent_class_candidates[$i];
                    $ranked_parent_class_candidates[$i] = $temp;
                }

        return $ranked_parent_class_candidates;
    }

    /**
     * Отображение сокращенного имени аннотированной сущности.
     *
     * @param $formed_entities - массив всех сформированных сущностей (аннотированных значениям в таблице)
     * @param $formed_parent_entities - массив определенных родительских классов для аннотированных значений (сущностей)
     * @param $cell_label - исходное название ячейки таблицы
     * @param $string_array - массив значений (меток) заголовков в ячейке таблицы
     * @param $current_key - текущий ключ значения (метки) заголовка в ячейке таблицы
     * @param $current_value - текущее значение (метка) заголовка в ячейке таблицы
     * @return string - сформированное название ячейки таблицы
     */
    public function displayAbbreviatedEntity($formed_entities, $formed_parent_entities, $cell_label,
                                             $string_array, $current_key, $current_value)
    {
        // Массив названий сегментов онтологии DBpedia
        $ontology_segment_name = [
            'http://dbpedia.org/ontology/',
            'http://dbpedia.org/resource/',
            'http://dbpedia.org/property/'
        ];
        // Массив префиксов для сегментов онтологии DBpedia
        $ontology_segment_prefix = ['dbo:', 'db:', 'dbp:'];
        // Цикл по массиву аннотированных элементов
        foreach ($formed_entities as $key => $formed_entity)
            if ($current_value == $key) {
                $abbreviated_concept = str_replace($ontology_segment_name,
                    $ontology_segment_prefix, $formed_entity, $count);
                if ($count == 0) {
                    if ($current_key > 0)
                        $cell_label .= $current_value;
                    if ($current_key == 0 && count($string_array) == 1)
                        $cell_label = $current_value;
                    if ($current_key == 0 && count($string_array) > 1)
                        $cell_label = $current_value . ' | ';
                }
                if ($count > 0) {
                    foreach ($formed_parent_entities as $fpe_key => $formed_parent_entity)
                        if ($formed_entity == $fpe_key) {
                            $abbreviated_parent_concept = str_replace($ontology_segment_name,
                                $ontology_segment_prefix, $formed_parent_entity);
                            $link = Html::a($abbreviated_parent_concept, ['#'],
                                [
                                    'class' => $formed_entity,
                                    'title' => $abbreviated_parent_concept,
                                    'data-toggle'=>'modal',
                                    'data-target'=>'#selectParentClassModalForm'
                                ]
                            );
                            if ($current_key > 0)
                                $cell_label .= $current_value . ' (' . $abbreviated_concept . ' - ' .
                                    $link . ')';
                            if ($current_key == 0 && count($string_array) == 1)
                                $cell_label = $current_value . ' (' . $abbreviated_concept . ' - ' .
                                    $link . ')';
                            if ($current_key == 0 && count($string_array) > 1)
                                $cell_label = $current_value . ' (' . $abbreviated_concept . ' - ' .
                                    $link . ') | ';
                        }
                }
            }

        return $cell_label;
    }
}