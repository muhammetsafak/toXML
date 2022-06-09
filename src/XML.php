<?php
/**
 * XML.php
 *
 * This file is part of toXML.
 *
 * @author     Muhammet ŞAFAK <info@muhammetsafak.com.tr>
 * @copyright  Copyright © 2022 Muhammet ŞAFAK
 * @license    ./LICENSE  MIT
 * @version    1.0
 * @link       https://www.muhammetsafak.com.tr
 */

namespace MuhammetSafak\ToXML;

use \DOMDocument;
use \InvalidArgumentException;
use \RuntimeException;
use function ucfirst;
use function method_exists;
use function in_array;
use function is_bool;
use function is_array;
use function is_string;
use function is_numeric;
use function key;
use function count;
use function array_keys;
use function preg_match;
use function trim;
use function array_key_exists;

final class XML
{

    /** @var DOMDocument */
    protected $dom = null;

    /** @var null|string */
    protected $xml = null;

    /** @var null|array */
    protected $array = null;

    /** @var string */
    protected $encode = 'UTF-8';

    /** @var string */
    protected $version = '1.0';

    /** @var string  */
    protected $attributesKey = '@attibutes';

    /** @var string  */
    protected $cdataKey = '@cdata';

    /** @var string  */
    protected $valueKey = '@value';

    /** @var bool */
    protected $useNamespaces = false;

    /** @var null|string */
    protected $rootNodeName = null;

    /** @var array */
    protected $namespaces = array();

    public function __construct($config = array())
    {
        if(!empty($config)){
            $this->isArray($config);
            foreach ($config as $key => $value) {
                $method = 'set' . ucfirst($key);
                if(!method_exists($this, $method)){
                    continue;
                }
                $this->{$method}($value);
            }
        }
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->toXML();
    }

    protected function init()
    {
        $this->dom = null;
        $this->array = null;
        $this->xml = null;
        $this->namespaces = array();
        return $this;
    }

    /**
     * @param $encode
     * @return self
     */
    public function setEncode($encode = 'UTF-8')
    {
        $this->isString($encode, 'The argument to the setEncode() method must be string.');
        $this->encode = $encode;

        return $this;
    }

    /**
     * @param string $version
     * @return self
     */
    public function setVersion($version = '1.0')
    {
        if(!in_array($version, ['1.0', '1.1'], true)) {
            throw new InvalidArgumentException('The XML version can be 1.0 or 1.1.');
        }
        $this->version = $version;
        return $this;
    }

    /**
     * @param string $rootName
     * @return $this
     */
    public function setRootNodeName($rootName)
    {
        $this->isString($rootName, 'The argument to the setRootNodeName() method must be string.');
        $this->rootNodeName = $rootName;
        return $this;
    }

    /**
     * @param string $attributesKey
     * @return $this
     */
    public function setAttributesKey($attributesKey = '@attibutes')
    {
        $this->isString($attributesKey, 'The argument to the setAttributesKey() method must be string.');
        $this->attributesKey = $attributesKey;

        return $this;
    }

    /**
     * @param string $cdataKey
     * @return $this
     */
    public function setCdataKey($cdataKey = '@cdata')
    {
        $this->isString($cdataKey, 'The argument to the setCdataKey() method must be string.');
        $this->cdataKey = $cdataKey;

        return $this;
    }

    /**
     * @param string $valueKey
     * @return $this
     */
    public function setValueKey($valueKey = '@value')
    {
        $this->isString($valueKey, 'The argument to the setValueKey() method must be string.');
        $this->valueKey = '@value';

        return $this;
    }

    /**
     * @param bool $useNamespaces
     * @return $this
     */
    public function setUseNamespaces($useNamespaces = false)
    {
        if(is_bool($useNamespaces)){
            throw new InvalidArgumentException('The argument to the setUseNamespaces() method must be boolean.');
        }
        $this->useNamespaces = $useNamespaces;

        return $this;
    }

    /**
     * @param array $array
     * @return self
     */
    public function withArray($array)
    {
        $this->isArray($array, 'The data to be converted to XML must be an array.');
        $this->init();
        $this->array = $array;

        return $this;
    }

    /**
     * @param string $xmlString
     * @return self
     */
    public function withXML($xmlString)
    {
        $this->isString($xmlString, 'XML must be given as a string.');
        $this->init();
        $this->xml = $xmlString;

        return $this;
    }

    /**
     * @return array|false
     */
    public function toArray()
    {
        if(is_array($this->array)){
            return $this->array;
        }
        if(empty($this->xml)){
            return false;
        }

        return $this->array = $this->buildArray($this->xml);
    }

    /**
     * @return false|string
     */
    public function toXML()
    {
        if(!empty($this->xml) && is_string($this->xml)){
            return $this->xml;
        }
        if(empty($this->array)){
            return false;
        }
        if(empty($this->rootNodeName)) {
            $this->rootNodeName = 'root';
        }

        return $this->xml = $this->buildXML($this->array)->saveXML();
    }


    /**
     * @param mixed $data
     * @throws InvalidArgumentException
     * @return true
     */
    private function isArray($data, $msg = null)
    {
        if(!is_array($data)){
            if($msg === null){
                $msg = 'It must be a array.';
            }
            throw new InvalidArgumentException($msg);
        }

        return true;
    }

    /**
     * @param mixed $data
     * @throws InvalidArgumentException
     * @return true
     */
    private function isString($data, $msg = null)
    {
        if(!is_string($data)){
            if($msg === null){
                $msg = 'It must be a string.';
            }
            throw new InvalidArgumentException($msg);
        }

        return true;
    }

    /**
     * @return DOMDocument
     */
    private function createDomDocument()
    {
        return new DOMDocument($this->version, $this->encode);
    }

    /**
     * @return DOMDocument
     */
    private function getDom()
    {
        if(empty($this->dom)){
            $this->dom = $this->createDomDocument();
        }

        return $this->dom;
    }

    /**
     * @param $array
     * @return DOMDocument
     */
    private function &buildXML($array)
    {
        if(!empty($this->rootNodeName)){
            $rootNodeName = $this->rootNodeName;
        }else{
            if(is_array($array) && count($array) == 1){
                $rootNodeName = array_keys($array)[0];
                $array = $array[$rootNodeName];
            }
        }
        $this->dom = $this->getDom();
        $this->dom->appendChild($this->convertXML($rootNodeName, $array));

        return $this->dom;
    }

    /**
     * @param $nodeName
     * @param $array
     * @return \DOMElement|false
     * @throws \DOMException
     */
    private function &convertXML($nodeName, $array = array())
    {
        $xml = $this->getDom();
        $node = $xml->createElement($nodeName);

        if(is_array($array)){
            if(isset($array[$this->attributesKey])){
                foreach ($array[$this->attributesKey] as $key => $value) {
                    if(!$this->isValidTagName($key)){
                        throw new RuntimeException('Illegal character in attribute name. attribute: ' . $key . ' in node: ' . $nodeName);
                    }
                    $node->setAttribute($key, $this->bool2str($value));
                }
                unset($array[$this->attributesKey]);
            }
            if(isset($array[$this->valueKey])){
                $node->appendChild($xml->createTextNode($this->bool2str($array[$this->valueKey])));
                unset($array[$this->valueKey]);
                return $node;
            }elseif(isset($array[$this->cdataKey])){
                $node->appendChild($xml->createCDATASection($this->bool2str($array[$this->cdataKey])));
                unset($array[$this->cdataKey]);
                return $node;
            }
        }
        if(is_array($array)){
            foreach ($array as $key => $value) {
                if(!$this->isValidTagName($key)){
                    throw new RuntimeException('Illegal character in tag name. tag: ' . $key . ' in node: ' . $nodeName);
                }
                if(is_array($value) && is_numeric(key($value))){
                    foreach ($value as $k => $v) {
                        $node->appendChild($this->convertXML($key, $v));
                    }
                }else{
                    $node->appendChild($this->convertXML($key, $value));
                }
                unset($array[$key]);
            }
        }
        if(!is_array($array)){
            $node->appendChild($xml->createTextNode($this->bool2str($array)));
        }
        return $node;
    }

    /**
     * @param $tag
     * @return bool
     */
    private function isValidTagName($tag)
    {
        $pattern = '/^[a-z_]+[a-z0-9\:\-\.\_]*[^:]*$/i';
        return preg_match($pattern, $tag, $matches) && $matches[0] == $tag;
    }

    /**
     * @param $v
     * @return mixed|string
     */
    private function bool2str($v)
    {
        return is_bool($v) ? ($v === FALSE ? 'false' : 'true') : $v;
    }

    /**
     * @param $xml
     * @return array
     */
    private function &buildArray($xml)
    {
        $this->dom = $this->createDomDocument();
        $parsed = $this->dom->loadXML($xml);
        if(!$parsed){
            throw new RuntimeException('Error parsing the XML string.');
        }
        $docNodeName = $this->dom->documentElement->nodeName;
        $array[$docNodeName] = $this->convertArray($this->dom->documentElement);
        if(!empty($this->namespaces)){
            if(!isset($array[$docNodeName][$this->attributesKey])){
                $array[$docNodeName][$this->attributesKey] = array();
            }
            foreach ($this->namespaces as $uri => $prefix) {
                if($prefix){
                    $prefix = ':' . $prefix;
                }
                $array[$docNodeName][$this->attributesKey]['xmlns' . $prefix] = $uri;
            }
        }
        return $array;
    }

    /**
     * @param \DOMNode $node
     * @return array
     */
    private function &convertArray($node)
    {
        $output = array();
        $this->collateArrayNamespace($node);
        switch ($node->nodeType) {
            case XML_CDATA_SECTION_NODE:
                $output[$this->cdataKey] = trim($node->textContent);
                break;
            case XML_TEXT_NODE:
                $output = trim($node->textContent);
                break;
            case XML_ELEMENT_NODE:
                for ($i = 0, $m = $node->childNodes->length; $i < $m; ++$i) {
                    $child = $node->childNodes->item($i);
                    $v = $this->convertArray($child);
                    if(isset($child->tagName)){
                        $t = $child->nodeName;
                        if(!isset($output[$t])){
                            $output[$t] = array();
                        }
                        $output[$t][] = $v;
                    }else{
                        if($v !== ''){
                            $output = $v;
                        }
                    }
                }
                if(is_array($output)) {
                    foreach ($output as $t => $v) {
                        if(is_array($v) && count($v) == 1) {
                            $output[$t] = $v[0];
                        }
                    }
                    if(empty($output)){
                        $output = '';
                    }
                }
                if($node->attributes->length){
                    $a = array();
                    foreach ($node->attributes as $attributeName => $attributeNode) {
                        $attributeName = $attributeNode->nodeName;
                        $a[$attributeName] = (string)$attributeNode->value;
                        $this->collateArrayNamespace($attributeNode);
                    }
                    if(!is_array($output)){
                        $output = array($this->valueKey => $output);
                    }
                    $output[$this->attributesKey] = $a;
                }
                break;
        }
        return $output;
    }

    /**
     * @param \DOMNode $node
     * @return void
     */
    private function collateArrayNamespace($node)
    {
        if($this->useNamespaces && $node->namespaceURI && !array_key_exists($node->namespaceURI, $this->namespaces)){
            $this->namespaces[$node->namespaceURI] = $node->lookupPrefix($node->namespaceURI);
        }
    }

}