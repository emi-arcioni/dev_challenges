<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Database;

class Controller
{
    protected $db;

    public function __construct(Database $database)
    {
        $this->db = $database;
    }

    public function response($content, $resp)
    {
        $resp->getBody()->write(json_encode($content));
        return $resp->withHeader('Content-Type', 'application/json');
    }
}