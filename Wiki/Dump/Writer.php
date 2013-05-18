<?php
namespace Wiki\Dump;

use Dendrocopos\DbCriteria;
use SmartWiki\Mapper\RevisionMapper;
use SmartWiki\Mapper\TextMapper;
use SmartWiki\Model\PageModel;
use SmartWiki\Model\RevisionModel;
use SmartWiki\Model\TextModel;
use SmartWiki\Helper\Xml;

/**
 * Class SmartWiki Dump Writer
 *
 * @author Vitold Sedyshev
 */
class Writer {

    /**
     * @param RevisionModel $rev
     * @return string
     */
    public function createRevision(RevisionModel $rev) {
        $result = '';
        $result .= Xml::openTag('revision') . PHP_EOL;
        $result .= Xml::tag('id', array(), $rev->rev_id) . PHP_EOL;
        $result .= Xml::tag('parentid', array(), $rev->rev_parent_id) . PHP_EOL;
        $result .= Xml::tag('timestamp', array(), $rev->rev_timestamp) . PHP_EOL;
        $result .= Xml::tag('comment', array(), $rev->rev_comment) . PHP_EOL;

        $criteria = new DbCriteria();
        $criteria->addColumnCondition(array(
            'text_id' => $rev->rev_text_id
        ));
        $text = TextMapper::instance()->find($criteria);
        if ($text instanceof \SmartWiki\Model\TextModel) {
            $result .= $this->createText($text);
        }

        $result .= Xml::closeTag('revision') . PHP_EOL;
        return $result;
    }

    /**
     * Write page block
     *
     * @param PageModel $page
     * @return string
     */
    public function createPage(PageModel $page) {
        $result = '';

        $result .= Xml::openTag('page') . PHP_EOL;
        $result .= Xml::tag('title', array(), $page->page_title) . PHP_EOL;
        $result .= Xml::tag('ns', array(), $page->page_namespace) . PHP_EOL;
        $result .= Xml::tag('id', array(), $page->page_id) . PHP_EOL;

        $criteria = new DbCriteria();
        $criteria->addColumnCondition(array(
            'rev_id' => $page->page_latest
        ));
        $rev = RevisionMapper::instance()->find($criteria);
        if ($rev instanceof \SmartWiki\Model\RevisionModel) {
            $result .= $this->createRevision($rev);
        }

        $result .= Xml::closeTag('page') . PHP_EOL;

        return $result;
    }

    /**
     * @param TextModel $text
     * @return string
     */
    public function createText(TextModel $text)
    {
        $result = '';
        $result .= Xml::openTag('text');
        $result .= $text->text_text;
        $result .= Xml::closeTag('text');

        return $result;
    }

}
