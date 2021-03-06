<?

class blocksettingsController extends StudipController
{
    public function __construct($dispatcher)
    {
        parent::__construct($dispatcher);
        $this->plugin = $dispatcher->current_plugin;
    }
    
    public function before_filter(&$action, &$args)
    {
        parent::before_filter($action, $args);
    }
    
    public function index_action($cid = null)
    {
        // set vars
        $this->course  = Course::findCurrent();
        $userid        = $GLOBALS["user"]->id;
        $this->cid     = Request::option('cid');
        $this->vorlage = Eportfoliomodel::findBySeminarId($this->cid);
        
        
        # Aktuelle Seite
        PageLayout::setTitle('ePortfolio-Vorlage - Einstellungen: ' . $this->course->getFullname());
        
        //autonavigation
        Navigation::activateItem("course/blocksettings");
        
        $sidebar = Sidebar::Get();
        $sidebar->setTitle('Navigation');
        
        $views = new ViewsWidget();
        $views->setTitle('Rechte');
        $views->addLink(_('Rechteverwaltung'), '#')->setActive(true);
        Sidebar::get()->addWidget($views);
        
        //get list chapters
        $chapters = Eportfoliomodel::getChapters($this->course->id);
        
        //get viewer information
        $viewers = $this->course->getMembersWithStatus('autor');
        
        
        //push to template
        $this->userid        = $userid;
        $this->title         = $this->course->getFullname();
        $this->chapterList   = $chapters; //$arrayList;
        $this->viewerList    = $viewers; //$return_arr;
        $this->numberChapter = count($chapters);
    }
    
    public function lockBlock_action($seminar_id, $block_id, $lock)
    {
        LockedBlock::lockBlock($seminar_id, $block_id, $lock);
        $this->render_nothing();
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
