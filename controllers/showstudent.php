<?

use Mooc\Container;

class ShowstudentController extends StudipController
{

    public function __construct($dispatcher)
    {
        parent::__construct($dispatcher);
        $this->plugin = $dispatcher->current_plugin;
    }

    public function before_filter(&$action, &$args)
    {
        parent::before_filter($action, $args);

        PageLayout::setTitle(_(Context::getHeaderLine() . ' - Übersicht'));

        Navigation::activateItem("course/eportfolioplugin");
    }

    public function index_action()
    {
        $this->group_id = Context::getId();
        $this->userid = $GLOBALS["user"]->id;

        $this->portfolio_id   = EportfolioGroup::getPortfolioIdOfUserInGroup($this->userid, $this->group_id);
        $this->groupTemplates = EportfolioGroupTemplates::getGroupTemplates($this->group_id);
    }

    public function createlateportfolio_action($group_id, $user_id)
    {
        $portfolio_id = EportfolioGroup::getPortfolioIdOfUserInGroup($user_id, $group_id);
        if (!$portfolio_id) {
            /**
             * Der User hat noch kein Portfilio
             * in die das Template importiert werden kann
             * **/
            $portfolio_id = EportfolioModel::createPortfolioForUser($group_id, $user_id, $this->dispatcher->current_plugin);
            $portfolio_id = $portfolio_id;

            $template_list_not_shared = EportfolioGroupTemplates::getGroupTemplates($group_id);

        } else {

            $portfolio_id = $portfolio_id[0];
            /**
             * Welche Templates wurden dem Nutzer noch nicht Verteilt?
             * **/
            $template_list_not_shared = EportfolioModel::getNotSharedTemplatesOfUserInGroup($group_id, $user_id, $portfolio_id);
        }

        /**
         * Jedes Template in der Liste verteilen
         * **/
        foreach ($template_list_not_shared as $current_template_id) {
            VorlagenCopy::copyCourseware(new Seminar($current_template_id), [$user_id => $portfolio_id]);
            EportfolioActivity::addVorlagenActivity($group_id, User::findCurrent()->id);
        }

        PageLayout::postMessage(MessageBox::success('Portfolio wurde erstellt'));
        $this->redirect('showstudent?cid=' . $group_id);
    }
}
