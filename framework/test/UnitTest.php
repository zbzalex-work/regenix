<?php

namespace regenix\test;

use regenix\lang\CoreException;
use regenix\lang\StrictObject;
use regenix\exceptions\TypeException;

/**
 * Class UnitTest
 * @package regenix\test
 */
abstract class UnitTest extends StrictObject
{

    const type = __CLASS__;

    /** @var UnitTest[] */
    public static $tested = array();

    /** @var array */
    protected $requires = array();

    /** @var \ReflectionMethod */
    protected $currentMethod = null;
    private $results = array();
    private $lastOk = true;
    private $checkRequired = false;

    protected function onBefore()
    {
    }

    protected function onAfter()
    {
    }

    protected function onException(\Exception $e)
    {
    }

    protected function onGlobalBefore()
    {
    }

    protected function onGlobalAfter()
    {
    }

    protected function assertWrite($result, $message = '')
    {
        $trace = debug_backtrace();
        $trace = $trace[1];
        $this->results[$this->currentMethod->getName()][] = array(
            'meta' => $this->currentMethod,
            'method' => $trace['function'],
            'line' => $trace['line'],
            'file' => $trace['file'],
            'result' => (boolean)$result,
            'message' => $message
        );
        $this->lastOk = $result;
        return $this;
    }

    protected function required($unitTestClass, $needOk = false)
    {
        $reflect = new \ReflectionClass($unitTestClass);
        if ($reflect->isAbstract())
            throw new CoreException('Required test class cannot be abstract: "%s" is abstract', $unitTestClass);

        $this->requires[$unitTestClass] = array('needOk' => $needOk);
    }

    protected function sleep($sec, $mlsec = 0)
    {
        $time = time();
        if (!time_nanosleep($sec, $mlsec * 1000000)) {
            if ($sec && $mlsec) {
                sleep($sec);
                usleep($mlsec * 1000000);
            } else if ($sec && !$mlsec) {
                sleep($sec);
            } else {
                usleep($mlsec * 1000000);
            }
        }
        return $time;
    }

    public function getRequires()
    {
        return $this->requires;
    }

    protected function requiredOk($unitTestClass)
    {
        $this->required($unitTestClass, true);
    }

    public function setCheckRequired($checkRequired)
    {
        $this->checkRequired = $checkRequired;
    }

    /**
     * @param string $class
     * @param callable $callback
     * @param array $args
     * @param string $message
     * @return $this
     * @throws \Exception
     * @throws \regenix\exceptions\TypeException
     */
    protected function assertException($class, $callback, array $args = array(), $message = '')
    {
        if (!is_callable($callback))
            throw new TypeException('$callback', 'callable');

        $meta = new \ReflectionClass($class);
        if (!$meta->isSubclassOf("\\Exception") && $meta->getName() !== '\\Exception')
            throw new TypeException('$class', 'Exception class');

        try {
            call_user_func_array($callback, $args);
        } catch (\Exception $e) {
            if (is_subclass_of($e, $class) || $meta->getName() === $class) {
                $this->assertWrite(true, $message);
                return $this;
            }
            throw $e;
        }
        $this->assertWrite(false, $message);
        return $this;
    }

    /**
     * @param $class
     * @param $callback
     * @param array $args
     * @param string $message
     * @return $this
     * @throws \regenix\exceptions\TypeException
     * @throws \Exception
     */
    protected function assertNotException($class, $callback, array $args = array(), $message = '')
    {
        if (!is_callable($callback))
            throw new TypeException('$callback', 'callable');

        $meta = new \ReflectionClass($class);
        if (!$meta->isSubclassOf("\\Exception") && $meta->getName() !== '\\Exception')
            throw new TypeException('$class', 'Exception class');

        try {
            call_user_func_array($callback, $args);
        } catch (\Exception $e) {
            if (is_subclass_of($e, $class) || $meta->getName() === $class) {
                $this->assertWrite(false, $message);
                return $this;
            }
            throw $e;
        }
        return $this->assertWrite(true, $message);
    }

    /**
     * @param $with
     * @param $what
     * @param string $message
     * @return $this
     */
    protected function assertEqual($with, $what, $message = '')
    {
        $message = $what != $with ? "$with != $what" : $message;

        return $this->assertWrite($what == $with, $message);
    }

    /**
     * @param $with
     * @param $what
     * @param string $message
     * @return $this
     */
    protected function assertNotEqual($with, $what, $message = '')
    {
        return $this->assertWrite($what != $with, $message);
    }

    /**
     * @param $with
     * @param $what
     * @param string $message
     * @return $this
     */
    protected function assertStrongEqual($with, $what, $message = '')
    {
        return $this->assertWrite($what === $with, $message);
    }

    /**
     * @param $what
     * @param string $message
     * @return $this
     */
    protected function assert($what, $message = '')
    {
        return $this->assertWrite($what === true, $message);
    }

    /**
     * @param $what
     * @param string $message
     * @return $this
     */
    protected function assertNot($what, $message = '')
    {
        return $this->assertWrite($what === false, $message);
    }

    /**
     * @param $what
     * @param string $message
     * @return $this
     */
    protected function assertNull($what, $message = '')
    {
        return $this->assertWrite($what === null, $message);
    }

    /**
     * @param $what
     * @param $object
     * @param string $message
     * @return $this
     */
    protected function assertType($what, $object, $message = '')
    {
        switch (strtolower($what)) {
            case 'array':
                $this->assertWrite(is_array($object), $message);
                break;
            case 'string':
                $this->assertWrite(is_string($object), $message);
                break;
            case 'int':
            case 'integer':
                $this->assertWrite(is_int($object), $message);
                break;
            case 'double':
            case 'float':
                $this->assertWrite(is_double($object), $message);
                break;
            case 'callable':
                $this->assertWrite(is_callable($object), $message);
                break;
            case 'object':
                $this->assertWrite(is_object($object), $message);
                break;
            case 'null':
                $this->assertWrite(is_null($object), $message);
                break;
            case 'numeric':
                $this->assertWrite(is_numeric($object), $message);
                break;
            default:
            {
                $this->assertWrite(is_a($object, $what), $message);
            }
        }
        return $this;
    }

    /**
     * @param int $size
     * @param $array
     * @param string $message
     * @return $this
     */
    protected function assertArraySize($size, $array, $message = '')
    {
        return $this->assertWrite(is_array($array) && (sizeof($array) === (int)$size), $message);
    }

    /**
     * @param array $what
     * @param $array
     * @param bool $strong
     * @param string $message
     * @return $this
     */
    protected function assertArrayContains(array $what, $array, $strong = false, $message = '')
    {
        if (!is_array($array))
            return $this->assertWrite(false, $message);

        foreach ($what as $key => $value) {

            if ($strong) {
                if ($array[$key] !== $value)
                    return $this->assertWrite(false, $message);
            } else {
                if ($array[$key] != $value)
                    return $this->assertWrite(false, $message);
            }
        }
        $this->assertWrite(true, $message);
    }

    /**
     * @param int $size
     * @param $string
     * @param string $message
     * @return $this
     */
    protected function assertStringLength($size, $string, $message = '')
    {
        return $this->assertWrite(is_string($string) && (strlen($string) === (int)$size), $message);
    }

    /**
     * @param string $pattern
     * @param string $string
     * @param string $message
     * @return $this
     */
    protected function assertPattern($pattern, $string, $message = '')
    {
        return $this->assertWrite(is_string($string) && preg_match($pattern, $string), $message);
    }

    /**
     * @param string $pattern
     * @param string $string
     * @param string $message
     * @return $this
     */
    protected function assertNotPattern($pattern, $string, $message = '')
    {
        return $this->assertWrite(!is_string($string) || !preg_match($pattern, $string), $message);
    }

    /**
     * @param $what
     * @param string $message
     * @return $this
     */
    protected function assertNotNull($what, $message = '')
    {
        return $this->assertWrite($what !== null, $message);
    }

    /**
     * @param $what
     * @param string $message
     * @return $this
     */
    protected function assertRequire($what, $message = '')
    {
        return $this->assertWrite(!!($what), $message);
    }

    /**
     * @param $what
     * @param array $keys
     * @param string $message
     * @return $this
     */
    protected function assertIssetArray($what, array $keys, $message = '')
    {
        if (!is_array($what))
            return $this->assertWrite(false, $message);

        foreach ($keys as $key) {
            if (!isset($what[$key]))
                return $this->assertWrite(false, $message);
        }
        return $this->assertWrite(true, $message);
    }

    /**
     * @param array|mixed $what
     * @param array $keys
     * @param string $message
     * @return $this
     */
    protected function assertKeyExists($what, array $keys, $message = '')
    {
        if (!is_array($what))
            return $this->assertWrite(false, $message);

        foreach ($keys as $key) {
            if (!array_key_exists($key, $what))
                return $this->assertWrite(false, $message);
        }

        return $this->assertWrite(true, $message);
    }

    /**
     * @param $what
     * @param string $message
     * @return $this
     */
    protected function assertNotRequire($what, $message = '')
    {
        $this->assertWrite(!($what), $message);
        return $this;
    }

    public function startTesting()
    {
        self::$tested[get_class($this)] = false;

        foreach ($this->requires as $require => $options) {
            if (!isset(self::$tested[$require])) {
                /** @var $test UnitTest */
                if ($this->checkRequired) {
                    $test = new $require();
                    $test->startTesting();
                    if ($options['needOk'] && (!$test->isOk() || self::$tested[$require] === false))
                        return null;
                }
            } else {

                if (self::$tested[$require] !== false &&
                    ($options['needOk'] && !self::$tested[$require]->isOk()))
                    return null;
            }
        }

        $this->onGlobalBefore();
        try {
            $class = new \ReflectionClass($this);
            $methods = $class->getMethods(\ReflectionMethod::IS_PUBLIC);
            foreach ($methods as $method) {
                $declClass = $method->getDeclaringClass();
                if ($declClass->isAbstract()) continue;
                if (in_array(strtolower($method->getName()),
                    array('onglobalbefore', 'onglobalafter', 'onafter', 'onbefore', 'onexception'))) continue;

                $this->currentMethod = $method;
                $this->onBefore();
                try {
                    $method->invoke($this);
                } catch (\Exception $e) {
                    $this->onException($e);
                    $this->assertWrite(false, 'Exception: ' . $e->getMessage() . ' at line ' . $e->getLine());
                }
                $this->onAfter();
            }
        } catch (\Exception $e) {
            // todo .. ?
        }
        $this->onGlobalAfter();
        return self::$tested[get_class($this)] = $this;
    }

    public function getResult()
    {
        return $this->results;
    }

    public function isLastOk()
    {
        return $this->lastOk;
    }

    public function isOk()
    {
        foreach ($this->results as $method => $results) {
            foreach ($results as $result) {
                if (!$result['result'])
                    return false;
            }
        }
        return true;
    }
}