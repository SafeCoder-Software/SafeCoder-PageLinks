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

use Joomla\CMS\Language\Text;

defined('_JEXEC') or die;

?>


<button class="uk-button uk-button-default uk-margin-small-right" type="button" uk-toggle="target: #safecoderpagelinks<?php echo $module->id; ?>">
    <?php echo Text::_('MOD_SAFECODER_PAGELINKS_BUTTON_OPEN'); ?>
</button>

<!-- This is the modal -->
<div id="safecoderpagelinks<?php echo $module->id; ?>" uk-modal>
    <div class="uk-modal-dialog uk-modal-body">
        <h2 class="uk-modal-title"><?php echo Text::_('MOD_SAFECODER_PAGELINKS_MODAL_TITLE'); ?></h2>
        <?php if (is_array($list) && count($list) > 0) : ?>

            <ul style="list-style-type: none;">
                <?php foreach ($list as $item) : ?>

                    <li>
                        <span uk-icon="arrow-right"></span> <a href="<?php echo $item['Link']; ?>" target="<?php echo $item['OpenIn']; ?>">
                            <?php echo $item['TitleValue']; ?>
                        </a>
                    </li>

                <?php endforeach; ?>

            </ul>

        <?php endif; ?>
        <p class="uk-text-right">
            <button class="uk-button uk-button-default uk-modal-close" type="button"><?php echo Text::_('JCLOSE'); ?></button>
        </p>
    </div>
</div>