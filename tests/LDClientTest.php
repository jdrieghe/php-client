<?php
namespace LaunchDarkly\Tests;

use InvalidArgumentException;
use LaunchDarkly\FeatureRequester;
use LaunchDarkly\LDClient;
use LaunchDarkly\LDUser;
use LaunchDarkly\LDUserBuilder;


class LDClientTest extends \PHPUnit_Framework_TestCase {

    public function testDefaultCtor() {
        new LDClient("BOGUS_SDK_KEY");
    }

    public function testToggleDefault() {
        MockFeatureRequester::$val = null;
        $client = new LDClient("someKey", array(
            'feature_requester_class' => MockFeatureRequester::class,
            'events' => false
            ));

        $builder = new LDUserBuilder(3);
        $user = $builder->build();
        $this->assertEquals('argdef', $client->variation('foo', $user, 'argdef'));
    }

    public function testToggleFromArray() {
        MockFeatureRequester::$val = null;
        $client = new LDClient("someKey", array(
            'feature_requester_class' => MockFeatureRequester::class,
            'events' => false,
            'defaults' => array('foo' => 'fromarray')
        ));

        $builder = new LDUserBuilder(3);
        $user = $builder->build();
        $this->assertEquals('fromarray', $client->variation('foo', $user, 'argdef'));
    }

    public function testToggleEvent() {
        MockFeatureRequester::$val = null;
        $client = new LDClient("someKey", array(
            'feature_requester_class' => MockFeatureRequester::class,
            'events' => true
        ));

        $builder = new LDUserBuilder(3);
        $user = $builder->build();
        $client->variation('foo', $user, 'argdef');
        $proc = getPrivateField($client, '_eventProcessor');
        $queue = getPrivateField($proc, '_queue');
        $this->assertEquals(1, sizeof($queue));
    }

    public function testOnlyValidFeatureRequester() {
        $this->setExpectedException(InvalidArgumentException::class);
        new LDClient("BOGUS_SDK_KEY", ['feature_requester_class' => \stdClass::class]);
    }

    public function testSecureModeHash() {
        $client = new LDClient("secret", ['offline' => true]);
        $user = new LDUser("Message");
        $this->assertEquals("aa747c502a898200f9e4fa21bac68136f886a0e27aec70ba06daf2e2a5cb5597",  $client->secureModeHash($user));
    }
}


function getPrivateField(&$object, $fieldName)
{
    $reflection = new \ReflectionClass(get_class($object));
    $field = $reflection->getProperty($fieldName);
    $field->setAccessible(true);

    return $field->getValue($object);
}


class MockFeatureRequester implements FeatureRequester {
    public static $val = null;
    function __construct($baseurl, $key, $options) {
    }
    public function get($key) {
        return self::$val;
    }

    public function getAll() {
        return null;
    }
}
