<?php

/**
 * @package     SafeCoder PgeLinks
 * @subpackage  mod_safecoder_pagelinks
 * 
 * @version     1.0.0
 * 
 * @author      Miron Savan <hello@safecoder.com>
 * @link        https://www.safecoder.com/pagelinks
 * @copyright   Copyright (C) 2012 SafeCoder Software SRL (RO30786660)
 * @license     GNU GPLv2 <http://www.gnu.org/licenses/gpl.html> or later; see LICENSE.txt
 */

namespace SafeCoderSoftware\Module;

use Joomla\CMS\Event\GenericEvent;
use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;

\defined('_JEXEC') or die;

class PageLinksHelper
{

    /**
     * Access pagelinks for page
     *
     * @return array
     */
    public static function LoadPageLinks()
    {

        try {

            /** @var \Joomla\CMS\Application\CMSApplication $app */
            $app = Factory::getApplication();

            $option = $app->input->get('option', '', 'CMD');
            $view = $app->input->get('view', '', 'CMD');
            $id = $app->input->get('id', 0, 'INT');

            if (!\in_array($option, array('com_content', 'com_categories'))) {
                return array();
            }

            if (!\in_array($view, array('article', 'category'))) {
                return array();
            }

            if (empty($id) || !\is_numeric($id) || $id < 1) {
                return array();
            }

            $links = self::LoadLinks($id, $view);
            if (!\is_array($links) || count($links) < 1) {
                return array();
            }

            $PluginConfig = self::LoadPluginSettings();

            return self::ProcessLinkValues($links, $PluginConfig);
        } catch (\Throwable $th) {
            return array();
        }
    }

    public static function LoadLinkReferences()
    {

        try {

            /** @var \Joomla\CMS\Application\CMSApplication $app */
            $app = Factory::getApplication();

            $option = $app->input->get('option', '', 'CMD');
            $view = $app->input->get('view', '', 'CMD');
            $id = $app->input->get('id', 0, 'INT');

            if (!\in_array($option, array('com_content', 'com_categories'))) {
                return array();
            }

            if (!\in_array($view, array('article', 'category'))) {
                return array();
            }

            if (empty($id) || !\is_numeric($id) || $id < 1) {
                return array();
            }

            $links = self::LoadLinksReferences($id, $view);
            if (!\is_array($links) || count($links) < 1) {
                return array();
            }

            $PluginConfig = self::LoadPluginSettings();

            return self::ProcessLinkValues($links, $PluginConfig);
        } catch (\Throwable $th) {
            return false;
        }
    }

    /**
     * 
     * Load links list from database
     *
     * @param integer $id - article/category id
     * @param string $view -> view: article or category
     * @return array
     * 
     */
    private static function LoadLinks($id = 0, $view = '')
    {

        if (empty($id) || !\is_numeric($id) || $id < 1) {
            return array();
        }

        if (!\in_array($view, array('article', 'category'))) {
            return array();
        }

        try {

            $db = Factory::getDbo();

            $columns = array();
            $columns[] = 'Link';
            $columns[] = 'Title';

            $query = $db->getQuery(true);
            $query->select($db->quoteName($columns));
            $query->from($db->quoteName('#__safecoder_pagelinks'));

            if ($view == 'article') {
                $query->where($db->quoteName('ArticleID') . ' = ' . $db->quote($id));
            } else if ($view == 'category') {
                $query->where($db->quoteName('CategoryID') . ' = ' . $db->quote($id));
            }

            $db->setQuery($query);
            $results = $db->loadAssocList();

            if (!\is_array($results) || count($results) < 1) {
                return array();
            }

            return $results;
        } catch (\Throwable $th) {
            return array();
        }
    }


    private static function LoadLinksReferences($id = 0, $view = '')
    {

        if (empty($id) || !\is_numeric($id) || $id < 1) {
            return array();
        }

        if (!\in_array($view, array('article', 'category'))) {
            return array();
        }

        try {

            $db = Factory::getDbo();


            $query = $db->getQuery(true);
            $query->select($db->quoteName(array('title', 'alias')));
            if($view == 'article') {
                $query->select($db->quoteName('catid'));
                $query->from($db->quoteName('#__content'));
            }
            else if($view == 'category') {
                $query->from($db->quoteName('#__categories'));
            }
            $query->where($db->quoteName('id') . ' = ' . $db->quote($id));
            $db->setQuery($query);
            $details = $db->loadAssoc();

            if(!\is_array($details) || count($details) < 1 || !\array_key_exists('title', $details) || !\array_key_exists('alias', $details)) {
                return array();
            }

            if(empty($details['title']) || empty($details['alias'])) {
                return array();
            }

            $linkURL = $id . '-' . $details['alias'];

            $columns = array();
            if ($view == 'article') {
                $columns[] = 'ArticleID';
            } else if ($view == 'category') {
                $columns[] = 'CategoryID';
            } else {
                return array();
            }

            $query = $db->getQuery(true);
            $query->select($db->quoteName($columns));
            $query->from($db->quoteName('#__safecoder_pagelinks'));
            $query->where($db->quoteName('Link') . ' LIKE(' . $db->quote('%' . $linkURL) . ')');
            $query->orWhere($db->quoteName('Link') . 'LIKE(' . $db->quote('%index.php?option=com_content&amp;view=article&amp;id=' . $id . '&amp;catid=' . $details['catid']) . ')');
            $query->orWhere($db->quoteName('Link') . 'LIKE(' . $db->quote('%index.php?option=com_content&view=article&id=' . $id . '&catid=' . $details['catid']) . ')');
            $query->orWhere($db->quoteName('Link') . 'LIKE(' . $db->quote('%index.php?option=com_content&view=article&id=' . $id) . ')');
            $query->orWhere($db->quoteName('Link') . 'LIKE(' . $db->quote('%index.php?option=com_content&amp;view=article&amp;id=' . $id) . ')');
            $db->setQuery($query);
            $results = $db->loadColumn();

            if (!\is_array($results) || count($results) < 1) {
                return array();
            }

            $results = \array_unique($results);

            $returnList = array();
            foreach ($results as $returnItem) {

                if (empty($returnItem) || !\is_numeric($returnItem) || $returnItem < 1) {
                    continue;
                }

                $rItem = array();
                if ($view == 'article') {

                    $query = $db->getQuery(true);
                    $query->select($db->quoteName(array('title', 'alias', 'catid')));
                    $query->from($db->quoteName('#__content'));
                    $query->where($db->quoteName('id') . ' = ' . $db->quote($returnItem));
                    $db->setQuery($query);
                    $det = $db->loadAssoc();

                    if(!\is_array($det) || count($det) < 1) {
                        continue;
                    }

                    $rItem['Link'] = Route::_('index.php?option=com_content&view=article&id=' . $returnItem . '&catid=' . $det['catid']);
                    $rItem['Title'] = $det['title'];
                } else if ($view == 'category') {
continue;
                } else {
                    continue;
                }


                $returnList[] = $rItem;
            }

            return $returnList;
        } catch (\Throwable $th) {
            return array();
        }
    }

    /**
     * Get plugin settings
     *
     * @return stdClass
     */
    private static function LoadPluginSettings()
    {

        /** @var \Joomla\CMS\Application\CMSApplication $app */
        $app = Factory::getApplication();

        $dispatcher = $app->getDispatcher();

        $event = new GenericEvent('onSafeCoderLoadPageLinksSettings');

        $EventResult = $dispatcher->dispatch('onSafeCoderLoadPageLinksSettings', $event);

        $PluginConfig = $EventResult->getArgument('result');
        if (!\is_null($PluginConfig) && \is_array($PluginConfig) && \array_key_exists(0, $PluginConfig)) {
            $PluginConfig = $PluginConfig[0];
        }

        if (!\is_object($PluginConfig) || !\property_exists($PluginConfig, 'UseLinkTitle')) {
            $PluginConfig = null;
        }

        return $PluginConfig;
    }

    /**
     * Prepare links for display
     *
     * @param array $links
     * @param [type] $PluginConfig
     * @return void
     */
    private static function ProcessLinkValues($links = array(), $PluginConfig = null)
    {

        try {

            if (!\is_array($links) || count($links) < 1) {
                return array();
            }

            $newList = array();

            foreach ($links as $value) {

                if (!\is_array($value) || count($value) < 1 || !\array_key_exists('Link', $value) || !\array_key_exists('Title', $value)) {
                    continue;
                }

                if (empty($value['Link'])) {
                    continue;
                }

                if (\property_exists($PluginConfig, 'IgnoreLinks')) {
                    if (\is_array($PluginConfig->IgnoreLinks) && \count($PluginConfig->IgnoreLinks) > 0) {
                        if (\in_array($value['Link'], $PluginConfig->IgnoreLinks)) {
                            continue;
                        }
                    }
                }

                $value['Link'] = Route::_($value['Link'], true, null, true);

                if (\property_exists($PluginConfig, 'IgnoreLinks')) {
                    if (\is_array($PluginConfig->IgnoreLinks) && \count($PluginConfig->IgnoreLinks) > 0) {
                        if (\in_array($value['Link'], $PluginConfig->IgnoreLinks)) {
                            continue;
                        }
                    }
                }

                if (\property_exists($PluginConfig, 'UseLinkTitle')) {

                    if ($PluginConfig->UseLinkTitle == 1) {
                        $value['TitleValue'] = $value['Title'];
                    } else {
                        $value['TitleValue'] = $value['Link'];
                    }
                } else {
                    $value['TitleValue'] = $value['Title'];
                }

                if (empty($value['TitleValue'])) {

                    if (\property_exists($PluginConfig, 'LinkTitleNotAvailable')) {
                        if ($PluginConfig->LinkTitleNotAvailable != 1) {
                            if (\property_exists($PluginConfig, 'LinkTitleCustomText')) {
                                $value['TitleValue'] = $PluginConfig->LinkTitleCustomText;
                            }
                        }
                    }
                }

                if (empty($value['TitleValue'])) {
                    $value['TitleValue'] = $value['Link'];
                }

                if (empty($value['TitleValue'])) {
                    continue;
                }

                if (\property_exists($PluginConfig, 'LinkTitleLimit')) {

                    if (\is_numeric($PluginConfig->LinkTitleLimit) && $PluginConfig->LinkTitleLimit > 0 && \strlen($value['TitleValue']) > $PluginConfig->LinkTitleLimit) {

                        $value['TitleValue'] = substr($value['TitleValue'], 0, $PluginConfig->LinkTitleLimit);

                        if (\property_exists($PluginConfig, 'LinkTitleContinuation')) {
                            $value['TitleValue'] .= $PluginConfig->LinkTitleContinuation;
                        }
                    }
                }

                if (\property_exists($PluginConfig, 'OpenLinkIn')) {

                    if ($PluginConfig->OpenLinkIn == 1) {
                        $value['OpenIn'] = '_blank';
                    } else {
                        $value['OpenIn'] = '_self';
                    }
                } else {
                    $value['OpenIn'] = '_blank';
                }


                $newList[] = $value;
            }

            return $newList;
        } catch (\Throwable $th) {
            return array();
        }
    }
}
