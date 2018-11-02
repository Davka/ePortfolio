<?php

include_once __DIR__.'/EportfolioGroup.class.php';
include_once __DIR__.'/BlockInfo.class.php';

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
 * @property int         $favorite
*/
class Eportfoliomodel extends SimpleORMap
{

    public $errors = array();

    protected static function configure($config = array())
    {
        $config['db_table'] = 'eportfolio';

        $config['belongs_to']['seminar'] = array(
            'class_name' => 'Seminar',
            'foreign_key' => 'Seminar_id',
            'on_delete' => 'delete',);

        $config['belongs_to']['owner'] = array(
            'class_name' => 'User',
            'foreign_key' => 'owner_id',);

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


    public static function getAllSupervisors($cid){
        $supervisoren = array();
        $portfolio = Eportfoliomodel::findBySeminarId($cid);
        if ($portfolio->group_id){
            array_push($supervisoren, EportfolioGroup::getAllSupervisors($portfolio->group_id));
        }
        return $supervisoren[0];
    }

    public function getOwnerFullname(){
        $user = $this->owner;
        $fullname = $user->vorname . ' ' . $user->nachname;
        return $fullname;
    }

    public static function getPortfolioVorlagen(){

      global $perm;
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

    public static function findBySeminarId($sem_id){
        $eportfolio = Eportfoliomodel::findOneBySQL('seminar_id = :id', array(':id'=> $sem_id));
        return $eportfolio;
    }

     public static function isOwner($sem_id, $user_id){
        $eportfolio = Eportfoliomodel::findBySeminarId($sem_id);
        return $eportfolio->owner_id == $user_id;
    }


    public static function getMyPortfolios(){

      $userid = $GLOBALS["user"]->id;
      $myportfolios = array();

      $semClass = Config::get()->getValue('SEM_CLASS_PORTFOLIO');
      $db = DBManager::get();
      $query = "SELECT Seminar_id FROM eportfolio WHERE owner_id = :userid";
      $statement = $db->prepare($query);
      $statement->execute(array(':userid'=> $userid));

      foreach ($statement->fetchAll() as $key) {
        if(Course::find($key[Seminar_id])->status == $semClass){
            array_push($myportfolios, $key[Seminar_id]);
        }
      }
      return $myportfolios;
    }

    /**
    * Gibt ein Array(title, id) mit allen Oberkapiteln einer Veranstaltung aus
    **/
    public static function getChapters($id){
        $db = DBManager::get();
        $query = "SELECT * FROM mooc_blocks WHERE seminar_id = :id AND type = 'Chapter' AND parent_id != '0' ORDER BY position ASC";
        $statement = $db->prepare($query);
        $statement->execute(array(':id'=> $id));
        $result = $statement->fetchAll();
        $return = array();
        foreach ($result as $key) {
          $tmp = array(
            'title' => $key[title],
            'id' => $key[id]
          );
          array_push($return, $tmp);
        }
        return $return;
    }

    /**
    * Gibt ein Array(title, id) mit allen Unterkapiteln eines Oberkapitels aus
    **/
    public static function getSubChapters($chapter_id){
      $query = "SELECT title, id FROM mooc_blocks WHERE parent_id = :parent_id AND type = 'Subchapter' ORDER BY position ASC";
      $statement = DBManager::get()->prepare($query);
      $statement->execute(array(':parent_id' => $chapter_id));
      $result = $statement->fetchAll();
      $return = array();
      foreach ($result as $key) {
        $tmp = array(
          'title' => $key[title],
          'id' => $key[id]
        );
        array_push($return, $tmp);
      }
      return $return;
    }

    /**
    * Prüft ob in in einem Kaptiel einer Courseware eine Resonanz auf
    * eine Supervisorennotiz gegeben wurde
    **/
    public static function checkSupervisorResonanz($chapter_id){
      $query = "SELECT id FROM mooc_blocks WHERE parent_id = :id";
      $statement = DBManager::get()->prepare($query);
      $statement->execute(array(':id'=> $chapter_id));
      $subchapters = $statement->fetchAll(PDO::FETCH_ASSOC);
        foreach ($subchapters as $subchapter) {
          if (Eportfoliomodel::checkSupervisorResonanzInSubchapter($subchapter['id'])) return true;
        }
    }

    /**
    * Prüft ob in in einem Unterkaptiel einer Courseware eine Resonanz auf
    * eine Supervisorennotiz gegeben wurde
    **/
    public static function checkSupervisorResonanzInSubchapter($subchapter_id){
      $query = "SELECT id FROM mooc_blocks WHERE parent_id = :value";
      $statement = DBManager::get()->prepare($query);
      $statement->execute(array(':value'=> $subchapter_id));
      $sections = $statement->fetchAll(PDO::FETCH_ASSOC);
      foreach ($sections as $section) {
        $query = "SELECT id FROM mooc_blocks WHERE parent_id = :valueSub AND type ='PortfolioBlockSupervisor' ";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array(':valueSub'=> $section['id']));
        $supervisorNotizBloecke = $statement->fetchAll(PDO::FETCH_ASSOC);
        foreach ($supervisorNotizBloecke as $block) {
          $query = "SELECT json_data FROM mooc_fields WHERE block_id = :block_id AND name = 'supervisorcontent'";
          $statement = DBManager::get()->prepare($query);
          $statement->execute(array(':block_id'=> $block['id']));
          $supervisorFeedback = $statement->fetchAll();
          if($supervisorFeedback[0][json_data] != '""'){
            return true;
          }
        }
      }
    }

    /**
    * Prüft ob einn Kapitel freigeschaltet wurde
    **/
    public static function checkKapitelFreigabe($chapter_id){
      $query = "SELECT * FROM eportfolio_freigaben WHERE block_id = :block_id";
      $statement = DBManager::get()->prepare($query);
      $statement->execute(array(':block_id' => $chapter_id));
      $result = $statement->fetchAll();
      if(!empty($result)) return true;
    }

    /**
    * Prüft ob es eine SupervisorNotiz in einem Kapitel gibt
    **/
    public function checkSupervisorNotiz($id){
      $db = DBManager::get();
      $query = "SELECT id FROM mooc_blocks WHERE parent_id = :id";
      $statement = $db->prepare($query);
      $statement->execute(array(':id'=> $id));
      $subchapters = $statement->fetchAll(PDO::FETCH_ASSOC);
      foreach ($subchapters as $subchapter) {
        if(Eportfoliomodel::checkSupervisorNotizInUnterKapitel($subchapter['id'])) return true;
      }
    }

    /**
    * Gibt die passende BlockId des EPortfolios anhand der VorlagenblockID zurück
    * $seminar_id ist hier die seminar_id des Portfolios des Users
    **/
    public static function getUserPortfilioBlockId($seminar_id, $block_id){
      $query = "SELECT block_id FROM eportfolio_block_infos WHERE seminar_id = :seminar_id AND vorlagen_block_id = :block_id";
      $statement = DBManager::get()->prepare($query);
      $statement->execute(array(':seminar_id' => $seminar_id, ':block_id' => $block_id));
      $result = $statement->fetchAll();
      return $result[0][0];
    }

    /**
    * Prüft ob ein Kapitel vom Nutzer selber erstellt wurde
    **/
    public static function isEigenesKapitel($seminar_id, $group_id, $chapter_id){
      $query = "SELECT vorlagen_block_id FROM eportfolio_block_infos WHERE block_id = :block_id AND Seminar_id = :seminar_id";
      $statement = DBManager::get()->prepare($query);
      $statement->execute(array(':block_id' => $chapter_id, ':seminar_id' => $seminar_id));
      $result = $statement->fetchAll();
      if(empty($result)){
        return true;
      }
    }

    /**
    * Prüft ob ein Unterkapitel vom Nutzer selber erstellt wurde
    **/
    public static function isEigenesUnterkapitel($subchapter_id){
      $timestapChapter = Eportfoliomodel::getTimestampOfChapter(Eportfoliomodel::getParentId($subchapter_id));
      if ($timestapChapter < Eportfoliomodel::getTimestampOfChapter($subchapter_id)) {
        return true;
      }
    }

    /**
    * Liefert Timestamp eines Kapitels
    **/
    public static function getTimestampOfChapter($block_id){
      $query = "SELECT mkdate FROM mooc_blocks WHERE id = :block_id";
      $statement = DBManager::get()->prepare($query);
      $statement->execute(array(':block_id' => $block_id));
      $result = $statement->fetchAll();
      return $result[0][0];
    }

    /**
    * Liefert den Timestamp des als letzt hinzugefügtes Templates
    * in einer Gruppe
    **/
    public static function getNewestTemplateTimestamp($group_id){
      $query = "SELECT mkdate FROM eportfolio_group_templates WHERE group_id = :group_id ORDER BY mkdate DESC";
      $statement = DBManager::get()->prepare($query);
      $statement->execute(array(':group_id' => $group_id));
      $result = $statement->fetchAll();
      return $result[0][0];
    }

    /**
    * Liefert mkdate des Templates
    **/
    public static function getTimestampOfTemplate($group_id, $seminar_id){
      $query = "SELECT mkdate FROM eportfolio_group_templates WHERE group_id = :group_id AND seminar_id = :seminar_id";
      $statement = DBManager::get()->prepare($query);
      $statement->execute(array(':group_id' => $group_id, ':seminar_id' => $seminar_id));
      $result = $statement->fetchAll();
      return $result[0][0];
    }

    /**
    * liefert ParentId eines Blocks
    **/
    public static function getParentId($block_id){
      $query = "SELECT parent_id FROM mooc_blocks WHERE id = :id";
      $statement = DBManager::get()->prepare($query);
      $statement->execute(array(':id' => $block_id));
      $result = $statement->fetchAll();
      return $result[0][0];
    }

    public static function checkSupervisorNotizInUnterKapitel($subchapter_id){
      $query = "SELECT id FROM mooc_blocks WHERE parent_id = :value";
      $statement = DBManager::get()->prepare($query);
      $statement->execute(array(':value'=> $subchapter_id));
      $sections = $statement->fetchAll(PDO::FETCH_ASSOC);
      foreach ($sections as $section) {
        $query = "SELECT id FROM mooc_blocks WHERE parent_id = :valueSub AND type ='PortfolioBlockSupervisor' ";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(array(':valueSub'=> $section['id']));
        $supervisorNotizBloecke = $statement->fetchAll(PDO::FETCH_ASSOC);
        foreach ($supervisorNotizBloecke as $block) {
          $query = "SELECT json_data FROM mooc_fields WHERE block_id = :block_id AND name = 'content'";
          $statement = DBManager::get()->prepare($query);
          $statement->execute(array(':block_id'=> $block['id']));
          $supervisorFeedback = $statement->fetchAll();
          if (!empty($supervisorFeedback[0][json_data])) {
            return true;
          }
        }
      }
    }

    public static function isVorlage($id)
    {
        if(Course::findById($id)){
            $seminar = Seminar::getInstance($id);
            $status = $seminar->getStatus();
            if ($status == Config::get()->getValue('SEM_CLASS_PORTFOLIO_VORLAGE')){
                return true;
            }
            else return false;
        }
        else return false;
    }

     public static function getAllBlocksInOrder($id){
        $db = DBManager::get();
        $blocks = array();
        $query = "SELECT title, id FROM mooc_blocks WHERE seminar_id = :id AND type = 'Chapter' AND parent_id != '0' ORDER BY position ASC";
        $statement = $db->prepare($query);
        $statement->execute(array(':id'=> $id));
        foreach($statement->fetchAll() as $chapter){
            array_push($blocks, $chapter[id]);
            $query = "SELECT title, id FROM mooc_blocks WHERE parent_id = :id ORDER BY position ASC";
            $statement = $db->prepare($query);
            $statement->execute(array(':id'=> $chapter[id]));
            foreach($statement->fetchAll() as $subchapter){
                array_push($blocks, $subchapter[id]);
                $query = "SELECT title, id FROM mooc_blocks WHERE parent_id = :id ORDER BY position ASC";
                $statement = $db->prepare($query);
                $statement->execute(array(':id'=> $subchapter[id]));
                foreach($statement->fetchAll() as $section){
                    array_push($blocks, $section[id]);
                    $query = "SELECT title, id FROM mooc_blocks WHERE parent_id = :id ORDER BY position ASC";
                    $statement = $db->prepare($query);
                    $statement->execute(array(':id'=> $section[id]));
                    foreach($statement->fetchAll() as $block){
                        array_push($blocks, $block[id]);
                    }
                }
            }
        }
        return $blocks;
    }

    public static function sendNotificationToUser($case, $portfolio_id, $block_id, $user_id){

        $portfolio = Eportfoliomodel::findBySeminarId($portfolio_id);
        $owner = $portfolio->getOwnerFullname();
        $link = $GLOBALS['ABSOLUTE_URI_STUDIP'] . 'plugins.php/courseware/courseware?cid=' . $portfolio_id . '&selected=' . $block_id;
        $group = Course::find($portfolio->group_id)->name;

        switch ($case) {
            default:
            case 'supervisornotiz':
                $mail_subj = 'Neue Portfolio-Notiz für Supervisoren von ' . $owner;
                $mail_msg = sprintf(
                    _("Neue Notiz von '%s'\n"
                    . "in: %s \n"
                    . "Direkt zur Notiz:\n %s"),
                    $owner, Course::find($portfolio->seminar_id)->name , $link
                );
                break;
            case 'freigabe':
                $mail_subj = 'Neue Portfolio Freigabe von ' . $owner;
                $mail_msg = sprintf(
                    _("Neue Freigabe von '%s'\n"
                    . "in: %s \n"
                    . "Direkt zum freigegebenen Inhalt:\n %s"),
                    $owner, Course::find($portfolio->seminar_id)->name , $link
                );
                break;
        }

        
            $rec_uname = array();
            //id ist kein user sondern supervisorgruppe
            if(!User::find($user_id)){
                $supervisor_group_user = SupervisorGroup::find($user_id)->user;
          
                foreach ($supervisor_group_user as $group_user) {
                        $rec_uname[] = get_username($group_user->user_id);
                }
            } else $rec_uname[] = $user_id;

            $messaging = new messaging();
            $messaging->send_as_email =  true;
            $messaging->insert_message(
                $mail_msg,
                $rec_uname,
                '____%system%____',
                '',
                '',
                '',
                null,
                $mail_subj,
                "",
                'normal',
                trim(Request::get("message_tags")) ?: null
            );
        //StudipMail::sendMessage($mail, sprintf(_('Neues aus Ihrer Supervisionsgruppe "%s"'), $course->name), $mail_msg);
    }


    /**
    * Liefert die zuverbleibenden Tage (gerundet) zwischen
    * jetzt und Abgabetermin des passenden Templates
    * der Gruppe. Liefert 0 wenn das Abgabedatum überschritten wurde
    **/
    public static function getDaysLeft($group_id, $template_id){
      $deadline = EportfolioGroupTemplates::getDeadline($group_id, $template_id);
      $now = time();

      if($now < $deadline){
        $daysleft = abs($now - $deadline)/60/60/24;
        return round($daysleft, 0);
      } else {
        return 0;
      }
    }

    /**
    * Liefert die Anzahl der Kapitel in einem Template
    **/
    public static function getNumberOfChaptersFromTemplate($template_id){
      $query = "SELECT COUNT(id) FROM mooc_blocks WHERE type = 'Chapter' AND Seminar_id = :template_id";
      $statement = DBManager::get()->prepare($query);
      $statement->execute(array(':template_id' => $template_id));
      $result = $statement->fetchAll();
      return $result[0][0];
    }

    /**
    * Liefert die Anzahl der freigebenen Kapitel der Users
    * innerhalb eines verteilten Templates
    **/
    public static function getNumberOfSharedChaptersOfTemplateFromUser($template_id, $user_template_id){
      $return = 0;
      $templateChapters = Eportfoliomodel::getChapters($template_id);
      foreach ($templateChapters as $chapter) {
        $block_id = Eportfoliomodel::getUserPortfilioBlockId($user_template_id, $chapter[id]);
        if (Eportfoliomodel::checkKapitelFreigabe($block_id)) $return++;
      }
      return $return;
    }

    /**
    * Liefert Fortschritt des Users in in einem Template
    **/
    public static function getProgressOfUserInTemplate($shared, $all){
      return round($shared / $all * 100, 0);
    }

    /**
    * Liefert die Anzahl der Supervisornotizen innerhalb eines $templateStatus
    * einers Users
    **/
    public static function getNumberOfNotesInTemplateOfUser($template_id, $user_template_id){
      $return = 0;
      $templateChapters = Eportfoliomodel::getChapters($template_id);
      foreach ($templateChapters as $chapter) {
        $block_id = Eportfoliomodel::getUserPortfilioBlockId($user_template_id, $chapter[id]);
        if (Eportfoliomodel::checkSupervisorNotiz($block_id)) $return++;
      }
      return $return;
    }

    /**
    * Liefert einen CoursewareLink für das erste Kapitel eines Templates eines Users
    **/
    public static function getLinkOfFirstChapter($template_id, $seminar_id){
      $templateChapters = Eportfoliomodel::getChapters($template_id);
      $vorlagenchapter = $templateChapters[0]['id'];
      $portfolio_block_id = BlockInfo::findOneBySQL('vorlagen_block_id = :vorlagenchapter AND Seminar_id = :cid', 
              array(':cid' => $seminar_id, ':vorlagenchapter' => $vorlagenchapter));
      return URLHelper::getURL('plugins.php/courseware/courseware', array('cid' => $seminar_id, 'selected' => $portfolio_block_id->block_id));
    }
    
    public static function getLastOwnerEdit($sem_id){
      $query = "SELECT chdate FROM mooc_blocks WHERE Seminar_id = :id ORDER BY chdate DESC";
      $statement = DBManager::get()->prepare($query);
      $statement->execute(array(':id' => $sem_id));
      $result = $statement->fetchAll();
      $last_edit = $result[0][0];;
      $last_freigabe = EportfolioActivity::getLastFreigabeOfPortfolio($sem_id);
      
      return max(array($last_edit, $last_freigabe));
    }

    /**
     * TODO: Kann in createPortfolio_action evtl. eingebaut werden
     * Erstellt für einen User ein Portfolio
     * Gibt die Seminar_id des Portfolios zurück
     * **/
    public static function createPortfolioForUser($group_id, $user_id){

      $db = DBManager::get();
      $groupname  = new Seminar($group_id);
      $groupid = Course::findCurrent()->id;
      $group = EportfolioGroup::find($group_id);
      $sem_type_id = Eportfoliomodel::getPortfolioSemId();

      $owner            = User::find($user_id);
      $owner_fullname   = $owner['Vorname'] . ' ' . $owner['Nachname'];
      $sem_name         = "Gruppenportfolio: ".$groupname->getName() . " (" . $owner_fullname .")";
      $sem_description  = "Dieses Portfolio wurde Ihnen von einem Supervisor zugeteilt";
      $current_semester = Semester::findCurrent();

      $sem              = new Seminar();
      $sem->Seminar_id  = $sem->createId();
      $sem->name        = $sem_name;
      $sem->description = $sem_description;
      $sem->status      = $sem_type_id;
      $sem->read_level  = 1;
      $sem->write_level = 1;
      //$sem->setEndSemester(-1);
      //$sem->setStartSemester($current_semester->beginn);
      $sem->institut_id = Config::Get()->STUDYGROUP_DEFAULT_INST;
      $sem->visible     = 0;
      $sem_id = $sem->Seminar_id;

      /**
       * TODO: Fehler beim $this->
       * andere Möglichkeit suchen den PluginPath zu bekommen
       * **/

      //$avatar = CourseAvatar::getAvatar($sem_id);
      //$filename = sprintf('%s/%s',$this->plugin->getpluginPath(),'assets/images/avatare/eportfolio.png');
      //$avatar->createFrom($filename);

      $sem->addMember($user_id, 'dozent'); // add user to his to seminar

      /**
       * Alle Supervisoren hinzufügen
       * **/
      $supervisors = EportfolioGroup::getAllSupervisors($group_id);
      foreach($supervisors as $supervisor){
          $sem->addMember($supervisor, 'autor');
      }

      $sem->store();

      $user = new User($user_id);

      $eportfolio = new Seminar();
      $eportfolio_id = $eportfolio->createId();
      $query = "INSERT INTO eportfolio (Seminar_id, eportfolio_id, group_id, owner_id, template_id, supervisor_id) VALUES (:sem_id, :eportfolio_id, :groupid , :userid, :masterid, :groupowner)";
      $statement = $db->prepare($query);
      $statement->execute(array(':groupid'=> $group_id, ':sem_id'=> $sem_id, ':eportfolio_id'=> $eportfolio_id, ':userid'=> $user_id,  ':masterid'=> $masterid, ':groupowner'=> $groupowner));
      $query = "INSERT INTO eportfolio_user(user_id, Seminar_id, eportfolio_id, owner) VALUES (:userid, :Seminar_id , :eportfolio_id, 1)";
      $statement = $db->prepare($query);
      $statement->execute(array(':Seminar_id'=> $sem_id, ':eportfolio_id'=> $eportfolio_id, ':userid'=> $user_id));
      //delete dummy courseware chapters //TODO funktionier noch nicht
      $query = "DELETE FROM mooc_blocks WHERE seminar_id = :sem_id AND type NOT LIKE 'Courseware'";
      $statement = $db->prepare($query);
      $statement->execute(array(':sem_id'=> $sem_id));

      return $sem->Seminar_id;
    }
    
    /**
     * Gibt eine Liste mit den Template_ids zurück
     * die einem Nutzer noch nicht verteilt wurden
     * innerhalb einer Veranstaltung
    **/
    public static function getNotSharedTemplatesOfUserInGroup($group_id, $user_id, $portfolio_id){

      $return = array(); 

      $template_list = EportfolioGroupTemplates::getGroupTemplates($group_id); 
      foreach ($template_list as $template) {
        $template_chapters = Eportfoliomodel::getChapters($template);
        foreach ($template_chapters as $chapter) {
          if(!Eportfoliomodel::getUserPortfilioBlockId($portfolio_id ,$chapter['id'])){
            array_push($return, $template);
          }
        }
      } 
      
      return array_unique($return);

    }

    public static function getPortfolioSemId(){
      foreach ($GLOBALS['SEM_TYPE'] as $id => $sem_type){ //get the id of ePortfolio Seminarclass
        if ($sem_type['name'] == 'ePortfolio') {
          return $id;
        }
      }
    }

}
