<?php
// src/Controller/ResponsiveGalleryController.php
namespace App\Controller;

#use Symfony\Bundle\FrameworkBundle\Controller\Controller;

require dirname(__DIR__).'/Classes/class.flickr_gallery.php';

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ResponsiveGalleryController
{
    public function gallery()
    {
    	$settings = array(
			'api_key'       => '8928d01d0b9cf733646d15f24fbea74a',
			'user_id'       => '187417263@N07',
			'gallery_title' => 'Gallery Categories',
			'gallery_url'   => '/',
			'assets_url'    => '/gallery',
			'cache'         => array('path'=>__DIR__.'/gallery/cache','time'=>30),
			'per_page'      => 24,
			'indicator'     => false,
			'jquery'        => true,
			'bootstrap'     => true,
		);
    	$gallery = new flickr_gallery($settings);
    	$gallery_display = $gallery->display();

    	?>
    		<!DOCTYPE html>
    		<html>
    		<head>
    			<meta name="viewport" content="width=device-width, initial-scale=1.0">
    			<title></title>
    		</head>
    		<body>
    			<?php echo $gallery_display;?>
    		</body>
    		</html>
    	<?php

    	$number = random_int(0, 100);

        return new Response(
        );
    }

    public function ajaxRequest(){
    	?>
    		<div>
    			<?php

    				$datasetId = $_REQUEST['id'];
    				$settings = array(
						'api_key'       => '8928d01d0b9cf733646d15f24fbea74a',
						'user_id'       => '187417263@N07',
						'gallery_title' => 'Gallery Categories',
						'gallery_url'   => '/',
						'assets_url'    => '/gallery',
						'cache'         => array('path'=>__DIR__.'/gallery/cache','time'=>30),
						'per_page'      => 24,
						'indicator'     => false,
						'jquery'        => true,
						'bootstrap'     => true,
					);
			    	$gallery = new flickr_gallery($settings);
    				$gallery_display = $gallery->displayPhotosInSet($datasetId);
    				print_r($gallery_display);
    			?>
    		</div>
    		
    	<?php
    	return new Response(
        
        );
    }
}

?>
<!-- Custom style for changes on design -->
<style type="text/css">
	.custom-menu .caption:hover {
		background: #100e17; 
		color: #fff;

	}
	.custom-menu .caption {
		padding: 3px 10px;
	}
	.custom-menu a {
		text-decoration: none;
		font-size: 16px;
		color: #333;
	}
	.custom-menu a.active .caption {
		background: #100e17; 
		color: #f7f7f7;
	} 
</style>
<!-- Custom Jquery Ajax to display data from flickr -->
<script src="https://code.jquery.com/jquery-3.2.1.min.js"></script>
<script type="text/javascript">
	jQuery(document).ready(function($){
		jQuery('.custom_click_event').click(function(e){
			e.preventDefault();
			var path = "/ajaxrequest";
			jQuery('.custom_click_event').removeClass('active');
			jQuery(this).addClass('active');
			var datasetId = jQuery(this).attr('dataset');
			jQuery.ajax({
                url:path,
                type: "POST",
                data: {
                    "id": datasetId
                },
                success: function (data)
                {
                    jQuery('.display_all_category').html(data);
                }
            });
		})
	})
</script>
<?php