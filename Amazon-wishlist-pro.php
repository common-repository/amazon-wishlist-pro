<?php
/**
 * Plugin Name: Amazon Wishlist PRO
 * Description: This plugin will display your Amazon wishlist, you can configure amazon domain, list order, Layout,  and much more. for bugs or features contact: altmannmarcelo@gmail.com
 * Version: 1.4
 * Author: Marcelo Altmann
 * Author URI: http://blog.marceloaltmann.com
 * License: GPL
*/

require_once(__DIR__.'/simple_html_dom.php');
add_action('init','Amazon_wishlist_pro');
function Amazon_wishlist_pro()
{
	$listID = get_option('Amazon-wishlist-pro-listID');
	$sort = get_option('Amazon-wishlist-pro-sort-order');
	$website = get_option('Amazon-wishlist-pro-websites');
	$useCache = get_option('Amazon-wishlist-pro-use-cache');
	$invalidateCache = get_option('Amazon-wishlist-pro-invalidate-cache');
	$cache_file = __DIR__.'/amazon_wishlist-pro.cache';
	$update = 0;
	$wishListURL = 'http://www.amazon.'.$website.'/registry/wishlist/'.$listID.'/ref=cm_wl_sb_v?visitor-view=1&reveal=unpurchased&filter=all&sort='.$sort.'&layout=standard&x=5&y=14';

	if($invalidateCache == 1)
	{
		if (file_exists($cache_file))
		{
			unlink($cache_file);
		}
	}
	if($useCache == 1)
	{
		if (file_exists($cache_file) && (filemtime($cache_file) > (time() - 60 * 24 )))
			$wishListURL = $cache_file;
		else
			$update = 1;
	}
	$wishListHTML = @file_get_html($wishListURL);
	if($update == 1)
		file_put_contents($cache_file, $wishListHTML, LOCK_EX);
	$wishListHTML = $wishListHTML->find('div[id=item-page-wrapper]', 0);
	
	if(!$wishListHTML)
		return 'Invalid ListID ' . $listID .' for amazon.' . $website;


	$template = get_option('Amazon-wishlist-pro-layout');

	$output = '';
	$a = 0;
	foreach($wishListHTML->find('.itemWrapper') as $element) 
	{

		$item = getItemInfo($element);
		if(get_option('Amazon-wishlist-pro-use-unsorted-list'))
			$output .= '<li>';
		$output .= strtr($template, $item);
		if(get_option('Amazon-wishlist-pro-use-unsorted-list'))
			$output .= '</li>';

	$a++;
	}
	if(trim($output) != '')
	{
		if(get_option('Amazon-wishlist-pro-use-unsorted-list'))
			$output = '<ul>' . $output . '</ul>';
	}

	if($invalidateCache == 1)
			update_option('Amazon-wishlist-pro-invalidate-cache', '0');
	
	return $output;

}

function getItemInfo($node)
{
	$image = $node->find('img',0)->src;
	$buyLink = $node->find('.wlBuyButton a',0)->href;
	$title = utf8_encode(trim($node->find('span.productTitle',0)->plaintext));
	$productLink = $node->find('span.productTitle strong a',0)->href;
	$price = utf8_encode($node->find('span.wlPriceBold',0)->plaintext);
	if(trim($price) == '')
	{
		$link = $productLink;
		$useCache = get_option('Amazon-wishlist-pro-use-cache');
		$invalidateCache = get_option('Amazon-wishlist-pro-invalidate-cache');
		$cache_file_prod = __DIR__.'/' . md5($productLink) .'.cache';
		if($invalidateCache == 1)
		{
			if (file_exists($cache_file_prod))
			{
				unlink($cache_file_prod);
			}
		}
		if($useCache == 1)
		{
			if (file_exists($cache_file_prod) && (filemtime($cache_file_prod) > (time() - 60 * 24 )))
				$link = $cache_file_prod;
			else
				$update = 1;
		}
		$productHTML = @file_get_html($link);
		if($update == 1)
			file_put_contents($cache_file_prod, $productHTML, LOCK_EX);
		
		$price = utf8_encode($productHTML->find('.priceLarge',0)->plaintext);	
	}
	$info = array(
		'@price@' => $price,
		'@image_src@' => $image,
		'@buy_href@' => $buyLink,
		'@title@' => $title,
		'@product_href@' => $productLink
	);

	return $info;
}

function defaultTemplate()
{
	$html = '<strong>@title@</strong><br />@price@ &nbsp;&nbsp;&nbsp;&nbsp;<a href="@product_href@" target="_blank">buy it</a><br /><br />';
	return $html;


}
function amazonWebsites()
{
	$websites = array(
		'co.uk' => 'amazon.co.uk',
		'com' => 'amazon.com',
		'de' => 'amazon.de',
		'at' => 'amazon.at',
		'fr' => 'amazon.fr',
		'co.jp' => 'amazon.co.jp',
		'ca' => 'amazon.ca',
		'es' => 'amazon.es',
		'cn' => 'amazon.cn',
		'it' => 'amazon.it',
	);

	return $websites;
}
if ( is_admin() )
{

	/* Call the html code */
	add_action('admin_menu', 'Amazon_wishlist_admin_menu');

	function Amazon_wishlist_admin_menu() 
	{
		add_options_page('Amazon Wishlist PRO', 'Amazon Wishlist PRO', 'administrator',
			'Amazon-wishlist-PRO', 'Amazon_wishlist_pro_admin_page');
	}
}

function getSortOrder()
{
	$order = array(
		'date-added' => 'Date Added',
		'universal-title' => 'Title',
		'universal-price' => 'Price (Low to High)',
		'universal-price-desc' => 'Price (High to Low)',
		'last-updated' => 'Last Updated',
		'priority' => 'Priority (High to Low)',
	);

	return $order;
}

function Amazon_wishlist_pro_admin_page() 
{
	$html = <<<EOHTML
<div>
<h2>Amazon Wishlist PRO - Options</h2>
<h3>Instructions</h3>
<p>Update the settings according your amazon website(.com .co.uk ...) and activate the <strong>Amazon wishlist PRO</strong> widget.</p>
<h3>Settings</h3>
<form method="post" action="options.php">
EOHTML;
	$html .= wp_nonce_field('update-options');
	$html .= <<<EOHTML

<table width="90%" class="form-table">
<tr valign="top">
<th scope="row">Your Amazon Wishlist ID.</th>
<td>
		<input name="Amazon-wishlist-pro-listID" type="text" id="Amazon-wishlist-pro-listID" value="
EOHTML;
	$html .= get_option('Amazon-wishlist-pro-listID');
	$html .= <<<EOHTML
" />
        </td>
</tr>

<tr>
        <th>Website.</th>
        <td>
		<select name="Amazon-wishlist-pro-websites" id="Amazon-wishlist-pro-websites">
EOHTML;
	foreach(amazonWebsites() as $key => $title)
	{
		$selected = '';
		if(get_option('Amazon-wishlist-pro-websites') == $key)
			$selected = 'selected="selected"';
		$html .= '<option value="' . $key . '" ' . $selected . '>'.$title.'</option>';
	}
	$html .= <<<EOHTML
	
        </td>
</tr>
<tr>
        <th>Sort Order.</th>
        <td>
		<select name="Amazon-wishlist-pro-sort-order" id="Amazon-wishlist-pro-sort-order">
EOHTML;
	foreach(getSortOrder() as $key => $title)
	{
		$selected = '';
		if(get_option('Amazon-wishlist-pro-sort-order') == $key)
			$selected = 'selected="selected"';
		$html .= '<option value="' . $key . '" ' . $selected . '>'.$title.'</option>';
	}
	$html .= <<<EOHTML
	
        </td>
</tr>
<tr>
        <th width="" scope="row">Use file cache (we strongly advise you to turn on the cache)</th>
        <td width="">
                <input name="Amazon-wishlist-pro-use-cache" type="checkbox" id="Amazon-wishlist-pro-use-cache"
		value="1" 
EOHTML;
	if(get_option('Amazon-wishlist-pro-use-cache') == 1)
		$html .= 'checked="checked"';
	$html .= <<<EOHTML
/>
        </td>
        
	</tr>
<tr>
<tr>
	<th>Invalidate cache</th>
	<td>
		<input name="Amazon-wishlist-pro-invalidate-cache" type="checkbox" id="Amazon-wishlist-pro-invalidate-cache"
		value="1" />
	</td>
</tr>
        <th width="" scope="row">Use unsroted list(ul li).</th>
        <td width="">
                <input name="Amazon-wishlist-pro-use-unsorted-list" type="checkbox" id="Amazon-wishlist-pro-use-unsorted-list"
		value="1" 
EOHTML;
	if(get_option('Amazon-wishlist-pro-use-unsorted-list') == 1)
		$html .= 'checked="checked"';
	$html .= <<<EOHTML
/>
        </td>
        
	</tr>
<tr>
	<th>Layout.</th>
	<td>
		<textarea name="Amazon-wishlist-pro-layout" id="Amazon-wishlist-pro-layout">
EOHTML;
	$html .= get_option('Amazon-wishlist-pro-layout');
	$html .= <<<EOHTML
		</textarea>
	</td>
</tr>
<tr>
	<td colspan="2">
		<strong>Available information:</strong>
		<br />
		@price@ => Item price <br />
		@image_src@ => Link for item image <br />
		@buy_href@ => Add to cart link <br />
		@title@ => Item Title <br />
		@product_href@ => Item Link
	</td>
<tr>


</table>
<input type="hidden" name="action" value="update" />
<input type="hidden" name="page_options" value="Amazon-wishlist-pro-listID, Amazon-wishlist-pro-sort-order, Amazon-wishlist-pro-layout, Amazon-wishlist-pro-websites, Amazon-wishlist-pro-use-unsorted-list, Amazon-wishlist-pro-use-cache, Amazon-wishlist-pro-invalidate-cache" />
<p>
<input type="submit" value="Update" />
</p>
</form>
<br />
<br />
<h3>Your Wishlist Preview</h3>
<table border="0" width="300" style="border-collapse:collapse">
	<tr>
		<td>
EOHTML;
	$html .= Amazon_wishlist_pro();
	$hmtml .= <<<EOHTML
		</td>
	</tr>
</table>

</div>
EOHTML;
echo $html;
}
register_activation_hook(__FILE__,'Amazon_wishlist_pro_install'); 
register_deactivation_hook( __FILE__, 'Amazon_wishlist_pro_remove' );

function Amazon_wishlist_pro_install() 
{
	$default = defaultTemplate();
	add_option("Amazon-wishlist-pro-listID", '33CTCD8ASA7FH', '', 'yes');
	add_option("Amazon-wishlist-pro-sort-order", 'date-added', '', 'yes');
	add_option("Amazon-wishlist-pro-layout", $default , '', 'yes');
	add_option("Amazon-wishlist-pro-websites", 'co.uk' , '', 'yes');
	add_option("Amazon-wishlist-pro-use-unsorted-list", '1' , '', 'yes');
	add_option("Amazon-wishlist-pro-use-cache", '1' , '', 'yes');
	add_option("Amazon-wishlist-pro-invalidate-cache", '0' , '', 'yes');
}

function Amazon_wishlist_pro_remove() 
{
	delete_option('Amazon-wishlist-pro-listID');
	delete_option('Amazon-wishlist-pro-sort-order');
	delete_option('Amazon-wishlist-pro-layout');
	delete_option('Amazon-wishlist-pro-websites');
	delete_option('Amazon-wishlist-pro-use-unsorted-list');
	delete_option('Amazon-wishlist-pro-use-cache');
	delete_option('Amazon-wishlist-pro-invalidate-cache');
}


class AWL_Widget extends WP_Widget 
{
	function AWL_Widget() 
	{
		parent::WP_Widget( false, $name = 'Amazon Wishlist PRO Widget' );
	}

	function widget( $args, $instance ) 
	{
		extract( $args );
		$title = apply_filters( 'widget_title', $instance['title'] );
?>

<?php
		echo $before_widget;
?>

<?php
		if ($title) {
			echo $before_title . $title . $after_title;
		}
?>

    <div class="my_textbox">
      <?php echo Amazon_wishlist_pro();?>
    </div>

<?php
		echo $after_widget;
?>
<?php
	}

	function update( $new_instance, $old_instance ) {
		return $new_instance;
	}

	function form( $instance ) {
		$title = esc_attr( $instance['title'] );
?>

    <p>
      <label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?>
      <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo $title; ?>" />
      </label>
    </p>
<?php
	}
}

add_action( 'widgets_init', 'AWL_WidgetInit' );
function AWL_WidgetInit() {
	  register_widget( 'AWL_Widget' );
}


?>
