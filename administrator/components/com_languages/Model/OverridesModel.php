<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_languages
 *
 * @copyright   Copyright (C) 2005 - 2018 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\Languages\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Language\LanguageHelper;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\Component\Languages\Administrator\Helper\LanguagesHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Factory;

/**
 * Languages Overrides Model
 *
 * @since  2.5
 */
class OverridesModel extends ListModel
{
	/**
	 * Constructor.
	 *
	 * @param   array                $config   An optional associative array of configuration settings.
	 * @param   MVCFactoryInterface  $factory  The factory.
	 *
	 * @see     \Joomla\CMS\MVC\Model\BaseDatabaseModel
	 * @since   3.2
	 */
	public function __construct($config = array(), MVCFactoryInterface $factory = null)
	{
		parent::__construct($config, $factory);

		$this->filter_fields = array('key', 'text');
	}

	/**
	 * Retrieves the overrides data
	 *
	 * @param   boolean  $all  True if all overrides shall be returned without considering pagination, defaults to false
	 *
	 * @return  array  Array of objects containing the overrides of the override.ini file
	 *
	 * @since   2.5
	 */
	public function getOverrides($all = false)
	{
		jimport('joomla.filesystem.file');

		// Get a storage key.
		$store = $this->getStoreId();

		// Try to load the data from internal storage.
		if (!empty($this->cache[$store]))
		{
			return $this->cache[$store];
		}

		$client = in_array($this->state->get('filter.client'), array(0, 'site')) ? 'SITE' : 'ADMINISTRATOR';

		// Parse the override.ini file in order to get the keys and strings.
		$filename = constant('JPATH_' . $client) . '/language/overrides/' . $this->getState('filter.language') . '.override.ini';
		$strings = LanguagesHelper::parseFile($filename);

		// Delete the override.ini file if empty.
		if (file_exists($filename) && empty($strings))
		{
			File::delete($filename);
		}

		// Filter the loaded strings according to the search box.
		$search = $this->getState('filter.search');

		if ($search != '')
		{
			$search = preg_quote($search, '~');
			$matchvals = preg_grep('~' . $search . '~i', $strings);
			$matchkeys = array_intersect_key($strings, array_flip(preg_grep('~' . $search . '~i',  array_keys($strings))));
			$strings = array_merge($matchvals, $matchkeys);
		}

		// Consider the ordering
		if ($this->getState('list.ordering') == 'text')
		{
			if (strtoupper($this->getState('list.direction')) == 'DESC')
			{
				arsort($strings);
			}
			else
			{
				asort($strings);
			}
		}
		else
		{
			if (strtoupper($this->getState('list.direction')) == 'DESC')
			{
				krsort($strings);
			}
			else
			{
				ksort($strings);
			}
		}

		// Consider the pagination.
		if (!$all && $this->getState('list.limit') && $this->getTotal() > $this->getState('list.limit'))
		{
			$strings = array_slice($strings, $this->getStart(), $this->getState('list.limit'), true);
		}

		// Add the items to the internal cache.
		$this->cache[$store] = $strings;

		return $this->cache[$store];
	}

	/**
	 * Method to get the total number of overrides.
	 *
	 * @return  int	The total number of overrides.
	 *
	 * @since		2.5
	 */
	public function getTotal()
	{
		// Get a storage key.
		$store = $this->getStoreId('getTotal');

		// Try to load the data from internal storage
		if (!empty($this->cache[$store]))
		{
			return $this->cache[$store];
		}

		// Add the total to the internal cache.
		$this->cache[$store] = count($this->getOverrides(true));

		return $this->cache[$store];
	}

	/**
	 * Method to auto-populate the model state.
	 *
	 * Note. Calling getState in this method will result in recursion.
	 *
	 * @param   string  $ordering   An optional ordering field.
	 * @param   string  $direction  An optional direction (asc|desc).
	 *
	 * @return  void
	 *
	 * @since   2.5
	 */
	protected function populateState($ordering = 'key', $direction = 'asc')
	{
		$app = Factory::getApplication();

		// Use default language of frontend for default filter.
		$default = ComponentHelper::getParams('com_languages')->get('site') . '0';

		$old_language_client = $app->getUserState('com_languages.overrides.filter.language_client', '');
		$language_client     = $this->getUserStateFromRequest('com_languages.overrides.filter.language_client', 'filter_language_client', $default, 'cmd');

		if ($old_language_client != $language_client)
		{
			$client   = substr($language_client, -1);
			$language = substr($language_client, 0, -1);
		}
		else
		{
			$client   = $app->getUserState('com_languages.overrides.filter.client', 0);
			$language = $app->getUserState('com_languages.overrides.filter.language', 'en-GB');
		}

		// Sets the search filter.
		$search = $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
		$this->setState('filter.search', $search);

		$this->setState('filter.language_client', $language . $client);
		$this->setState('filter.client', $client ? 'administrator' : 'site');
		$this->setState('filter.language', $language);

		// Add filters to the session because they won't be stored there by 'getUserStateFromRequest' if they aren't in the current request.
		$app->setUserState('com_languages.overrides.filter.client', $client);
		$app->setUserState('com_languages.overrides.filter.language', $language);

		// List state information
		parent::populateState($ordering, $direction);
	}

	/**
	 * Method to get all found languages of frontend and backend.
	 *
	 * The resulting array has entries of the following style:
	 * <Language Tag>0|1 => <Language Name> - <Client Name>
	 *
	 * @return  array  Sorted associative array of languages.
	 *
	 * @since		2.5
	 */
	public function getLanguages()
	{
		// Try to load the data from internal storage.
		if (!empty($this->cache['languages']))
		{
			return $this->cache['languages'];
		}

		// Get all languages of frontend and backend.
		$languages       = array();
		$site_languages  = LanguageHelper::getKnownLanguages(JPATH_SITE);
		$admin_languages = LanguageHelper::getKnownLanguages(JPATH_ADMINISTRATOR);

		// Create a single array of them.
		foreach ($site_languages as $tag => $language)
		{
			$languages[$tag . '0'] = Text::sprintf('COM_LANGUAGES_VIEW_OVERRIDES_LANGUAGES_BOX_ITEM', $language['name'], Text::_('JSITE'));
		}

		foreach ($admin_languages as $tag => $language)
		{
			$languages[$tag . '1'] = Text::sprintf('COM_LANGUAGES_VIEW_OVERRIDES_LANGUAGES_BOX_ITEM', $language['name'], Text::_('JADMINISTRATOR'));
		}

		// Sort it by language tag and by client after that.
		ksort($languages);

		// Add the languages to the internal cache.
		$this->cache['languages'] = $languages;

		return $this->cache['languages'];
	}

	/**
	 * Method to delete one or more overrides.
	 *
	 * @param   array  $cids  Array of keys to delete.
	 *
	 * @return  integer Number of successfully deleted overrides, boolean false if an error occurred.
	 *
	 * @since		2.5
	 */
	public function delete($cids)
	{
		// Check permissions first.
		if (!Factory::getUser()->authorise('core.delete', 'com_languages'))
		{
			$this->setError(Text::_('JLIB_APPLICATION_ERROR_DELETE_NOT_PERMITTED'));

			return false;
		}

		jimport('joomla.filesystem.file');

		$filterclient = Factory::getApplication()->getUserState('com_languages.overrides.filter.client');
		$client = $filterclient == 0 ? 'SITE' : 'ADMINISTRATOR';

		// Parse the override.ini file in oder to get the keys and strings.
		$filename = constant('JPATH_' . $client) . '/language/overrides/' . $this->getState('filter.language') . '.override.ini';
		$strings = LanguagesHelper::parseFile($filename);

		// Unset strings that shall be deleted
		foreach ($cids as $key)
		{
			if (isset($strings[$key]))
			{
				unset($strings[$key]);
			}
		}

		// Write override.ini file with the strings.
		if (LanguageHelper::saveToIniFile($filename, $strings) === false)
		{
			return false;
		}

		$this->cleanCache();

		return count($cids);
	}

	/**
	 * Removes all of the cached strings from the table.
	 *
	 * @return  boolean result of operation
	 *
	 * @since   3.4.2
	 */
	public function purge()
	{
		$db = Factory::getDbo();

		// Note: TRUNCATE is a DDL operation
		// This may or may not mean depending on your database
		try
		{
			$db->truncateTable('#__overrider');
		}
		catch (\RuntimeException $e)
		{
			return $e;
		}

		Factory::getApplication()->enqueueMessage(Text::_('COM_LANGUAGES_VIEW_OVERRIDES_PURGE_SUCCESS'));
	}
}
