<?php
namespace App\Controller;

class flickr_gallery
{
	protected $settings;

	function __construct($settings)
	{
		foreach($settings as $k =>$v){
			$this->$k = $v;
		}
		
		// set defaults
		$this->gallery_title = ($this->gallery_title ? $this->gallery_title : 'Gallery Home');
		$this->page          = (isset($_GET['p']) && $_GET['p'] > 0 ? $_GET['p'] : 1);
		$this->per_page      = ($this->per_page ? $this->per_page : null);
	}

	/**
	 * All sets for user id
	 * @return array
	 */
	public function getPhotoSets()
	{
		$sets = self::apiRequest('flickr.photosets.getList',array('user_id'=>$this->user_id,'page'=>$this->page,'per_page'=>$this->per_page));
		return $sets;
	}

	/**
	 * All photos within a single set
	 * @param  int $photoset_id flickr photoset id
	 * @return array from api repsonse
	 */
	public function getPhotosInSet($photoset_id)
	{
		$photos = self::apiRequest('flickr.photosets.getPhotos',array('photoset_id'=>$photoset_id,'extras'=>'date_upload'));
		return $photos;
	}

	/**
	 * Returns Flickr user id from a username
	 * @param  string $username Flickr username
	 * @return string user id
	 */
	public function getUserId($username)
	{
		$id = self::apiRequest('flickr.people.findByUsername',array('username'=>$username));
		if($id['stat'] == 'ok'){
			return $id['user']['id'];
		}
		return $id;
	}

	/**
	 * Recent photos from Flickr user
	 * @param  string $photos number of photos to return
	 * @return array
	 */
	public function getRecentPhotos($photos=5)
	{
		$photos = self::apiRequest('flickr.people.getPhotos',array('user_id'=>$this->user_id,'per_page'=>$photos));
		if($photos['stat'] == 'ok')
		{
			return $photos['photos']['photo'];
		}
		
		return false;
	}

	/**
	 * Handles all Flickr API requests
	 * @param  string $method http://www.flickr.com/services/api/
	 * @param  array  $params api paramaters
	 * @return array  unserialize response
	 */
	private function apiRequest($method,$params=array())
	{
		$cache_key = $params;
		
		$params = array('api_key'=>urlencode($this->api_key),'format'=>'php_serial') + $params;
		foreach($params as $k => $v){
			$encoded_params[] = urlencode($k).'='.urlencode($v);
		}

		$url = 'https://api.flickr.com/services/rest/?method='.$method.'&'.implode('&', $encoded_params);
		
		$response = self::getCache(implode('.', $cache_key)); // check for cached response
		if(!$response)
		{
			$response = @file_get_contents($url);
			self::setCache(implode('.', $cache_key),$response); // store response in cache
		}		
		
		return unserialize($response);
	}

	/*
	|--------------------------------------------------------------------------
	| File Cache
	|--------------------------------------------------------------------------
	*/

	private function getCache($key)
	{
		// check if file exists and if file is newer than cache time setting (minutes)
		if(file_exists($this->cache['path'].'/'.$key) AND filemtime($this->cache['path'].'/'.$key) > (date("U") - (60 * $this->cache['time'])))
		{
			$cache = file($this->cache['path'].'/'.$key);
			return $cache[0];
		}
		
		return false;
	}

	private function setCache($key,$data)
	{
		if(isset($this->cache['path']) AND is_writeable($this->cache['path']))
		{
			$fp = fopen($this->cache['path'].'/'.$key, 'w');
			fwrite($fp, $data);
			fclose($fp);
		}
	}

	/*
	|--------------------------------------------------------------------------
	| Bootstrap 3 Display Functions
	|--------------------------------------------------------------------------
	*/

	/**
	 * Display handler
	 * @return string
	 */
	public function display()
	{
		if(isset($_GET['id'])){

			echo "under".$_GET['id'];
			$return = self::displayPhotosInSet($_GET['id']);
		}
		else{$return = self::displayPhotoSets();}

		// loads styles here (could be moved to header if needed)
		return (!$this->bootstrap ? '' : '<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-beta.2/css/bootstrap.min.css">').'
		'.$return;
	}
	
	/**
	 * Generates html for photos sets
	 * @return string
	 */
	public function displayPhotoSets()
	{
		$sets = self::getPhotoSets();
		if($sets['stat'] == 'ok' AND is_array($sets['photosets']['photoset']))
		{

			if($sets['photosets']['total'] > 0)
			{
				
				$display = '<div class="col-md-2 px-0 mb-4">';
				foreach($sets['photosets']['photoset'] as $set)
				{
					$display .= '<div class="w-100 custom-menu">
										<a href="?id='.$set['id'].'" class="thumbnail photoset custom_click_event" dataset="'.$set['id'].'">
	      								<div class="caption">
	      									'.$set['title']['_content'].'
	      								</div>
	      							</a>
	      						</div>';
	    
				}

				$display .= "</div><div class='col-md-10 display_all_category'><div class='image-wrapper'><p>Select a category from the left too see images here.</p></div></div></div>";
			}
			else{$display = self::alert('No photo sets to display','warning');}
			

			$return = '<div class="row">
  								'.$display.'
  							</div></div>'.self::pagination($sets['photosets']['page'],$sets['photosets']['pages']);
		}
		else
		{
			$return = '<div class="row">
							'.self::alert('Unable to get photo sets due to: '.$sets['message'],'danger').'
						</div>';
		}

		return self::breadcrumbs().$return;
	}

	/**
	 * Generates html for photos within set
	 * @param  int $set_id flickr set id
	 * @return string
	 */
	public function displayPhotosInSet($set_id)
	{
		$sets = self::getPhotosInSet($set_id);
		if($sets['stat'] == 'ok' AND is_array($sets['photoset']['photo']))
		{
			$display = '<div class="col-md-12"><div class="row d-flex justify-content-start justify-content-center-xs">';
			foreach($sets['photoset']['photo'] as $set)
			{
				$title = self::imageTitle($set['title']);
				$display .= '<div class=" mb-3 col-12 col-md-3">
									<a href="http://farm'.$set['farm'].'.staticflickr.com/'.$set['server'].'/'.$set['id'].'_'.$set['secret'].'_b.jpg" class="thumbnail" title="'.$title.'" data-gallery>
      								<img class="lazy rounded img-fluid w-100" src="http://farm'.$set['farm'].'.staticflickr.com/'.$set['server'].'/'.$set['id'].'_'.$set['secret'].'_q.jpg">
      							</a>
      						</div>';
    
			}
			$display .= '</div>';
			
			$return = self::breadcrumbs(array('title'=>$sets['photoset']['title'])).'
						<div class="row" id="links">
  							'.$display.'
  						</div>';
						?>
			</div>
				<?php
		}
		else
		{
			$return = self::breadcrumbs().'
						<div class="row">
							'.self::alert('Unable to get photo sets due to: '.$sets['message'],'danger').'
						</div>';
		}

		return $return;
	}

	/**
	 * html for alert messages
	 * @param  string $message    message text
	 * @param  string $alert_type bootstrap alert types (success, info, warning, danger)
	 * @return string
	 */
	public function alert($message,$alert_type='danger')
	{
		return '<div class="alert alert-'.$alert_type.'">'.$message.'</div>';
	}

	/**
	 * Bread crumbs HTML
	 * @param  array  $crumbs single array('title'=>'','url'=>'')
	 * @return string
	 */
	private function breadcrumbs($crumbs=array())
	{
		$bc = '<li class="active">'.$this->gallery_title.'</a></li>';
		
		if(count($crumbs) > 0)
		{
			$bc = '	<li class="active">'.$crumbs['title'].' Photo Gallery</li>';
		}
		
		return '<div class="container">
					<div class="row">
						<ol class="breadcrumb w-100	d-flex flex-column justify-content-center align-items-center">
				   		'.$bc.'
				   		</ol>
				   </div>';
	}

	/**
	 * Photoset's pagination
	 * @param  int $current current page
	 * @param  int $pages total pages
	 * @return string
	 */
	function pagination($current,$pages)
	{
		// if only one page, do not return pagination
		if($pages == 1){return '';}
		
		if($current < $pages)
		{
			$prev = array('class'=>'','url'=>'?p='.($current - 1));
			$next = array('class'=>'','url'=>'?p='.($current + 1));

			// for first page
			if($current <= 1){
				$prev = array('class'=>'disabled','url'=>'#');
			}
		}
		elseif($current >= $pages)
		{
			if($current > $pages){$current = $pages + 1;} // force prev to link to actual last page
			$prev = array('class'=>'','url'=>'?p='.($current - 1));
			$next = array('class'=>'disabled','url'=>'#');
		}

		return '<ul class="pager">
						<li class="'.$prev['class'].'"><a href="'.$prev['url'].'">&laquo; Previous</a></li>
						<li class="'.$next['class'].'"><a href="'.$next['url'].'">Next &raquo;</a></li>
					</ul>';	
	}


	/**
	 * Return valid image titles
	 * Flickr stores the image's file name as the title by default
	 * This checks for common camera prefixes to detect file names
	 * @param  string $title
	 * @return string
	 */
	private function imageTitle($title)
	{
		// camera prefixes (i'm sure there are more)
		$image_prefix = array('dscn','dcim','cimg','dsc');
		foreach($image_prefix as $i)
		{
			if(0 === strpos($title, $i)){
				return '';
			}
		}
		
		return $title;
	}
}