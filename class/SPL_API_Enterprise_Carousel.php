<?php


require_once('base/SPL_DB.php');

class SPL_API_Enterprise_Carousel extends SPL_DB {

  var $apikey;
  var $method;
  var $params;
  var $config;


  function __construct($config=null, $request=null, $apikey=null) {
    
    if ( is_array($config) ) {
      $this->config = $config;

      if ( isset($config['api']['connect']) 
        && isset($config['api']['web_user']) 
        && isset($config['api']['web_pass']) ) { 
        
        parent::__construct( $config['api']['connect']
                            ,$config['api']['web_user']
                            ,$config['api']['web_pass']
                            );
      }
      
      if ( is_array($request) ) {
        $this->params = $request['params'];
        $this->method = $request['method'];
      }

      $this->apikey = $apikey;
      
    }

    $this->initApi();
  }
  
  private function initApi() {


  }

  public function getApiRequest() {
    //return $this->method;
    return $this->getBrowseGroup(); 

  }

  protected function getBrowseGroup() {
    $count = 5;
    $apis = array(
                  'star-fiction'=>array('label'=>'Fiction', 'icon'=>'book', 'link'=>'/browse/star-fiction/')
                , 'star-non-fiction'=>array('label'=>'Non-Fiction', 'icon'=>'book', 'link'=>'/browse/star-non-fiction/')
                , 'dvd-new'=> array('label'=>'DVDs', 'icon'=>'play-circle', 'link'=>'/browse/dvd-new/')
                , 'music'=>array('label'=>'Music', 'icon'=>'music', 'link'=>'/browse/music/')
                );
    
    $lists = array();
    foreach ( $apis as $api=>$title ) { 
      $list = $this->getCarouselBrowseList($count, $api);
      if ( is_array( $list ) ) {
        $lists[$api] = $list; 
      }
    }

    for ($i=0; $i<$count; $i++ ) {
      $slide = new stdClass();
      $slide->format = 'browse-group';
      $slide->position = $i;
      foreach ( $apis as $api=>$detail ) { 
        $slide->list[$api]['meta'] = $apis[$api];
        $slide->list[$api]['item'] = $lists[$api][$i]; 
      }
      $slides[] = $slide;
    }

    if ( is_array($slides) ) {
      return $slides;
    }
  }

  protected function getCarouselBrowseList($limit=4, $api='star') {
    $list = SPL_API::curlPostProxy('http://api.spokanelibrary.org/browse/'.$api);
    //$list = SPL_API::curlPostProxy('http://api.spokanelibrary.org/new/?menu='.$api);
    $list = json_decode($list);
    if ( is_array($list->titles) ) {
      $slides = array();
      $titles = array_slice($list->titles, 0, $limit*2);
      unset($list);
      foreach ( $titles as $title ) {
        //if ( !empty($title->summary) && ( !empty($title->isbn) || !empty($title->upc) ) ) {
          if ( !empty($title->upc) ) {
            $title->upc = str_pad($title->upc, 12, '0', STR_PAD_LEFT); 
          }
          if ( !empty($title->upc) ) {
            $ebsco = $title->upc;
          } elseif ( !empty($title->isbn) ) {
            $ebsco = $title->isbn;
          }
          $slide = new stdClass();
          $slide->format = 'browse';
          $slide->title = $this->getCarouselExcerpt($title->title, 55, true);
          $slide->author = rtrim($title->author,',');
          $slide->img = 'http://contentcafe2.btol.com/ContentCafe/jacket.aspx?UserID=ebsco-test&Password=ebsco-test&Return=T&Type=M&Value='.$ebsco;
          $slide->url = '/bib/'.$title->bib.'/';
          $slide->bib = $title->bib;
          $slide->content = $this->getCarouselExcerpt($title->summary, 75);
          $slide->isbn = $title->isbn;
          $slide->upc = $title->upc;
          $slides[] = $slide;
        //}
      }
    }

    if ( is_array($slides) ) {
      $slides = array_slice($slides, 0, $limit);

      return $slides;
    }
  }

  protected function getCarouselExcerpt($content, $chars=175, $clean=false, $elide='&hellip;') {
    if ( $clean ) {
      // http://stackoverflow.com/questions/20805286/php-remove-text-between-and-parentheses
      $content = preg_replace('#\s*\[.+\]\s*#U', '', $content); 
    }
    if ( strlen($content) > $chars) {   
      $str = substr($content,0,$chars);
      $str = substr($str,0,strrpos($str,' '));
      if (substr($str, -1) != '.' && substr($str, -1) != '!') {
        $str = $str.$elide;
      }
    } else {
      $str = $content;
    }

    return $str;
  }

} // CLASS

?>
