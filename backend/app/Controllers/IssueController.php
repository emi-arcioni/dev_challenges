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
    public function index(Request $request, Response $resp, array $args)
    {
        $keys = $this->db->redis->keys('*');
        sort($keys);

        $response = [];
        foreach($keys as $id) {
            $response[] = $this->get($request, $resp, ['id' => $id, 'raw' => true]);
        }

        return $this->response($response, $resp);
    }

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

                $response = [
                    'id' => (int)$id,
                    'status' => $data['status'],
                    'members' => $members
                ];
            } else {
                $members = json_decode($data['members'], true);

                $response = [
                    'id' => (int)$id,
                    'status' => $data['status'],
                    'members' => $members,
                    'avg' => $this->average(array_filter(array_map(function($member) {
                        return $member['value'];
                    }, $members)))
                ];
            }
        } else {
            throw new HttpNotFoundException($request, 'Issue not found');
        }

        if (empty($args['raw'])) {
            return $this->response($response, $resp);
        } else {
            return $response;
        }
    }

    public function join(Request $request, Response $resp, array $args)
    {
        /** 
        * TODO: Feel free to use a session or token to keep identified the user in subsequent requests.
        */

        $id = $args['id'];
        $params = $request->getParsedBody();

        // Must receive a payload with the intended name. ie: {"name": "florencia"}
        if (empty($params['name'])) {
            throw new HttpBadRequestException($request, 'The name field is mandatory');
        }

        $member = [
            'name' => $params['name'],
            'status' => 'waiting',
            'value' => null
        ];
        $member_exists = false;

        if ($this->issueExist($id)) {
            $data = $this->db->redis->hgetall($id);
            if ($data['status'] != 'voting') {
                throw new HttpBadRequestException($request, 'The issue status is not "voting"');
            }
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
            throw new HttpBadRequestException($request, $member['name'] . ' already joined the issue #' . $id);
        }
        $this->db->redis->hmset($id, ['status' => 'voting', 'members' => json_encode($members)]);

        return $this->response(['message' => $member['name'] . ' joined the issue #' . $id . ' successfully'], $resp);
    }

    public function vote(Request $request, Response $resp, array $args)
    {
        $id = $args['id'];
        $params = $request->getParsedBody();

        if (empty($params['name'])) {
            throw new HttpBadRequestException($request, 'The name field is mandatory');
        }
        if (empty($params['value'])) {
            throw new HttpBadRequestException($request, 'The value field is mandatory');
        }
        if (!$this->issueExist($id)) {
            throw new HttpNotFoundException($request, 'The issue doesn\'t exist');
        }

        $data = $this->db->redis->hgetall($id);

        // Reject votes when status of {:issue} is not voting.
        if ($data['status'] != 'voting') {
            throw new HttpBadRequestException($request, 'The issue status is not "voting"');
        }
        if (empty($data['members'])) {
            throw new HttpBadRequestException($request, 'There are no members joined to this issue');
        }

        $member_exists = false;
        $members = json_decode($data['members'], true);
        
        foreach($members as $index => $member) {
            if ($member['name'] == $params['name']) {
                $member_exists = true;
                $member_status = $member['status'];
                break;
            }
        }

        // Reject votes if user not joined {:issue}.
        if (!$member_exists) {
            throw new HttpBadRequestException($request, 'The user with name ' . $params['name'] . ' didn\'t joined this issue');
        }

        // Reject votes if user already voted or passed. 
        if ($member_status == 'voted' || $member_status == 'passed') {
            throw new HttpBadRequestException($request, 'The user with name ' . $params['name'] . ' already voted or passed this issue');
        }

        if (is_numeric($params['value'])) {
            $member['value'] = $params['value'];
            $member['status'] = 'voted';
        } else {
            $member['status'] = 'passed';
        }

        $members[$index] = $member;
        $waiting = array_filter($members, function($member) {
            return $member['status'] == 'waiting';
        });
        if (count($waiting) == 0) {
            $status = 'reveal';
        } else {
            $status = 'voting';
        }
        $this->db->redis->hmset($id, ['status' => $status, 'members' => json_encode($members)]);

        return $this->response(['message' => 'The user with name ' . $params['name'] . ' ' . $member['status']], $resp);
    }

    private function issueExist($issue_id)
    {
        return $this->db->redis->exists($issue_id);
    }

    private function average($arr)
    {
        return array_sum($arr) / count($arr);
    }
}