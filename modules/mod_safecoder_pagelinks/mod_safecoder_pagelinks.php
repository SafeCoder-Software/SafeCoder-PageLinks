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

require_once JPATH_ROOT . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR . 'mod_safecoder_pagelinks' . DIRECTORY_SEPARATOR . 'helper' . DIRECTORY_SEPARATOR . 'PageLinksHelper.php';

use Joomla\CMS\Helper\ModuleHelper;
use SafeCoderSoftware\Module\PageLinksHelper;

defined('_JEXEC') or die;

$list = PageLinksHelper::LoadPageLinks();
$listReferences = PageLinksHelper::LoadLinkReferences();

require ModuleHelper::getLayoutPath('mod_safecoder_pagelinks', $params->get('layout', 'default'));
