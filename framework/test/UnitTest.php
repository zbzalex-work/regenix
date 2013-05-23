<?php
namespace framework\test;

use framework\exceptions\StrictObject;
use framework\exceptions\TypeException;
use framework\lang\ClassLoader;

/**
 * Class UnitTest
 * @package framework\test
 */
abstract class UnitTest extends StrictObject {

    const type = __CLASS__;

    /** @var UnitTest[] */
    public static $tested = array();

    /** @var array */
    protected $requires = array();

    /** @var \ReflectionMethod */
    protected $currentMethod = null;
    private $results = array();

    protected function onBefore(){}
    protected function onAfter(){}
    protected function onException(\Exception $e){}

    protected function onGlobalBefore(){}
    protected function onGlobalAfter(){}

    protected function assertWrite($result, $message = ''){
        $trace = debug_backtrace();
        $trace = $trace[1];
        $this->results[$this->currentMethod->getName()][] = array(
            'meta'   => $this->currentMethod,
            'method' => $trace['function'],
            'line' => $trace['line'],
            'file' => $trace['file'],
            'result' => (boolean)$result,
            'message' => $message
        );
    }

    protected function required($unitTestClass, $needOk = false){
        $this->requires[$unitTestClass] = array('needOk' => $needOk);
    }

    public function getRequires(){
        return $this->requires;
    }

    protected function requiredOk($unitTestClass){
        $this->required($unitTestClass, true);
    }

    /**
     * @param string $class
     * @param callable $callback
     * @param array $args
     * @return $this
     * @throws \framework\exceptions\TypeException
     */
    protected function assertException($class, $callback, array $args = array(), $message = ''){
        if (!is_callable($callback))
            throw new TypeException('$callback', 'callable');

        ClassLoader::load($class);
        $meta = new \ReflectionClass($class);
        if (!$meta->isSubclassOf("\\Exception") && $meta->getName() !== '\\Exception')
            throw new TypeException('$class', 'Exception class');

        try {
            call_user_func_array($callback, $args);
        } catch (\Exception $e){
            if ($meta->isSubclassOf($class) || $meta->getName() === $class){
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
     * @throws \framework\exceptions\TypeException
     * @throws \Exception
     */
    protected function assertNotException($class, $callback, array $args = array(), $message = ''){
        if (!is_callable($callback))
            throw new TypeException('$callback', 'callable');

        ClassLoader::load($class);
        $meta = new \ReflectionClass($class);
        if (!$meta->isSubclassOf("\\Exception") && $meta->getName() !== '\\Exception')
            throw new TypeException('$class', 'Exception class');

        try {
            call_user_func_array($callback, $args);
        } catch (\Exception $e){
            if ($meta->isSubclassOf($class) || $meta->getName() === $class){
                $this->assertWrite(false, $message);
                return $this;
            }
            throw $e;
        }
        $this->assertWrite(true, $message);
        return $this;
    }

    /**
     * @param $with
     * @param $what
     * @param string $message
     * @return $this
     */
    protected function assertEqual($with, $what, $message = ''){
        $this->assertWrite($what == $with, $message);
        return $this;
    }

    /**
     * @param $with
     * @param $what
     * @param string $message
     * @return $this
     */
    protected function assertNotEqual($with, $what, $message = ''){
        $this->assertWrite($what != $with, $message);
        return $this;
    }

    /**
     * @param $with
     * @param $what
     * @param string $message
     * @return $this
     */
    protected function assertStrongEqual($with, $what, $message = ''){
        $this->assertWrite($what === $with, $message);
        return $this;
    }

    /**
     * @param $what
     * @param string $message
     * @return $this
     */
    protected function assert($what, $message = ''){
        $this->assertWrite($what === true, $message);
        return $this;
    }

    /**
     * @param $what
     * @param string $message
     * @return $this
     */
    protected function assertNot($what, $message = ''){
        $this->assertWrite($what === false, $message);
        return $this;
    }

    /**
     * @param $what
     * @param string $message
     * @return $this
     */
    protected function assertNull($what, $message = ''){
        $this->assertWrite($what === null, $message);
        return $this;
    }

    /**
     * @param $what
     * @param $object
     * @param string $message
     * @return $this
     */
    protected function assertType($what, $object, $message = ''){
        switch($what){
            case 'array': $this->assertWrite(is_array($object), $message); break;
            case 'string': $this->assertWrite(is_string($object), $message); break;
            case 'int':
            case 'integer': $this->assertWrite(is_int($object), $message); break;
            case 'double':
            case 'float': $this->assertWrite(is_double($object), $message); break;
            case 'callable': $this->assertWrite(is_callable($object), $message); break;
            case 'object': $this->assertWrite(is_object($object), $message); break;
            default: {
                $this->assertWrite(is_a($object, $what), $message);
            }
        }
        return $this;
    }

    /**
     * @param array $what
     * @param $array
     * @param string $message
     * @return $this
     */
    protected function assertArraySize($what, $array, $message = ''){
        $this->assertWrite(is_array($array) && (sizeof($array) === (int)$what), $message);
        return $this;
    }

    /**
     * @param $what
     * @param string $message
     * @return $this
     */
    protected function assertNotNull($what, $message = ''){
        $this->assertWrite($what, $message);
        return $this;
    }

    /**
     * @param $what
     * @param string $message
     * @return $this
     */
    protected function assertRequire($what, $message = ''){
        $this->assertWrite(!!($what), $message);
        return $this;
    }

    /**
     * @param $what
     * @param array $keys
     * @param string $message
     * @return $this
     */
    protected function assertIssetArray($what, array $keys, $message = ''){
        if (!is_array($what))
            $this->assertWrite(false, $message);

        foreach($keys as $key){
            if (!isset($what[$key])){
                $this->assertWrite(false, $message);
                return;
            }
        }
        $this->assertWrite(true, $message);
        return $this;
    }

    /**
     * @param $what
     * @param string $message
     * @return $this
     */
    protected function assertNotRequire($what, $message = ''){
        $this->assertWrite(!($what), $message);
        return $this;
    }

    public function startTesting(){
        self::$tested[get_class($this)] = false;

        foreach($this->requires as $require => $options){
            if ( !isset(self::$tested[$require]) ){
                /** @var $test UnitTest */
                $test = new $require();
                $test->startTesting();
                if ( $options['needOk'] && (!$test->isOk() || self::$tested[$require] === false) )
                    return null;
            } else {

                if ( self::$tested[$require] !== false &&
                    ($options['needOk'] && !self::$tested[$require]->isOk()) )
                    return null;
            }
        }

        $this->onGlobalBefore();
        try {
            $class   = new \ReflectionClass($this);
            $methods = $class->getMethods(\ReflectionMethod::IS_PUBLIC);
            foreach($methods as $method){
                $declClass = $method->getDeclaringClass();
                if ( $declClass->isAbstract() ) continue;
                if (in_array(strtolower($method->getName()),
                    array('onglobalbefore', 'onglobalafter', 'onafter', 'onbefore', 'onexception'))) continue;

                $this->currentMethod = $method;
                $this->onBefore();
                try {
                    $method->invoke($this);
                } catch (\Exception $e){
                    $this->onException($e);
                }
                $this->onAfter();
            }
        } catch (\Exception $e){
            // todo .. ?
        }
        $this->onGlobalAfter();
        return self::$tested[ get_class($this) ] = $this;
    }

    public function getResult(){
        return $this->results;
    }

    public function isOk(){
        foreach($this->results as $method => $results){
            foreach($results as $result){
                if ( !$result['result'] )
                    return false;
            }
        }
        return true;
    }
}