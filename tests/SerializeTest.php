<?php

use PHPUnit\Framework\TestCase;
use Toplan\PhpSms\Agents\Agent;
use Toplan\PhpSms\Sms;

class SerializeTest extends TestCase
{
    protected static $sms;

    public static function setUpBeforeClass()
    {
        Sms::queue(false);
        Sms::cleanScheme();
        Sms::cleanConfig();
        Sms::scheme('TestAgent', [
            '100 backup',
            'sendContentSms' => function ($agent) {
                $agent->result(Agent::SUCCESS, true);
                $agent->result(Agent::INFO, 'some_info');
            },
        ]);
        Sms::beforeSend(function () {
            print_r('[_before_send_]');
        }, true);
        Sms::afterSend(function () {
            print_r('[_after_send_]');
        }, true);
        self::$sms = Sms::make()->to('18280354...')->content('content...');
    }

    public function testSerialize()
    {
        Sms::afterAgentSend(function ($task, $data) {
            $this->assertEquals('TestAgent', $data['driver']);
            $this->assertEquals('some_info', $data['result']['info']);
        });

        $serialized = serialize(self::$sms);

        $originalScheme = Sms::scheme();

        Sms::cleanScheme();
        $this->assertEmpty(Sms::scheme());

        $sms = unserialize($serialized);

        Sms::scheme($originalScheme);

        $this->assertArrayHasKey('TestAgent', Sms::scheme());
        $this->expectOutputString('[_before_send_][_after_send_]');
        $sms->send();
    }
}
