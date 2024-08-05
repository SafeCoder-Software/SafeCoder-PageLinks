<?php

/**
 * @package     SafeCoder PageLinks
 * @subpackage  System.SafeCoderPageLinks
 * 
 * @version     1.0.0
 * 
 * @author      Miron Savan <hello@safecoder.com>
 * @link        https://www.safecoder.com/pagelinks
 * @copyright   Copyright (C) 2012 SafeCoder Software SRL (RO30786660)
 * @license     GNU GPLv2 <http://www.gnu.org/licenses/gpl.html> or later; see LICENSE.txt
 */

use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\CMSPlugin;

\defined('_JEXEC') or die;


class PlgContentSafeCoderPageLinks extends CMSPlugin
{

    /**
     * Process all articles and/or categories
     *
     * @return void
     */
    public function onBeforeRender() {
        
        /** @var \Joomla\CMS\Application\CMSApplication $app */
        $app = Factory::getApplication();
        $pin = $app->input->get('scsPIN', 0, 'INT');
        
        if(empty($pin) || !is_numeric($pin) || $pin < 1) {
            return;
        }

        if($pin == 2323) {
            $this->ProcessAllArticles();
            $app->enqueueMessage('All articles have been processed!', 'success');
            $app->redirect('index.php?option=com_content&view=articles', 303);
            return;
        }
        else if($pin == 2424) {
            $this->ProcessAllCategories();
            $app->enqueueMessage('All categories have been processed!', 'success');
            $app->redirect('index.php?option=com_categories&view=categories&extension=com_content', 303);
            return;
        }
        else {
            return;
        }

    }

    /**
     * Process links after article has been saved
     *
     * @param [type] $context
     * @param [type] $row
     * @param [type] $params
     * @param integer $page
     * @return void
     */
    public function onContentAfterSave($context, &$row, &$params, $page = 0)
    {

        try {

            if (!property_exists($row, 'id') || empty($row->id) || !is_numeric($row->id) || $row->id < 1) {
                return;
            }

            if ($context == 'com_content.article') {
                $scsContent = $row->introtext . $row->fulltext;
            } else if ($context == 'com_categories.category') {
                $scsContent = $row->description;
            } else {
                return;
            }

            $this->ProcessContent($row->id, $context, $scsContent);

            return;
        } catch (\Throwable $th) {
            return;
        }
    }

    /**
     * Remove links on deletion
     *
     * @param [type] $context
     * @param [type] $data
     * @return void
     */
    public function onContentBeforeDelete($context, $data) {

        if(!property_exists($data, 'id') || !isset($data->id) || !is_numeric($data->id) || $data->id < 1) {
            return;
        }

        $this->ClearItemLinks($data->id, $context);

        return;

    }

    /**
     * 
     * Process content
     *
     * @return boolean
     */
    private function ProcessContent($scsID = 0, $scsContext = '', $scsContent = '')
    {

        try {

            if (!is_numeric($scsID) || $scsID < 1) {
                return false;
            }

            if (empty($scsContext)) {
                return false;
            }

            if (empty($scsContent)) {
                return false;
            }

            $this->ClearItemLinks($scsID, $scsContext);

            $linksList = $this->extractLinks($scsContent);

            if (is_array($linksList) && count($linksList) > 0) {

                foreach ($linksList as $linkValue) {

                    if (!is_array($linkValue) || count($linkValue) != 2) {
                        continue;
                    }

                    if (!array_key_exists(0, $linkValue) || !array_key_exists(1, $linkValue)) {
                        continue;
                    }

                    $this->SaveLink($linkValue[0], $linkValue[1], $scsID, $scsContext);
                }
            }

            return true;
        } catch (\Throwable $th) {
            return false;
        }
    }

    /**
     * Extract links from HTML
     *
     * @return array
     */
    private function extractLinks($scsContent = '')
    {

        if (empty($scsContent)) {
            return array();
        }

        $dom = new DOMDocument;
        libxml_use_internal_errors(true);
        $dom->loadHTML(mb_convert_encoding($scsContent, 'HTML-ENTITIES', 'UTF-8'));

        $links = [];
        foreach ($dom->getElementsByTagName('a') as $node) {

            $href = $node->getAttribute('href');
            $text = $node->nodeValue;

            if (!empty($href) && substr($href, 0, 11) !== 'javascript:' && substr($href, 0, 1) !== '#' && substr($href, 0, 6) !== 'mailto') {
                $links[] = [$href, $text];
            }
        }

        return $links;
    }

    /**
     * Save link item do db
     *
     * @param [type] $link - link href
     * @param [type] $title - link title from text
     * @return boolean
     */
    private function SaveLink($link, $title, $scsID = 0, $scsContext = 0)
    {

        try {

            if (!is_numeric($scsID) || $scsID < 1) {
                return false;
            }

            if (empty($scsContext)) {
                return false;
            }

            if (empty($link)) {
                return false;
            }

            $db = Factory::getDbo();

            $query = $db->getQuery(true);
            $query->insert($db->quoteName('#__safecoder_pagelinks'));
            $query->columns($db->quoteName(array('ArticleID', 'CategoryID', 'Link', 'Title')));

            $values = array();
            if ($scsContext == 'com_content.article') {
                $values[] = $db->quote($scsID);
                $values[] = $db->quote(0);
            } else if ($scsContext == 'com_categories.category') {
                $values[] = $db->quote(0);
                $values[] = $db->quote($scsID);
            } else {
                return false;
            }

            $values[] = $db->quote($link);
            $values[] = $db->quote($title);

            $query->values(implode(', ', $values));
            $db->setQuery($query);
            $db->execute();

            return true;
        } catch (\Throwable $th) {
            return false;
        }
    }

    /**
     * Clear all article/category links
     *
     * @return boolean
     */
    private function ClearItemLinks($scsID = 0, $scsContext = '')
    {

        try {

            if (!is_numeric($scsID) || $scsID < 1) {
                return false;
            }

            $db = Factory::getDbo();

            $query = $db->getQuery(true);
            $query->delete($db->quoteName('#__safecoder_pagelinks'));

            if ($scsContext == 'com_content.article') {
                $query->where($db->quoteName('ArticleID') . ' = ' . $db->quote($scsID));
            } else if ($scsContext == 'com_categories.category') {
                $query->where($db->quoteName('CategoryID') . ' = ' . $db->quote($scsID));
            } else {
                return false;
            }

            $db->setQuery($query);
            $db->execute();

            return true;
        } catch (\Throwable $th) {
            return false;
        }
    }

    /**
     * Get plugin settings Event
     *
     * @return stdClass
     */
    public function onSafeCoderLoadPageLinksSettings() {

        $UseLinkTitle = $this->params->get('UseLinkTitle', 1);
        if($UseLinkTitle != 1) {
            $UseLinkTitle = 0;
        }

        $LinkTitleNotAvailable = $this->params->get('LinkTitleNotAvailable', 1);
        if($LinkTitleNotAvailable != 1) {
            $LinkTitleNotAvailable = 0;
        }

        $LinkTitleCustomText = $this->params->get('LinkTitleCustomText', 'See reference...');
        $LinkTitleCustomText = trim(strip_tags($LinkTitleCustomText));
        if(empty($LinkTitleCustomText)) {
            $LinkTitleCustomText = 'See reference...';
        }

        $LinkTitleLimit = $this->params->get('LinkTitleLimit', 0);
        if(empty($LinkTitleLimit) || !is_numeric($LinkTitleLimit) || $LinkTitleLimit < 1) {
            $LinkTitleLimit = 0;
        }

        $LinkTitleContinuation = $this->params->get('LinkTitleContinuation', '');
        $LinkTitleContinuation = trim(strip_tags($LinkTitleContinuation));

        $OpenLinkIn = $this->params->get('OpenLinkIn', 1);
        if($OpenLinkIn != 1) {
            $OpenLinkIn = 0;
        }

        $IgnoreLinks = (array) $this->params->get('IgnoreLinks', array());
        $IgnoreLinksList = array();
        
        if(is_array($IgnoreLinks) && count($IgnoreLinks) > 0) {

            foreach ($IgnoreLinks as $key => $value) {
                if(property_exists($value, 'LinkItem')) {
                    $value->LinkItem = trim(strip_tags($value->LinkItem));
                    if(!empty($value->LinkItem)) {
                        $IgnoreLinksList[] = $value->LinkItem;
                    }
                }
            }
            
        }
        
        $resultObj = new stdClass();
        $resultObj->UseLinkTitle = $UseLinkTitle;
        $resultObj->LinkTitleNotAvailable = $LinkTitleNotAvailable;
        $resultObj->LinkTitleCustomText = $LinkTitleCustomText;
        $resultObj->LinkTitleLimit = $LinkTitleLimit;
        $resultObj->LinkTitleContinuation = $LinkTitleContinuation;
        $resultObj->OpenLinkIn = $OpenLinkIn;
        $resultObj->IgnoreLinks = $IgnoreLinksList;

        return $resultObj;

    }

    /**
     * Extract links for all existing articles
     *
     * @return boolean
     */
    public function ProcessAllArticles() {

        try {
            
            $db = Factory::getDbo();

            $query = $db->getQuery(true);
            $query->select($db->quoteName(array('id', 'introtext', 'fulltext')));
            $query->from($db->quoteName('#__content'));
            $query->order($db->quoteName('id') . ' ASC');
            $db->setQuery($query);
            $list = $db->loadAssocList();

            if(!is_array($list) || count($list) < 1) {
                return false;
            }

            foreach($list as $item) {

                if(!is_array($item) || count($item) != 3 || !array_key_exists('id', $item) || !array_key_exists('introtext', $item) || !array_key_exists('fulltext', $item)) {
                    continue;
                } 

                if(empty($item['id']) || $item['id'] < 1) {
                    continue;
                }

                $content = trim($item['introtext'] . $item['fulltext']);
                if(empty($content)) {
                    continue;
                }

                $this->ProcessContent($item['id'], 'com_content.article', $content);

            }

            return true;

        } catch (\Throwable $th) {
            return false;
        }

    }

    /**
     * Extract links for all existing categories
     *
     * @return boolean
     */
    public function ProcessAllCategories() {

        try {
            
            $db = Factory::getDbo();

            $query = $db->getQuery(true);
            $query->select($db->quoteName(array('id', 'description')));
            $query->from($db->quoteName('#__categories'));
            $query->order($db->quoteName('id') . ' ASC');
            $db->setQuery($query);
            $list = $db->loadAssocList();

            if(!is_array($list) || count($list) < 1) {
                return false;
            }

            foreach($list as $item) {

                if(!is_array($item) || count($item) != 3 || !array_key_exists('id', $item) || !array_key_exists('description', $item)) {
                    continue;
                } 

                if(empty($item['id']) || $item['id'] < 1) {
                    continue;
                }

                $content = trim($item['description']);
                if(empty($content)) {
                    continue;
                }

                $this->ProcessContent($item['id'], 'com_categories.category', $content);

            }

            return true;

        } catch (\Throwable $th) {
            return false;
        }

    }

}
