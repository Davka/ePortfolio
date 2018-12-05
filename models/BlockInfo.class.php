<?

/**
 * @author  <asudau@uos.de>
 *
 * @property string $Seminar_id (ePortfolio)
 * @property string $block_id
 * @property string $vorlagen_block_id
 * @property boolean $blocked
 * @property int $mkdate
 * @property int $chdate
 */
class BlockInfo extends SimpleORMap
{
    
    public $errors = [];
    
    /**
     * Give primary key of record as param to fetch
     * corresponding record from db if available, if not preset primary key
     * with given value. Give null to create new record
     *
     * @param mixed $id primary key of table
     */
    public function __construct($id = null)
    {
        
        $this->db_table = 'eportfolio_block_infos';
        
        parent::__construct($id);
    }
    
    /**
     * Use as constructor
     * is used by VorlagenCopy, when students get their own copy of a courseware
     *
     * @param string $portfolio_id
     * @param string $block_id
     * @param string $vorlagen_block_id
     */
    public static function createEntry($portfolio_id, $block_id, $vorlagen_block_id)
    {
        $entry                    = new self($block_id);
        $entry->vorlagen_block_id = $vorlagen_block_id;
        $entry->Seminar_id        = $portfolio_id;
        $entry->mkdate            = time();
        if ($entry->store()) {
            return true;
        } else return false;
    }
    
    /**
     * check if a given Mooc-Block is marked as locked
     *
     * @param string $block_id
     */
    public static function isLocked($block_id)
    {
        $entry = self::findById($block_id);
        if ($entry->blocked) {
            return true;
        } else return false;
    }
    
    /**
     * get corresponding Mooc-Block for a PortfolioVorlage in a students portfolio
     *
     * @param string $portfolio_id
     * @param string $block_id
     * @param string $vorlagen_block_id
     */
    public static function getPortfolioBlockByVorlagenID($block_id, $portfolio_id)
    {
        $entry = self::findBySQL('block_id = :block_id AND Seminar_id = :portfolio_id', [':block_id' => $block_id, 'portfolio_id' => $portfolio_id]);
        if ($entry && $entry->block_id) {
            return $entry->block_id;
        } else return false;
    }
}
