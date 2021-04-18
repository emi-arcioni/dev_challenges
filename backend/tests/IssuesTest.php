<?php
declare(strict_types=1);

namespace Tests;

use Tests\Traits\AppTestTrait;
use PHPUnit\Framework\TestCase;

class IssuesTest extends TestCase
{
    use AppTestTrait;

    public function testIssueResponsesAnArray()
    {
        $request = $this->createRequest('GET', '/issues');
        $response = $this->app->handle($request);

        $body = json_decode((string)$response->getBody(), true);

        $this->assertTrue(is_array($body));
    }

    public function testUserCanJoinAIssue()
    {
        $this->redis->flushall();

        $request = $this->createJsonRequest('POST', '/issues/1/join', ['name' => 'Emilio']);
        $response = $this->app->handle($request);

        $body = json_decode((string)$response->getBody(), true);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Emilio joined the issue #1 successfully', $body['message']);
    }
    
    public function testUserCantJoinAgainTheSameIssue()
    {
        $request = $this->createJsonRequest('POST', '/issues/1/join', ['name' => 'Emilio']);
        $response = $this->app->handle($request);
        
        $body = json_decode((string)$response->getBody(), true);
        
        $this->assertEquals('Emilio already joined the issue #1', $body['message']);
    }
    
    public function testUserCanVote()
    {
        $request = $this->createJsonRequest('POST', '/issues/1/vote', ['name' => 'Emilio', 'value' => 10]);
        $response = $this->app->handle($request);
        
        $body = json_decode((string)$response->getBody(), true);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('The user with name Emilio voted', $body['message']);
    }

    public function testIssueDataStructure()
    {
        $request = $this->createRequest('GET', '/issues/1');
        $response = $this->app->handle($request);

        $expected = [
            'id' => 1,
            'status' => 'reveal',
            'members' => [
                [
                    'name' => 'Emilio',
                    'status' => 'voted',
                    'value' => 10
                ]
            ],
            'avg' => 10
        ];

        $this->assertJsonData($expected, $response);
    }
}