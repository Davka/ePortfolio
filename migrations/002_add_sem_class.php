<?

require __DIR__ . '/../vendor/autoload.php';

class AddSemClass extends Migration
{
    public function description()
    {
        return 'add SemClass and SemTypes whos courses have this plugin in their overview slot';
    }
    
    
    public function up()
    {
        $id = $this->insertSemClass();
    }
    
    public function down()
    {
        $db = DBManager::get();
        
        //remove entry in sem_classes
        $name      = "ePortfolio";
        $statement = $db->prepare("DELETE FROM sem_classes WHERE name = ?");
        $statement->execute([$name]);
        
        //remove entry in sem_types
        $nameType  = "ePortfolio";
        $statement = $db->prepare("DELETE FROM sem_types WHERE name = ?");
        $statement->execute([$nameType]);
    }
    
    
    private function insertSemClass()
    {
        $db       = DBManager::get();
        $name     = "ePortfolio";
        $nameType = "ePortfolio";
		$id = -2;	
        
        if ($this->validateUniqueness($name)) {
            $statement = $db->prepare("INSERT INTO sem_classes SET name = ?, mkdate = UNIX_TIMESTAMP(), chdate = UNIX_TIMESTAMP()");
            $statement->execute([$name]);
            $id = $db->lastInsertId();
            
            //Insert sem_type
            $statementSemTypes = $db->prepare("INSERT INTO sem_types SET name = ?, class = $id, mkdate = UNIX_TIMESTAMP(), chdate = UNIX_TIMESTAMP()");
            $statementSemTypes->execute([$nameType]);
            $type_id = $db->lastInsertId();
            
            Config::get()->create('SEM_CLASS_PORTFOLIO', [
                'value'       => $type_id,
                'is_default'  => 0,
                'type'        => 'integer',
                'range'       => 'global',
                'section'     => 'global',
                'description' => 'ID der Veranstaltungsklasse für Portfolios'
            ]);
            
        } else {
            // We already got a type with that name, should be a previous installation ...
            $statement = $db->prepare('SELECT id FROM sem_classes WHERE name = ?');
            $statement->execute([$name]);
            $id = $statement->fetchColumn();
        }
        
        if ($id === -2) {
            $message = sprintf('Ungültige id (id=%d)', $id);
            throw new Exception($message);
        }
        
        
        $sem_class = SemClass::getDefaultSemClass();
        $sem_class->set('name', $name);
        $sem_class->set('id', $id);
        $sem_class->set('studygroup_mode', '1');
        $sem_class->set('default_read_level', 1);
        $sem_class->set('default_write_level', 1);
        $sem_class->set('course_creation_forbidden', 1);
        $sem_class->set('admission_type_default', 3);
        
        // Setting Mooc-courses default datafields: mooc should not to be disabled, courseware and mooc should be active
        $current_modules                                        = $sem_class->getModules(); // get modules
        $current_modules['EportfolioPlugin']['activated']       = '1';
        $current_modules['EportfolioPlugin']['sticky']          = '1';
        $current_modules['Courseware']['activated']             = '1';   // set values
        $current_modules['Courseware']['sticky']                = '1'; // sticky = 1 -> can't be chosen in "more"-field of course
        $current_modules['CoreParticipants']['activated']       = '0';
        $current_modules['CoreParticipants']['sticky']          = '1';
        $current_modules['CoreDocuments']['activated']          = '1';
        $current_modules['CoreDocuments']['sticky']             = '1';
        $current_modules['CoreForum']['activated']              = '0';
        $current_modules['CoreForum']['sticky']                 = '1';
        $current_modules['CoreOverview']['activated']           = '0';
        $current_modules['CoreOverview']['sticky']              = '1';
        $current_modules['CoreAdmin']['activated']              = '0';
        $current_modules['CoreAdmin']['sticky']                 = '1';
        $current_modules['CoreSchedule']['activated']           = '0';
        $current_modules['CoreSchedule']['sticky']              = '1';
        $current_modules['CoreWiki']['activated']               = '0';
        $current_modules['CoreWiki']['sticky']                  = '1';
        $current_modules['CoreElearningInterface']['activated'] = '0';
        $current_modules['CoreElearningInterface']['sticky']    = '1';
        $current_modules['CoreResources']['activated']          = '0';
        $current_modules['CoreResources']['sticky']             = '1';
        $current_modules['CoreLiterature']['activated']         = '0';
        $current_modules['CoreLiterature']['sticky']            = '1';
        $current_modules['VipsPlugin']['activated']             = '0';
        $current_modules['VipsPlugin']['sticky']                = '1';
        
        $sem_class->set('overview', 'EportfolioPlugin');
        $sem_class->setModules($current_modules); // set modules
        
        $sem_class->store();
        
        return $id;
    }
    
    private function validateUniqueness($name)
    {
        $statement = DBManager::get()->prepare('SELECT id FROM sem_classes WHERE name = ?');
        $statement->execute([$name]);
        if ($old = $statement->fetchColumn()) {
            // $message = sprintf('Es existiert bereits eine Veranstaltungskategorie mit dem Namen "%s" (id=%d)', htmlspecialchars($name), $old);
            // throw new Exception($message);
            return false;
        }
        return true;
    }
    
    private function removeSemClassAndTypes($id)
    {
        $sem_class = new SemClass(intval($id));
        $sem_class->delete();
        $GLOBALS['SEM_CLASS'] = SemClass::refreshClasses();
    }
}
