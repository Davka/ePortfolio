<?php

include_once __DIR__.'/EportfolioGroup.class.php';

/**
 * @author  <asudau@uos.de>
 *
 * @property varchar     $Seminar_id
 * @property varchar     $eportfolio_id
 * @property varchar     $group_id
 * @property string      $templateStatus
 * @property varchar     $owner_id
 * @property varchar     $supervisor_id
 * @property json        $freigaben_kapitel //deprecated
 * @property varchar     $template_id
 * @property json        $settings //deprecated?
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
    
    public function getPortfolioVorlagen(){

      global $perm;
      $semId;
      $seminare = array();

      $semId = Config::get()->getValue('SEM_CLASS_PORTFOLIO_VORLAGE');

      $db = DBManager::get();
      $query = "SELECT Seminar_id FROM seminare WHERE status = :semId";
      $statement = $db->prepare($query);
      $statement->execute(array(':semId'=> $semId));
      foreach ($statement->fetchAll() as $key) {
        if($perm->have_studip_perm('autor', $key[Seminar_id])){
            array_push($seminare, $key[Seminar_id]);
        }
      }

      return $seminare;

    }
    
    public static function getChapters($id){
        $db = DBManager::get();
        $query = "SELECT title, id FROM mooc_blocks WHERE seminar_id = :id AND type = 'Chapter' ORDER BY position ASC";
        $statement = $db->prepare($query);
        $statement->execute(array(':id'=> $id));
        return $statement->fetchAll();
    }
}
