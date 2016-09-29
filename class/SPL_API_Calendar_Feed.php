<?php


//require_once('base/SPL_DB.php');

class SPL_API_Calendar_Feed extends SPL_API {

  var $apikey;
  var $method;
  var $params;
  var $config;

  function __construct($config=null, $request=null, $apikey=null) {
    
    if ( is_array($config) ) {
      $this->config = $config;

      if ( is_array($request) ) {
        $this->params = $request['params'];
        $this->method = $request['method'];
      }

      $this->apikey = $apikey;
      
    }

    $this->initApi();
  }
  
  private function initApi() {
    //phpinfo();

  }

  public function getApiRequest() {
    $limit = 10;
    $proxylist_rss = file_get_contents('http://www.trumba.com/calendars/spl-web-feed.rss');
    $feed = simplexml_load_string($proxylist_rss);
    $i = 1;
    foreach ($feed->channel->item  as $item){
      if ( $i <= $limit ) {
        $namespaces = $item->getNameSpaces(true);

        $event = array();
        $event['title'] = $item->title;
        $event['link'] = $item->link;
        //$event['description'] = $item->description;
        
        $trumba = $item->children($namespaces['x-trumba']);
        //$event['trumba'] = $trumba;
        if ( $trumba->customfield[4] ) {
          $event['image'] = $trumba->customfield[4];
        }
        $event['datetime'] = $trumba->formatteddatetime;

        $dt_start = new DateTime($trumba->localstart);
        $event['date'] = $dt_start->format('l, F j');
        $event['ordinal'] = $dt_start->format('S');
        $time = explode(',', $trumba->formatteddatetime);
        $event['time'] = trim(end($time));

        $xcal = $item->children($namespaces['xCal']);
        //$event['xcal'] = $xcal;
        $event['location'] = $xcal->location;
        $event['description'] = $xcal->description;

        $calendar[] = $event;
      }
      $i++;
    }

    return $calendar;
  }


} // CLASS

?>
