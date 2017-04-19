  <?php

class ShowsupervisorController extends StudipController {

    public function __construct($dispatcher)
    {
        parent::__construct($dispatcher);
        $this->plugin = $dispatcher->plugin;
        $user = get_username();
        $id = $_GET["id"];
        $this->id = $id;
        //userData for Modal

        if($_POST["type"] == 'addTemp'){
          $this->addTempToDB();
          exit();
        }
        if($_POST["type"] == 'createPortfolio'){
          $this->createPortfolio();
          exit();
        }



        //sidebar
        $sidebar = Sidebar::Get();
        Sidebar::Get()->setTitle('Supervisionsansicht');

        $nav = new LinksWidget();
        $nav->setTitle(_('Supervisionsgrupppen'));
        $groups = $this->getGroups($GLOBALS["user"]->id);
        foreach ($groups as $key) {
          $seminar = new Seminar($key);
          $name = $seminar->getName();
          if($_GET['id'] == $key){
            $attr = array('class' => 'active-link');
          } else {
            $attr = array('class' => '');
          }

          $nav->addLink($name, "showsupervisor?id=".$key, null, $attr);
        }

        $navcreate = new LinksWidget();
        $navcreate->setTitle('Erstellen');

        $attr = array("data-toggle"=>"modal", "data-target" => "#myModal");
        $navcreate->addLink("Neue Gruppe anlegen", "#", "", $attr);

        $sidebar->addWidget($nav);
        $sidebar->addWidget($navcreate);

    }

    public function before_filter(&$action, &$args)
    {
        parent::before_filter($action, $args);
        $this->set_layout($GLOBALS['template_factory']->open('layouts/base.php'));
    }

    public function index_action()
    {

      if($_GET["create"]){
        $this->createSupervisorGroup($GLOBALS["user"]->id, $_POST["name"], $_POST["description"]);
      }

      $id = $_GET["id"];
      $this->id = $id;

      if(!$id == ''){
        $check = DBManager::get()->query("SELECT owner_id FROM eportfolio_groups WHERE seminar_id = '$id'")->fetchAll();

        //check permission
        if(!$check[0][0] == $GLOBALS["user"]->id){
          throw new AccessDeniedException(_("Sie haben keine Berechtigung"));
        } else {
          $this->groupList = $this->getGroupMember($id);

        }
      } else {

      }

      $this->userid = $GLOBALS["user"]->id;

      //not working MultiPersonSearch
      $mp = MultiPersonSearch::get('eindeutige_id')
        ->setLinkText(_('Person hinzufügen'))
        ->setTitle(_('Person zur Gruppe hinzufügen'))
        ->setExecuteURL($this->url_for('controller'))
        ->render();

      $this->mp = $mp;

      $this->url = $_SERVER['REQUEST_URI'];

    }

    public function getCourseBeschreibung($cid){

      $db = DBManager::get();
      $query = $db->query("SELECT Beschreibung FROM seminare WHERE Seminar_id = '$cid'")->fetchAll();
      return $query[0][Beschreibung];

    }

    public function countViewer($cid) {

      $query = DBManager::get()->query("SELECT  COUNT(Seminar_id) FROM eportfolio_user WHERE Seminar_id = '$cid' AND owner = 0")->fetchAll();
      echo $query[0][0];

    }

    public function createSupervisorGroup($owner, $title, $text) {

      $course = new Seminar();
      $id = $course->getId();
      $course->store();
      $course->addMember($owner, 'dozent', true);

      $edit = new Course($id);
      $edit->visible = 0;
      $edit->store();

      DBManager::get()->query("UPDATE seminare SET Name = '$title', Beschreibung = '$text', status = 142 WHERE Seminar_id = '$id' ");
      DBManager::get()->query("INSERT INTO eportfolio_groups (seminar_id, owner_id) VALUES ('$id', '$owner')");

      echo $id;
      die();

    }

    public function getGroups($id) {

      $q = DBManager::get()->query("SELECT seminar_id FROM eportfolio_groups WHERE owner_id = '$id'")->fetchAll();
      $array = array();
      foreach ($q as $key) {
        array_push($array, $key[0]);
      }
      return $array;

    }

    public function getGroupMember($cid) {

      $q = DBManager::get()->query("SELECT user_id FROM eportfolio_groups_user WHERE Seminar_id = '$cid'")->fetchAll();
      $array = array();
      foreach ($q as $key) {
        array_push($array, $key[0]);
      }
      return $array;

    }

    public function getCourseName($id) {
      $q = DBManager::get()->query("SELECT Name FROM seminare WHERE Seminar_id = '$id'")->fetchAll();
      return $q[0][0];
    }

    public function getTemplates($id){
      $q = DBManager::get()->query("SELECT id, temp_name, description FROM eportfolio_templates WHERE group_id = '$id'")->fetchAll();
      return $q;
    }

    public function addTempToDB(){
      $groupid = $_POST["groupid"];
      $tempid = $_POST["tempid"];
      $q = DBManager::get()->query("SELECT templates FROM eportfolio_groups WHERE seminar_id = '$groupid'")->fetchAll();
      if(empty($q[0][0])){
        $array = array($tempid);
        $array = json_encode($array);
        DBManager::get()->query("UPDATE eportfolio_groups SET templates = '$array' WHERE seminar_id = '$groupid'");
      } else {
        $array = json_decode($q[0][0]);
        if(in_array($tempid, $array)){
          echo "already";
          exit();
        }
        array_push($array, $tempid);
        $array = json_encode($array);
        DBManager::get()->query("UPDATE eportfolio_groups SET templates = '$array' WHERE seminar_id = '$groupid'");
      }

    //  print_r($array);
      //DBManager::get()->query("UPDATE eportfolio_groups SET templates = '$array' WHERE seminar_id = '$groupid'");
    }

    public function getGroupTemplates($id){
      $q = DBManager::get()->query("SELECT templates FROM eportfolio_groups WHERE seminar_id = '$id'")->fetchAll();
      $q = json_decode($q[0][0], true);
      return $q;
    }

    public function getTemplateName($id){
      $q = DBManager::get()->query("SELECT temp_name FROM eportfolio_templates WHERE id = '$id'")->fetchAll();
      $array = array();
      return $q[0][0];
    }

    public function generateRandomString($length = 32) {
      $characters = '0123456789abcdefghijklmnopqrstuvwxyz';
      $charactersLength = strlen($characters);
      $randomString = '';
      for ($i = 0; $i < $length; $i++) {
          $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
      return $randomString;
    }

    public function createPortfolio($id){
      $member = $this->getGroupMember($_POST["groupid"]);
      foreach ($member as $key => $value) {

          $userid = $value;
          $Seminar_id = $this->generateRandomString();
          $eportfolio_id = $this->generateRandomString();
          $Institut_id = "7a4f19a0a2c321ab2b8f7b798881af7c";
          $VeranstaltungsNummer = "25252525";
          $name = $this->getTemplateName($_POST["tempid"]);
          $status = 124;
          $Beschreibung = $_POST[text];
          $Lesezugriff = 1;
          $Schreibzugriff = 1;
          $start_time = 1459461600;
          $duration_time = 0;

          $statususer = "dozent";
          $bind_calendar = 1;
          $visibleuser = "yes";
          $position = 0;
          $gruppe = 5;
          $notification = 0;
          $bind_calendar = 1;

          $id = $_POST["tempid"];

          $db = DBManager::get();
          $result = $db->query("INSERT INTO seminare (Seminar_id, VeranstaltungsNummer, Institut_id, Name, status, Beschreibung, Lesezugriff, Schreibzugriff, start_time, duration_time, mkdate, chdate) VALUES ('$Seminar_id', '$VeranstaltungsNummer', '$Institut_id', '$name', '$status', '$Beschreibung', '$Lesezugriff', '$Schreibzugriff', '$start_time', '$duration_time', 'UNIX_TIMESTAMP()', 'UNIX_TIMESTAMP()'); ");
          $resultuser = $db->query("INSERT INTO seminar_user (Seminar_id, user_id, status, position, gruppe, notification, visible, bind_calendar, mkdate) VALUES ('$Seminar_id', '$userid', '$statususer', '$position', '$gruppe', '$notification', '$visibleuser', '$bind_calendar', 'UNIX_TIMESTAMP()');");

          $result_eportfolioTable = $db->query("INSERT INTO eportfolio (Seminar_id, eportfolio_id, owner_id, template_id) VALUES ('$Seminar_id', '$eportfolio_id', '$userid', '$id'); ");

          $db->query("INSERT INTO eportfolio_user (user_id, Seminar_id, eportfolio_id, owner) VALUES ('$userid', '$Seminar_id' , '$eportfolio_id', 1)");

          // $deleteCoursewareStandard = $db->query("DELETE FROM mooc_blocks WHERE type != 'Courseware' AND seminar_id = '".$Seminar_id."';");
          $createCoursewareTemplate = $db->query("SELECT * FROM mooc_blocks WHERE type = 'Courseware' AND seminar_id = '".$Seminar_id."'; ")->fetchAll();
          foreach ($createCoursewareTemplate as $block) {
            $block_id = $block[id];

          }
      }
    }

}
