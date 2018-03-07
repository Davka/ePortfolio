<?php


/**
 * @author  <asudau@uos.de>
 *
 * @property varchar     $seminar_id
 * @property varchar     $user_id
 */
class EportfolioGroupUser extends SimpleORMap
{

    public $errors = array();

     protected static function configure($config = array())
    {
        $config['db_table'] = 'eportfolio_groups_user';

        $config['belongs_to']['eportfolio_group'] = array(
            'class_name' => 'EportfolioGroup',
            'foreign_key' => 'seminar_id', );
        
        
         parent::configure($config);
    }
    
    
    /**
     * Give primary key of record as param to fetch
     * corresponding record from db if available, if not preset primary key
     * with given value. Give null to create new record
     *
     * @param mixed $id primary key of table
     */
    public function __construct($id = null) {

        parent::__construct($id);
    }
    
     public static function findByGroupId($id)
    {
        return static::findBySQL('seminar_id = ?', array($id));
    }
    
}
