<?php


/**
 * @author  <asudau@uos.de>
 *
 * @property int     $id
 * @property string  $type
 * @property int     $related_contact
 * @property string  $content
 * @property int     $mkdate
 */
class EportfolioGroupUser extends SimpleORMap
{

    public $errors = array();

    /**
     * Give primary key of record as param to fetch
     * corresponding record from db if available, if not preset primary key
     * with given value. Give null to create new record
     *
     * @param mixed $id primary key of table
     */
    public function __construct($id = null) {

        $this->db_table = 'eportfolio_groups_user';

        parent::__construct($id);
    }
    
     public static function findByGroupId($id)
    {
        return static::findBySQL('seminar_id = ?', array($id));
    }
    
}
