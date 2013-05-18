<?php

require dirname(__FILE__) . '/' . 'vendor' . '/' . 'autoload.php';

/**
 * Class Archive
 *
 *
 */
class Archive {

    /**
     * @var integer
     */
    protected $_currentPage = 0;

    /**
     * @param array $reader
     * @param array $attributes
     * @param array $userData
     */
    public function callbackPage($reader, $attributes, $userData = array()) {
        $requreTitle = $userData['needle'];
        $currentTitle = array_key_exists('mediawiki.page.title', $attributes) ? $attributes['mediawiki.page.title'] : '';
        if ($currentTitle === $requreTitle) {
            var_dump($attributes);
        }
        $this->_currentPage ++;

        // logging
        echo sprintf("[%08d] %s\r", $this->_currentPage, $currentTitle);
    }

    /**
     * Search article in MediaWiki dump
     *
     * @param string $name
     * @param string $needle
     * @return void
     */
    public function search($name, $needle) {
        $wikiReader = new \Wiki\Dump\Reader();
        $wikiReader->setUserData(array('needle' => $needle));
        $wikiReader->registerHook('afterPage', array($this, 'callbackPage'));
        $wikiReader->process($name);
    }

}

// Search some element
$archive = new Archive();
$searchTitle = 'Список глав государств в 497 году';
$archive->search('ruwiki-latest-pages-articles.xml', $searchTitle);
