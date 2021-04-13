<?php
declare(strict_types=1);

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Container\ContainerInterface;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpNotFoundException;

class IssueController extends Controller
{   
    public function get(Request $request, Response $resp, array $args)
    {
        $id = $args['id'];
        $issue_exists = $this->db->redis->exists($id);

        if ($issue_exists) {
            $data = $this->db->redis->hgetall($id);
            if ($data['status'] == 'voting') {
                // Because during voting status the votes are secret you must hide each vote until all members voted.
                $members = array_map(function($member) {
                    return [
                        'name' => $member['name'],
                        'status' => $member['status']
                    ];
                }, json_decode($data['members'], true));
            } else {

            }

            $response = [
                'status' => $data['status'],
                'members' => $members
            ];
        } else {
            throw new HttpNotFoundException($request, 'Issue not found');
        }

        return $this->response($response, $resp);
    }

    public function join(Request $request, Response $resp, array $args)
    {
        /** 
        * If issue not exists generate a new one.
        * Must receive a payload with the intended name. ie: {"name": "florencia"}
        * Feel free to use a session or token to keep identified the user in subsequent requests.
        */

        $id = $args['id'];
        $params = $request->getParsedBody();

        if (empty($params['name'])) {
            throw new HttpBadRequestException($request, 'The name field is mandatory');
        }

        $issue_exists = $this->db->redis->exists($id);
        $member_exists = false;

        $member = [
            'name' => $params['name'],
            'status' => 'waiting',
            'value' => null
        ];
        if ($issue_exists) {
            $data = $this->db->redis->hmget($id, ['members']);
            $members = json_decode($data['members'], true);

            foreach($members as $existing_member) {
                if ($existing_member['name'] == $member['name']) {
                    $member_exists = true;
                }
            }
        }
        if (!$member_exists) {
            $members[] = $member;
        } else {
            return $this->response(['message' => $member['name'] . ' already joined the issue #' . $id], $resp);
        }
        $this->db->redis->hmset($id, ['status' => 'voting', 'members' => json_encode($members)]);

        return $this->response(['message' => $member['name'] . ' joined the issue #' . $id . ' succesfully'], $resp);
    }

    public function vote(Request $request, Response $resp, array $args)
    {
        /**
        * Reject votes when status of {:issue} is not voting.
        * Reject votes if user not joined {:issue}.
        * Reject votes if user already voted or passed. 
        */
    }
}