<?

class settingsController extends StudipController
{
    
    public function __construct($dispatcher)
    {
        parent::__construct($dispatcher);
        $this->plugin = $dispatcher->current_plugin;
        
        if (Request::get('action') == 'deleteUserAccess') {
            $this->deleteUserAccess($_POST['userId'], $_POST['seminar_id']);
            exit();
        }
        
        
        if ($_POST['action'] == 'setsettingsColor') {
            $this->setsettingsColor($_POST['cid'], $_POST['color']);
            exit();
        }
        
    }
    
    public function before_filter(&$action, &$args)
    {
        parent::before_filter($action, $args);
    }
    
    public function index_action($cid = null)
    {
        
        $userid          = $GLOBALS["user"]->id;
        $course          = Course::findCurrent();
        $this->isVorlage = Eportfoliomodel::isVorlage($course->id);
        $eportfolio      = Eportfoliomodel::findBySeminarId($course->id);
        
        $seminar = new Seminar($course->id);
        
        # Aktuelle Seite
        PageLayout::setTitle('ePortfolio - Zugriffsrechte: ' . $course->getFullname());
        if ($this->isVorlage) {
            PageLayout::setTitle('ePortfolio-Vorlage - Zugriffsrechte: ' . $course->getFullname());
        }
        
        //autonavigation
        Navigation::activateItem("course/settings");
        
        $sidebar = Sidebar::Get();
        $sidebar->setTitle(_('Navigation'));
        
        $views = new ViewsWidget();
        $views->setTitle(_('Rechte'));
        $views->addLink(_('Rechteverwaltung'), '#')->setActive(true);
        Sidebar::get()->addWidget($views);
        
        $chapters      = Eportfoliomodel::getChapters($course->id);
        $viewers       = $course->getMembersWithStatus('autor');
        $supervisor_id = $this->getSupervisorGroupOfPortfolio($course->id);
        
        if (Request::get('setSupervisor')) {
            $supervisorId           = $_POST["supervisorId"];
            $access_array           = ['viewer' => 0];
            $access_array_serialize = serialize($access_array);
            
            $query     = "UPDATE eportfolio SET supervisor_id = :supervisorId WHERE Seminar_id = :cid";
            $statement = DBManager::get()->prepare($query);
            $statement->execute([
                ':supervisorId' => $supervisorId,
                ':cid'          => $course->id
            ]);
            $query     = "INSERT INTO seminar_user (seminar_id, user_id, status, visible, eportfolio_access)
                    VALUES (:cid, :supervisorId, 'dozent', 1, :access_array_serialize)";
            $statement = DBManager::get()->prepare($query);
            $statement->execute([
                ':supervisorId'           => $supervisorId,
                ':cid'                    => $course->id,
                ':access_array_serialize' => $access_array_serialize
            ]);
        }
        
        
        if (Request::get('setViewer')) {
            $viewerId               = Request::get('viewerId');
            $eportfolio_access      = [];
            
            $list = $this->getCurrentChapter($course->id);
            
            foreach ($list as $key => $value) {
                $eportfolio_access[$value] = 1;
            }
            
            $json = serialize($eportfolio_access);
            
            $query     = "INSERT INTO seminar_user (seminar_id, user_id, status, visible) VALUES (:cid, :viewerId, 'autor', 1)";
            $statement = DBManager::get()->prepare($query);
            $statement->execute([':viewerId' => $viewerId, ':cid' => $course->id]);
            
            $query     = "INSERT INTO eportfolio_user (user_id, Seminar_id, eportfolio_id, status, eportfolio_access, owner)
                    VALUES (:viewerId, :cid, :eportfolio_id, 'autor', :json, 0)";
            $statement = DBManager::get()->prepare($query);
            $statement->execute([':viewerId' => $viewerId, ':cid' => $course->id, ':eportfolio_id' => $eportfolio->eportfolio_id, ':json' => $json]);
        }
        
        if (Request::get('saveChanges')) {
            $this->saveChanges();
        }
        
        $search_obj = new SQLSearch("SELECT auth_user_md5.user_id, CONCAT(auth_user_md5.nachname, ', ', auth_user_md5.vorname, ' (' , auth_user_md5.email, ')' ) as fullname, username, perms "
            . "FROM auth_user_md5 "
            . "WHERE (CONCAT(auth_user_md5.Vorname, \" \", auth_user_md5.Nachname) LIKE :input "
            . "OR CONCAT(auth_user_md5.Nachname, \" \", auth_user_md5.Vorname) LIKE :input "
            . "OR auth_user_md5.username LIKE :input)"
            . "AND auth_user_md5.user_id NOT IN "
            . "(SELECT eportfolio_user.user_id FROM eportfolio_user WHERE eportfolio_user.Seminar_id = '" . $course->id . "')  "
            . "ORDER BY Vorname, Nachname ",
            _("NUtzer suchen"), "username");
        
        $this->mp = MultiPersonSearch::get('selectFreigabeUser')
            ->setLinkText(_('Zugriffsrechte vergeben'))
            ->setTitle(_('NutzerInnen zur Verwaltung von Zugriffsrechten hinzufügen'))
            ->setSearchObject($search_obj)
            ->setExecuteURL(URLHelper::getLink('plugins.php/eportfolioplugin/settings/addZugriff/' . $course->id))
            ->render();
        
        $this->cid           = $course->id;
        $this->userid        = $userid;
        $this->title         = $course->getFullname();
        $this->chapterList   = $chapters;
        $this->viewerList    = $viewers;
        $this->numberChapter = count($chapters);
        $this->supervisorId  = $supervisor_id;
    }
    
    public function setAccess_action($user_id, $seminar_id, $chapter_id, $status)
    {
        $freigabe = new EportfolioFreigabe();
        $freigabe::setAccess($user_id, $seminar_id, $chapter_id, $status);
        $this->render_nothing();
    }
    
    /**
     * name und beschreibung speichern
     */
    public function saveChanges()
    {
        $query     = "UPDATE seminare SET Name = :change_name, Beschreibung = :change_beschreibung WHERE Seminar_id = :cid";
        $statement = DBManager::get()->prepare($query);
        $statement->execute(
            [':change_name'         => Request::get('name'),
             ':cid'                 => Request::get('cid'),
             ':change_beschreibung' => Request::get('Beschreibung')
            ]);
    }
    
    public function getCurrentChapter($id)
    {
        return DBManager::get()->fetchAll(
            "SELECT id FROM mooc_blocks WHERE seminar_id = :id AND type = 'Chapter'",
            [':id' => $id]
        );
    }
    
    /**
     * TOTO refactoring gehört in ePortfoliomodel
     * @param $id
     * @return bool
     */
    public function getSupervisorGroupOfPortfolio($id)
    {
        $portfolio = Eportfoliomodel::findBySeminarId($id);
        if ($portfolio->group_id) {
            $portfoliogroup = EportfolioGroup::findbySQL('seminar_id = :id', [':id' => $portfolio->group_id]);
        }
        if ($portfoliogroup[0]->supervisor_group_id) {
            return $portfoliogroup[0]->supervisor_group_id;
        } else {
            return false;
        }
    }
    
    
    public function addZugriff_action($id)
    {
        $mp            = MultiPersonSearch::load('selectFreigabeUser');
        $seminar       = new Seminar($id);
        $eportfolio    = Eportfoliomodel::findBySeminarId($id);
        $eportfolio_id = $eportfolio->eportfolio_id;
        $userRole      = 'autor';
        
        $db        = DBManager::get();
        $query     = "INSERT INTO eportfolio_user (user_id, Seminar_id, eportfolio_id, status, owner)
                    VALUES (:userId, :id, :eportfolio_id, 'autor', 0)";
        $statement = $db->prepare($query);
        
        
        foreach ($mp->getAddedUsers() as $userId) {
            $seminar->addMember($userId, $userRole);
            $statement->execute([':id' => $id, ':userId' => $userId, ':eportfolio_id' => $eportfolio_id]);
        }
        
        $this->redirect('settings/index/' . $id);
    }
    
    public function deleteUserAccess($userId, $cid)
    {
        $seminar       = new Seminar($cid);
        $eportfolio    = Eportfoliomodel::findBySeminarId($cid);
        $eportfolio_id = $eportfolio->eportfolio_id;
        
        $seminar->deleteMember($userId);
        
        $query     = "DELETE FROM eportfolio_user WHERE user_id = :userId AND seminar_id = :cid AND eportfolio_id = :eportfolio_id";
        $statement = DBManager::get()->prepare($query);
        $statement->execute([':cid' => $cid, ':userId' => $userId, ':eportfolio_id' => $eportfolio_id]);
    }
    
    
    public function setsettingsColor($cid, $color)
    {
        $newArray          = [];
        $newArray['color'] = $color;
        $newArray          = json_encode($newArray);
        $query             = "UPDATE eportfolio SET settings = :newArray WHERE seminar_id = :cid";
        $statement         = DBManager::get()->prepare($query);
        $statement->execute([':cid' => $cid, ':newArray' => $newArray]);
    }
    
    public function getsettingsColor()
    {
        $query     = "SELECT settings FROM eportfolio WHERE seminar_id = :cid";
        $statement = DBManager::get()->prepare($query);
        $statement->execute([':cid' => Request::get('cid')]);
        $color = json_decode($statement->fetchAll()[0][0]);
        return $color->color;
    }
    
    public function eigenesPortfolio($cid)
    {
        $query     = "SELECT template_id FROM eportfolio WHERE seminar_id = :cid";
        $statement = DBManager::get()->prepare($query);
        $statement->execute([':cid' => $cid]);
        if (empty($statement->fetchAll()[0][0])) {
            return true;
        } else {
            return false;
        }
    }
    
    public function checkIfOwner($userId, $cid)
    {
        $query     = "SELECT status FROM seminar_user WHERE user_id = :userId AND seminar_id = :cid";
        $statement = DBManager::get()->prepare($query);
        $statement->execute([':cid' => $cid, ':userId' => $userId]);
        if ($statement->fetchAll()[0][0] == "dozent") {
            return true;
        } else {
            return false;
        }
    }
    
    
    public function url_for($to = '')
    {
        $args   = func_get_args();
        $params = [];
        if (is_array(end($args))) {
            $params = array_pop($args);
        }
        $args    = array_map('urlencode', $args);
        $args[0] = $to;
        
        return PluginEngine::getURL($this->dispatcher->current_plugin, $params, join('/', $args));
    }
}
