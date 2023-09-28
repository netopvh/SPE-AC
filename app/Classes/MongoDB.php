<?php

namespace App\Classes;

use App\Utils\ConectionMongoDB;

class MongoDB
{

    private $db = 'dados';
    private $collection = 'pontos';
    private $filter = [];
    private $options = [];
    private $update = [];

    function setDatabase($db)
    {
        $this->db = $db;
    }

    function setCollection($collection)
    {
        $this->collection = $collection;
    }

    function setFilter($key, $value)
    {
        $this->filter[$key] = $value;
    }

    function setOptions($key, $value)
    {
        $this->options[$key] = $value;
    }

    function setUpdate($key, $value)
    {
        $this->options[$key] = $value;
    }

    function executeQuery()
    {
        $manager = ConectionMongoDB::getInstance();
        $query = new \MongoDB\Driver\Query($this->filter, $this->options);
        $cursor = $manager->executeQuery($this->db . '.' . $this->collection, $query);

        return $cursor->toArray();
    }

    function insert($insert)
    {
        $manager = ConectionMongoDB::getInstance();
        $bulk = new \MongoDB\Driver\BulkWrite;
        $bulk->insert($insert);

        $manager->executeBulkWrite($this->db . '.' . $this->collection, $bulk);
    }

    function update($key, $update)
    {
        $manager = ConectionMongoDB::getInstance();
        $bulk = new \MongoDB\Driver\BulkWrite;
        $bulk->update(['_id' => $key], $update);

        $manager->executeBulkWrite($this->db . '.' . $this->collection, $bulk);
    }

    function distinct($distinct, $query = [])
    {
        $manager = ConectionMongoDB::getInstance();
        $cmd = new \MongoDB\Driver\Command([
            'distinct' => $this->collection,
            'key' => $distinct,
            'query' => $query
        ]);

        $cursor = $manager->executeCommand($this->db, $cmd);

        return $cursor->toArray();
    }
}