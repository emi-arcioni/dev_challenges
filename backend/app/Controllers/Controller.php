<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Database;
use Pusher\Pusher;

class Controller
{
    protected $db;
    protected $pusher;

    public function __construct(Database $database)
    {
        $this->db = $database;
        $this->pusher = new Pusher(
            $_ENV['PUSHER_KEY'],
            $_ENV['PUSHER_SECRET'],
            $_ENV['PUSHER_APP_ID'],
            [
                'cluster' => $_ENV['PUSHER_CLUSTER'],
                'useTLS' => true
            ]
        );
    }

    public function response($content, $resp)
    {
        $resp->getBody()->write(json_encode($content));
        return $resp->withHeader('Content-Type', 'application/json');
    }
}