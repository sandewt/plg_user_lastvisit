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
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Database\ParameterType;

\defined('_JEXEC') or die;

/**
 * LastVisit plugin class.
 *
 * @since  1.0.0
 */
final class LastVisit extends CMSPlugin
{
    use DatabaseAwareTrait;

    /**
     * This plugin shows the last (previous) visit date after a login in the frontend.
     *
     * @return  void
     *
     * @since   1.0.0
     */
    public function onUserAfterLogin(): void
    {
        if (!$this->getApplication()->isClient('site')) {
            return;
        }

        if (!PluginHelper::isEnabled('actionlog', 'joomla')) {
            return;
        }

        // Load plugin language files only when needed
        $this->loadLanguage();

        // Load the last visit date
        $this->showLastVisitDate();
    }

    /**
     * Show the last visit date
     *
     * @return  void
     *
     * @since   1.0.0
     */
    private function showLastVisitDate(): void
    {
        $app  = $this->getApplication();
        $user = $app->getIdentity();
        $lang = $app->getLanguage();

        // Get the user data from the action_logs table
        $db    = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select($db->quoteName('log_date'))
            ->from($db->quoteName('#__action_logs'))
            ->where($db->quoteName('user_id') . ' = :id')
            ->where($db->quoteName('message_language_key') . ' = ' . $db->quote('PLG_ACTIONLOG_JOOMLA_USER_LOGGED_IN'))
            ->order($db->quoteName('log_date') . ' DESC')
            ->bind(':id', $user->id, ParameterType::INTEGER);
        $db->setQuery($query, 1, 1);

        try {
            $result = $db->loadObject();
        } catch (\RuntimeException) {
            // Ignore it
        }

        // Show a message with the last visit date
        if (!empty($result->log_date)) {
            $lastvisit = HTMLHelper::_('date', $result->log_date, $lang->_('DATE_FORMAT_LC2'));
            $app->enqueueMessage(sprintf($lang->_('PLG_USER_LASTVISIT_SHOWDATE'), $lastvisit), 'info');
        }
    }
}
