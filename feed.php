<?php

require '../inc_0700/config_inc.php'; #provides configuration, pathing, error handling, db credentials
spl_autoload_register('MyAutoLoader::NamespaceLoader');//required to load SurveySez namespace objects
$config->metaRobots = 'no index, no follow';#never index feed pages
//if the id is set on the querystring, display, else - redirect
if(isset($_GET['id']) && (int)$_GET['id'] > 0){#proper data must be on querystring
    
    showFeeds();
    
}else{
	myRedirect(VIRTUAL_PATH . "RSS/subcategories.php");
}
function showFeeds()
{
    startSession();
	//Expire the session if user is inactive for 2
	//minutes or more.
	$expireAfterSeconds = 2 * 60;
 
	//Check to see if our "last action" session
	//variable has been set.
	if(isset($_SESSION['FeedURL'])){
    
    //Figure out how many seconds have passed
    //since the user was last active.
    $secondsInactive = time() - $_SESSION['FeedURL'];
    
    //Check to see if they have been inactive for too long.
    if($secondsInactive >= $expireAfterSeconds){
        //User has been inactive for too long.
        //Kill their session.
        session_unset();
        session_destroy();
    	}   
	}
	//Assign the current timestamp as the user's
	//latest activity
	$_SESSION['FeedURL'] = time();
	
    // begin sql call to process rss feed
    $myID = (int)$_GET['id']; #Convert to integer, will equate to zero if fails
    $sql = "select FeedURL from p4_feedURLs where FeedID=" . $myID;
    # connection comes first in mysqli (improved) function
    $result = mysqli_query(IDB::conn(),$sql) or die(trigger_error(mysqli_error(IDB::conn()), E_USER_ERROR));
    if(!isset($_SESSION['Feeds'])){$_SESSION['Feeds'] = array();
                                  
    while($row = mysqli_fetch_assoc($result)){# process SQL
        // pulls the RSS feed link if not cached
        $request = dbOut($row['FeedURL']);
        $TimeDate = date("Y-m-d H:i:s"); 
        
        //populate the object array with a new instance of Feed class
        $_SESSION['Feeds'][] = new Feed($myID, $request, $TimeDate);     
        
        //dumpDie($_SESSION['Feeds']);
        //^currently returns an array of objects 
        } 
    @mysqli_free_result($result);// end sql call
                                   
 }else if(isset($_SESSION['Feeds'])){
        
        foreach($_SESSION['Feeds'] as $Feed){
            //foreach processes the array of Feed objects and if the FeedID matches the ID stored in a session
            // displays the XML from the SESSION cache not db 
            $FeedID = $Feed->myID;
            $TimeStamp = $Feed->TimeDate;
            // add TimeDate compare here
                if ($FeedID == $myID){
                $request = $Feed->Description;
                }//end if IDs match
                else {
                    while($row = mysqli_fetch_assoc($result)){# process SQL
                        // pulls the RSS feed link if not cached
                        $request = dbOut($row['FeedURL']);
                        $TimeDate = date("Y-m-d H:i:s");
                        //populate the object array with a new instance of Feed class
                        $_SESSION['Feeds'][] = new Feed($myID, $request, $TimeDate);     
                    }//end while db loop 
                     
                }//end else            
            }//end foreach
            @mysqli_free_result($result);// end sql call
        }//end elseif isset
    
// takes the contents of the xml file and loads them
$response = file_get_contents($request);
$xml = simplexml_load_string($response);    

// process through the array of stories and display link+title+description
get_header();
foreach($xml->channel->item as $story)
  {
    echo '<a href="' . $story->link . '"><h3>' . $story->title . '</h3></a>
    <p>' . $story->pubDate . '<br />
    <img src="' . $story->image . '">' . $story->description . '</p>';
  }     
}//end showFeeds()
class Feed
{
    public $myID = 0;
    public $Description = '';
    public $TimeDate = '';
    
    public function __construct($myID, $Description, $TimeDate)
    {
        $this->myID = $myID;
        $this->Description = $Description;
        $this->TimeDate = $TimeDate;
        
    }//end Feed constructor
}//end feed class
get_footer();
?>
