<?

class SupervisorgroupController extends StudipController
{
    var $id = null;
    
    public function __construct($dispatcher)
    {
        parent::__construct($dispatcher);
        $this->plugin = $dispatcher->current_plugin;
        $this->id     = Request::get('cid');
        $this->createSidebar();
        $this->checkGetId();
    }
    
    public function before_filter(&$action, &$args)
    {
        parent::before_filter($action, $args);
    }
    
    public function index_action()
    {
        $group         = new Supervisorgroup($this->id);
        $this->title   = $group->getName();
        $this->groupId = $group->getId();
        $this->linkId  = $this->id;
        
        $this->mp = MultiPersonSearch::get('supervisorgroupSelectUsers')
            ->setLinkText(_('Supervisoren hinzufügen'))
            ->setTitle(_('Personen zur Supervisorgruppe hinzufügen'))
            ->setSearchObject(new StandardSearch('user_id'))
            ->setJSFunctionOnSubmit()
            ->setExecuteURL(URLHelper::getLink('plugins.php/eportfolioplugin/supervisorgroup/addUser', []))
            ->render();
        
        $this->usersOfGroup = $group->getUsersOfGroup();
    }
    
    private function createSidebar()
    {
        $sidebar = Sidebar::Get();
        $sidebar->setTitle('Supervisorgruppen');
        
        $navcreate = new LinksWidget();
        $navcreate->setTitle('Supervisorgruppen');
        $attr = ["onclick" => "showModalNewSupervisorGroupAction()"];
        $navcreate->addLink(_('Neue Gruppe anlegen'), "#", null, $attr);
        
        $navgroups = new LinksWidget();
        $navgroups->setTitle(_('Supervisorgruppen'));
        foreach ($this->getSupervisorgroups() as $group) {
            $navgroups->addLink($group[name], "supervisorgroup?id=" . $group['id']);
        }
        
        $sidebar->addWidget($navcreate);
        $sidebar->addWidget($navgroups);
    }
    
    private function getSupervisorgroups()
    {
        return DBManager::get()->query("SELECT * FROM supervisor_group")->fetchAll();
    }
    
    private function checkGetId()
    {
        if (Request::get('id') == null) {
            $this->id = $this->getFirstGroupId();
        }
    }
    
    private function getFirstGroupId()
    {
        $query = DBManager::get()->fetch("SELECT id FROM supervisor_group")->fetchAll();
        return $query[0]['id'];
    }
    
    public function addUser_action($group)
    {
        $mp    = MultiPersonSearch::load('supervisorgroupSelectUsers');
        $group = new SupervisorGroup($group);
        foreach ($mp->getAddedUsers() as $key) {
            $group->addUser($key);
        }
        //$this->render_nothing();
        $this->redirect($this->url_for('showsupervisor/supervisorgroup/' . $group->eportfolio_group->seminar_id), ['cid' => $group->eportfolio_group->seminar_id]);
    }
    
    public function deleteUser_action($group_id, $user_id)
    {
        $group = new Supervisorgroup($group_id);
        $group->deleteUser($user_id);
        $this->redirect($this->url_for('showsupervisor/supervisorgroup/' . $group->eportfolio_group->seminar_id), ['cid' => $group->eportfolio_group->seminar_id]);
    }
    
    public function newGroup_action()
    {
        $name = $_POST['groupName'];
        Supervisorgroup::newGroup($name);
    }
    
    public function deleteGroup_action()
    {
        $id = $_GET['cid'];
        Supervisorgroup::deleteGroup($id);
    }
    
    public function url_for($to = '')
    {
        $args = func_get_args();
        
        # find params
        $params = [];
        if (is_array(end($args))) {
            $params = array_pop($args);
        }
        
        # urlencode all but the first argument
        $args    = array_map('urlencode', $args);
        $args[0] = $to;
        
        return PluginEngine::getURL($this->dispatcher->current_plugin, $params, join('/', $args));
    }
}
