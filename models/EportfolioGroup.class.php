<?php

include_once __DIR__.'/SupervisorGroup.class.php';

/**
 * @author  <asudau@uos.de>
 *
 * @property varchar                $seminar_id
 * @property varchar                $owner_id
 * @property text                   $templates
 * @property varchar                $supervisor_group_id
 * @property EportfolioGroupUser[]  $user
 */
class EportfolioGroup extends SimpleORMap
{

    public $errors = array();

    //testen
    protected static function configure($config = array())
    {
        $config['db_table'] = 'eportfolio_groups';

        $config['belongs_to']['group_owner'] = array(
            'class_name' => 'User',
            'foreign_key' => 'owner_id', );

        $config['has_many']['user'] = array(
            'class_name' => 'EportfolioGroupUser',
            'assoc_foreign_key' => 'seminar_id',
            'assoc_func' => 'findByGroupId',
            'on_delete' => 'delete',
            'on_store' => 'store',
        );

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



  public static function getGroupMember($id) {
    $group = new EportfolioGroup($id);
    $array = array();
    foreach ($group->user as $user) {
      array_push($array, $user->user_id);
    }
    return $array;
  }

  public static function getAllSupervisors($id) {
    $group = new EportfolioGroup($id);
    $supervisorGroup = new SupervisorGroup($group->supervisor_group_id);
    $array = array();
    foreach ($supervisorGroup->user as $user) {
      array_push($array, $user->user_id);
    }
    return $array;
  }

  //TODO anpassen
  public static function newGroup($owner, $title, $text){
    $current_semester = Semester::findCurrent();    
    $seminar = new Seminar();
    $id = $seminar->getId();
    $seminar->name = $title;
    $seminar->setEndSemester(-1);
    $seminar->setStartSemester($current_semester->beginn);
    $seminar->store();
    $seminar->addMember($owner, 'dozent', true);

    $sem_class = Config::get()->getValue('SEM_CLASS_PORTFOLIO_Supervisionsgruppe');
    //Course Objekt vom Seminar erzeugen und visible setzen
    $course = new Course($id);
    $course->visible = 0;
    $course->status = $sem_class;
    $course->beschreibung = $text;
    $course->store();
    
    $supervisorgroup = new SupervisorGroup();
    $supervisorgroup->name = $title;
    $supervisorgroup->store();

    //var_dump($id);
    $group = new EportfolioGroup($id);
    $group->supervisor_group_id = $supervisorgroup->id;
    $group->owner_id = $owner;
    $group->store();

    $supervisorgroup->eportfolio_group = $group;
    $supervisorgroup->store();
    $supervisorgroup->addUser($owner);

    return $id;
  }

  public static function deleteUser($userId, $seminar_id){
    $user = EportfolioGroupUser::findBySQL('user_id = :user_id AND seminar_id = :seminar_id',
                array(':user_id' => $user_id, ':seminar_id' => $seminar_id));
        $user->delete();

        $seminar = new Seminar($this->eportfolio_group);
        $seminar->deleteMember($user_id);
        $sem->store();
  }

  public static function getOwner($id){
    $query = "SELECT owner_id FROM eportfolio_groups WHERE seminar_id = :id";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array(':id'=> $id));
    return $statement->fetchAll()[0][0];
  }

  public static function getFirstGroupOfuser($userId){
    return self::getAllGroupsOfSupervisor($userId)[0];
  }

  public static function getAllGroupsOfUser($userId){
    $query = "SELECT seminar_id FROM eportfolio_groups_user WHERE user_id = :user_id";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array(':user_id'=> $userId));
    return $statement->fetchAll();
  }

  //brauchen wir auf jeden Fall
  public static function getAllGroupsOfSupervisor($userId){
      $ownGroups = EportfolioGroup::findBySQL('owner_id = :id', array(':id'=> $userId));
      $addedGroups = SupervisorGroupUser::getSupervisorGroups($userId);

      $array = array();
      foreach ($ownGroups as $group) {
        array_push($array, $group->seminar_id);
      }
      foreach ($addedGroups as $group) {
        if ($group->eportfolio_group->seminar_id){
            array_push($array, $group->eportfolio_group->seminar_id);
        }
      }

      return array_unique($array);
  }

  public function getGroupId(){
    return $this->seminar_id;
  }

  public static function getSupervisorGroupId($id){
    return self::findById($id)->supervisor_group_id;
  }

  public function getRelatedStudentPortfolios(){
      $member = $this->user;
      $portfolios = array();
      if (count($this->templates) >= 1) {

        foreach ($member as $key) {
          $portfolio = Eportfoliomodel::findBySQL('group_id = :groupid AND owner_id = :value', array(':groupid'=> $this->seminar_id, ':value'=> $key->user_id));
          array_push($portfolios, $portfolio[0]->Seminar_id);
        }
        return $portfolios;
      } else return NULL;
  }

  public static function deleteGroup($cid){

    #supervisorgroup holen
    $supervisor_group_id = self::findById($cid)->supervisor_group_id;

    // #eportfolio_groups l�schen
    $group = new EportfolioGroup($cid);
    $group->delete();

    // #eportfolio_groups_user l�schen
    $query = "DELETE FROM eportfolio_groups_user WHERE seminar_id = :seminar_id";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array(':seminar_id'=> $cid));

    // #seminar mit id l�schen
    $course = new Seminar($cid);
    $course->delete();

    #supervisor_group l�schen
    SupervisorGroup::deleteGroup($supervisor_group_id);

    #eportfolio mit group_id l�schen
    $eportfolio = new Eportfoliomodel($cid);
    $eportfolio->delete();

    #eportfolio_user

  }

}
