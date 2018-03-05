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
class Eportfoliomodel extends SimpleORMap
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

        $this->db_table = 'eportfolio';

        parent::__construct($id);
    }
    
    public static function getAllSupervisors($cid){
        $supervisoren = array();
        $portfolio = Eportfoliomodel::findBySQL('Seminar_id = :cid', array(':cid' => $cid));
        if ($portfolio[0]->group_id){
            array_push($supervisoren, EportfolioGroup::getAllSupervisors($portfolio[0]->group_id));
        }
        return $supervisoren[0];
    }
}
