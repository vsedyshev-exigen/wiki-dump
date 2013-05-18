<?php
namespace Wiki\Dump;

/**
 * Class Wiki Dump Reader
 *
 * @package Wiki\Dump
 */
class Reader {

    /**
     * @var array
     */
    public $_hooks = array();

    /**
     * @var mixed
     */
    protected $_userData;

    /**
     * @var callable
     */
    protected $_page;

    /**
     * @var resource
     */
    protected $_parser;

    /**
     * Buffer block size (only to performance tuning)
     *
     * @var int
     */
    protected $_size = 8192;

    /**
     * Charset
     *
     * @var string
     */
    protected $_charset = 'utf-8';

    /**
     * @var array
     */
    protected $_path = array();

    /**
     * @var array
     */
    protected $_attributes;

    public function __construct() {

        // Create an XML parser
        $this->_parser = xml_parser_create( $this->_charset );

        // Specify element handler
        xml_set_element_handler($this->_parser, array($this, 'openTag'), array($this, 'closeTag'));

        // Specify data handler
        xml_set_character_data_handler($this->_parser, array($this, 'cdata') );

        // Reset current page object
        $this->reset();
    }

    public function __destruct() {
        xml_parser_free($this->_parser);
    }

    /**
     * Set charset
     *
     * @param $charset
     * @return void
     */
    public function setCharset($charset) {
        $this->_charset = $charset;
    }

    /**
     * @param array $userData
     */
    public function setUserData($userData = array()) {
        $this->_userData = $userData;
    }

    /**
     * @param $callback
     */
    public function setPage($callback)
    {
        $this->_page = $callback;
    }

    /**
     * @param string $element_name
     */
    protected function openTagPath($element_name) {
        array_push( $this->_path, $element_name );
    }

    /**
     * @param string $element_name
     * @throws \Exception
     */
    protected function closeTagPath($element_name) {
        $tag = array_pop( $this->_path );
        if ( $tag !== $element_name ) {
            throw new \Exception('Wrong XML scheme.');
        }
    }

    /**
     * Open XML element
     *
     * @param $parser
     * @param $element_name
     * @param $element_attrs
     */
    protected function openTag($parser, $element_name, $element_attrs) {
        $element_name = mb_strtolower( $element_name, $this->_charset );
        $this->openTagPath($element_name);

        // Вызываем различные вызовы
        if ( $element_name == 'page' ) {
            $this->reset();
            $this->runHooks('beforePage');
        }
    }

    /**
     * Функция выхода из XML элемента
     *
     * @param $parser
     * @param $element_name
     * @throws \Exception
     * @return void
     */
    protected function closeTag($parser, $element_name) {
        $element_name = mb_strtolower( $element_name, $this->_charset );
        $this->closeTagPath($element_name);

        // Вызываем различные обратные вызовы
        if ( $element_name == 'page' ) {
            $this->normalize();
            $this->runHooks('afterPage');
        }
    }

    /**
     * Get current path
     *
     * @param string $delimeter
     * @return string
     */
    protected function getCurrentPath($delimeter = '.') {
        $result = join($delimeter, $this->_path );
        return $result;
    }

    /**
     * Function to use when finding character data
     *
     * @param $parser
     * @param $data
     */
    protected function cdata($parser, $data) {
        $attribute = $this->getCurrentPath();
        if (array_key_exists($attribute, $this->_attributes)) {
            $this->_attributes[$attribute] = $this->_attributes[$attribute] . $data;
        } else {
            $this->_attributes[$attribute] = $data;
        }
    }

    /**
     * Сбрасываем все параметры для записи
     *
     * @return void
     */
    protected function reset() {
        $this->_attributes = array();
    }

    /**
     * Производит нормализацию параметров
     *
     * @return void
     */
    protected function normalize() {
        foreach($this->_attributes as $attribute => $value ) {
            $this->_attributes[$attribute] = trim($value);
        }
    }

    /**
     * Process
     *
     * @param string $name
     * @throws \Exception
     * @return bool
     */
    public function process($name) {
        $handle = fopen($name, 'rb');
        if (!$handle) {
            return false;
        }

        // Main loop 
        do {
            $data = fread($handle, $this->_size);
            $res = xml_parse($this->_parser, $data, feof($handle));
            if ( $res === 0 ) {
                throw new \Exception(sprintf("XML Error: %s at line %d", xml_error_string(xml_get_error_code($this->_parser)), xml_get_current_line_number($this->_parser)));
            }
        } while ( !feof($handle) );

        fclose($handle);

        return true;
    }

    /**
     * Вызываем все указанные навешанные обработчики
     *
     * @param string $name
     * @throws \Exception
     * @return void
     */
    public function runHooks($name) {
        //echo 'Hook: ' . $name . PHP_EOL;
        if (array_key_exists($name, $this->_hooks)) {
            $hooks = $this->_hooks[$name];
            foreach($hooks as $hook) {
                if (is_callable($hook)) {
                    call_user_func_array($hook, array($this, $this->_attributes, $this->_userData));
                } else {
                    throw new \Exception('Can not call callback on "' . $name . '" event.');
                }
            }
        }

    }

    /**
     * Добавляем обработчик события
     *
     * @param string $name
     * @param callback $callback
     */
    public function registerHook($name, $callback) {
        $this->_hooks[$name][] = $callback;
    }

}