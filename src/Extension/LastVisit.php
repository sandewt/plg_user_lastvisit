<?php

/**
 * @package     EWT.Plugin
 * @subpackage  User.lastvisit
 *
 * @author      JG Sanders
 * @copyright   Copyright (C) 2023 JG Sanders. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later        
 */

namespace EWT\Plugin\User\LastVisit\Extension;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Database\ParameterType;

defined('_JEXEC') or die;

/**
 * LastVisit plugin class.
 *
 * @since  1.0.0
 */
final class LastVisit extends CMSPlugin
{
    use DatabaseAwareTrait;

    /**
     * Load the language file on instantiation.
     *
     * @var    boolean
     * @since  1.0.0
     */
    protected $autoloadLanguage = true;

    /**
     * This plugins shows the last visit date
     *
     * @param   array  $options  Array holding options
     *
     * @return  void
     *
     * @since   1.0.0
     */
    public function onUserAfterLogin($options = []): void
    {
        $app  = $this->getApplication();
        $user = $app->getIdentity();
        $lang = $app->getLanguage();

        if (!$app->isClient('site') && !$user->guest) {
            return;
        }

        $db = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select($db->quoteName('log_date'))
            ->from($db->quoteName('#__action_logs'))
            ->where($db->quoteName('user_id') . ' = :id')
            ->where($db->quoteName('message_language_key') . ' = ' . $db->quote('PLG_ACTIONLOG_JOOMLA_USER_LOGGED_IN'))
            ->order($db->quoteName('log_date') . ' DESC')
            ->bind(':id', $user->id, ParameterType::INTEGER);
        $db->setQuery($query);

        try
        {
            $result = $db->loadRowList();
        } catch (RuntimeException $e) {
            $app->enqueueMessage($lang->_('JERROR_AN_ERROR_HAS_OCCURRED'), 'error');
        }

        $date = HTMLHelper::_('date', $result[1][0], $lang->_('DATE_FORMAT_LC2'));

        $app->enqueueMessage(sprintf($lang->_('PLG_USER_LASTVISIT_DATE'), $date), 'info');
    }
}
