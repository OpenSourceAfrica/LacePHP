<?php
namespace Weave\Plugins\ShoeAI\Agents;

class ShoeAIStatus
{
    public static function status(): void
    {
        $http = new HttpClient();
        $resp = $http->post('/status', []);
        echo $resp['body'], PHP_EOL;
    }
}