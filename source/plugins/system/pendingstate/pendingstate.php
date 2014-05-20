<?php
/**
 * Joomla! System plugin - Pending State
 *
 * @author Yireo (info@yireo.com)
 * @copyright Copyright 2014
 * @license GNU Public License
 * @link http://www.yireo.com
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die( 'Restricted access' );

// Import the parent class
jimport( 'joomla.plugin.plugin' );

/*
 * Plugin-class
 */
class plgSystemPendingState extends JPlugin
{
    const STATE_PENDING = 3;
    
	/**
     * Event onContentBeforeSave
	 *
	 * @param	string		The context of the content passed to the plugin
	 * @param	object		A JTableContent object
	 * @param	bool		If the content is just about to be created
	 */
	public function onContentBeforeSave($context, $article, $isNew)
	{
        // No need to do anything if this article has no publish_up field
        if(empty($article->publish_up))
        {
            return;
        }

        // Determine the state field
        if(isset($article->state)) $stateField = 'state';
        if(isset($article->published)) $stateField = 'published';

        // No need to do anything if the state is not published
        if(isset($article->$stateField) && $article->$stateField != 1) 
        {
            return;
        }

        // No need to do anything if this article has been published in the past
        $publish_up = strtotime($article->publish_up);
        if($publish_up < time())
        {
            return;
        }

        // Change state to pending
        $article->$stateField = self::STATE_PENDING;
    }

    /**
     * Event onAfterRender
     *
     * @access public
     * @param null
     * @return null
     */
    public function onAfterRender()
    {
        // Only execute on the frontend
        $app = JFactory::getApplication();
        if ($app->isSite() == false)
        {
            return false;
        }
    
        // Process queue of pending items
        if ($this->params->get('auto_cleanup', 1) == 1) 
        {
            $this->processPendingItems();
        }
    }

    /**
     * Method to process pending items
     *
     * @access public
     * @param null
     * @return null
     */
    public function processPendingItems()
    {
        $tables = array(
            array('#__content', 'state', 'com_content.article'),
        );

        $db = JFactory::getDBO();

        foreach($tables as $table)
        { 
            $tableName = $table[0];
            $stateField = $table[1];
            $context = $table[2];

            $query = $db->getQuery(true);
            $query->select($db->quoteName(array('id')));
            $query->from($db->quoteName($tableName));
            $query->where($db->quoteName($stateField).' = '.self::STATE_PENDING);
            $query->where($db->quoteName('publish_up').' < NOW()');

            $db->setQuery($query);
            $rows = $db->loadAssocList();

            if(empty($rows))
            {
                return;
            }

            $ids = array();
            foreach($rows as $row)
            {
                $query = $db->getQuery(true);
                $query->update($db->quoteName($tableName));
                $query->set($db->quoteName($stateField).'=1');
                $query->where($db->quoteName('id').' = '.$row['id']);
                $db->setQuery($query);
                $db->query();

                $ids[] = $row['id'];
            }

            // Trigger plugins
            $dispatcher = JDispatcher::getInstance();
            JPluginHelper::importPlugin('content');
            $dispatcher->trigger('onContentChangeState', array($context, $ids, 1));
        }
    }
}
