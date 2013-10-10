<?php
namespace regenix\mvc\http;

use regenix\lang\ArrayTyped;
use regenix\lang\CoreException;
use regenix\lang\Singleton;
use regenix\lang\StrictObject;
use regenix\lang\String;
use regenix\mvc\route\RouteInjectable;

class RequestBody extends StrictObject
    implements Singleton, RouteInjectable {

    const type = __CLASS__;

    private $data = null;

    public function __construct($data = null){
        $this->data = $data;
    }

    protected function getData(){
        if ($this->data)
            return $this->data;

        return $this->data = file_get_contents('php://input');
    }

    /**
     * @param $name
     * @return UploadFile
     */
    public function getFile($name){
        $meta = $_FILES[$name];

        if ($meta){
            return new UploadFile($name, $meta);
        } else
            return null;
    }

    /**
     * @param string $prefix
     * @return array
     * @return UploadFile[]
     */
    public function getFiles($prefix){
        $meta = $_FILES[$prefix];
        if (!is_array($meta))
            $meta = array($meta);

        $result = array();
        foreach($meta as $el){
            $result[] = new UploadFile($prefix, $el);
        }
        return $result;
    }

    /**
     * @return UploadFile[string]
     */
    public function getAllFiles(){
        $result = array();
        foreach($_FILES as $name => $meta){
            if (is_array($meta)){
                foreach($result as $el){
                    $result[$name][] = new UploadFile($name, $el);
                }
            } else
                $result[$name] = new UploadFile($name, $meta);
        }
        return $result;
    }

    /**
     * parse data as json
     * @return array
     */
    public function asJson(){
        $json = json_decode($this->getData(), true);
        return $json;
    }

    /**
     * parse data as query string
     * @return ArrayTyped
     */
    public function asQuery(){
        return new ArrayTyped($_POST);
    }

    /**
     * @return array
     */
    public function asArray(){
        return $_POST;
    }

    /**
     * get data as string
     * @return string
     */
    public function asString(){
        return (string)$this->getData();
    }

    /**
     * @param $object
     * @param string $prefix
     * @param array $fields
     * @param string $method - query or json
     * @throws \regenix\lang\CoreException
     * @throws \InvalidArgumentException
     * @return object
     */
    public function appendTo(&$object, $fields = array(), $prefix = '', $method = 'query'){
        $data = null;
        switch($method){
            case 'query': $data = $this->asQuery(); break;
            case 'json' : $data = $this->asJson(); break;
            default: {
                throw new CoreException('Unknown method `%s` for appendTo', $method);
            }
        }

        if (is_object($object)) {
            $class = new \ReflectionClass($object);
            foreach($data as $name => $value){
                if ($fields && !in_array($name, $fields, true)) continue;
                if ($prefix && !String::startsWith($name, $prefix)) continue;

                $property = $prefix ? substr($name, strlen($prefix)) : $name;
                if ($class->hasProperty($property)){
                    $prop = $class->getProperty($property);
                    if ($prop->isPublic() && !$prop->isStatic()){
                        $prop->setValue($object, $value);
                    }
                }
            }
        } else
            throw new \InvalidArgumentException('Argument 1 must be object');

        return $object;
    }
}