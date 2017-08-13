<?php 

/**
 *	Simple script to copy product translations from one lang to others.
 *
 *	Place this file in PrestaShop root dir (next to the 'index.php' file),
 *		configure below and open by browser.
 *
 *  @author Mateusz Bartocha
 *	@email	contact@bestcoding.net
 *	@www	https://bestcoding.net
 */


/************** CONFIGURATION ***************/


// the quantity of products to copy data
define('_PRODUCTS_COUNT_', 1000);

// database host
define('_DB_HOST_', 'localhost');

// database name
define('_DB_NAME_', 'prestashop');

// database user
define('_DB_USER_', 'root');

// database password
define('_DB_PASS_', '');

// language ID from which data will be copied
define('_COPY_FROM_', 1); 

// languages IDs for which the data will be copied 
// (separated by comma)
define('_COPY_TO_', '2,3,4,5,6,7,8,9,10');

// what do you want to copy 
define('_COPY_DESCRIPTION_', 		true); 	// description
define('_COPY_DESCRIPTION_SHORT_', 	true); 	// short description
define('_COPY_LINK_REWRITE_', 		true); 	// link rewrite
define('_COPY_META_DESCRIPTION_', 	true); 	// meta description
define('_COPY_META_TITLE_', 		true); 	// meta title
define('_COPY_META_KEYWORDS_', 		true); 	// meta keywords
define('_COPY_TAGS_', 			true); 	// tags
define('_COPY_IMAGES_LEGEND_', 		true); 	// images legends

// the script will be constantly refreshed and copied further data
define('_AUTO_COPY_', true);


/************** / CONFIGURATION ***************/


if (!file_exists('counter.txt'))
{
	file_put_contents('counter.txt', "0");
	$counter = 0;
}
else $counter = (int) file_get_contents('counter.txt');

if ($counter >= _PRODUCTS_COUNT_) 
{
	@unlink('counter.txt');
	die('done!');
}

$limit = 5; //safe value
$i = 0;

$db = new PDO('mysql:host='._DB_HOST_.';dbname='._DB_NAME_.';charset=utf8', _DB_USER_, _DB_PASS_);

if (!$db) die('database connection error!');

require_once "./config/config.inc.php";

$sql = "
    SELECT DISTINCT p.`id_product`, pl.`description`, 
    	pl.`description_short`, pl.`link_rewrite`, il.`legend`, 
    	pl.`meta_description`, pl.`meta_keywords`, pl.`meta_title`
    FROM `ps_product` p
    INNER JOIN `ps_product_lang` pl ON 
    	(pl.`id_product` = p.`id_product` AND pl.`id_lang` = "._COPY_FROM_.")
    INNER JOIN `ps_image` i ON (i.`id_product` = p.`id_product`)
    INNER JOIN `ps_image_lang` il ON 
    	(il.`id_image` = i.`id_image` AND il.`id_lang` = "._COPY_FROM_.")
    LIMIT ".$counter.",".$limit."
    ";
$products = $db->query($sql);

$COPY_TO = explode(',', _COPY_TO_);

$query_to_exec = "";
    
foreach ($products as $product)
{

	$query_to_exec .= " 
		UPDATE `ps_product_lang` SET "
			.(_COPY_DESCRIPTION_ ? "`description` = '".$product['description']."'," : "") 
			.(_COPY_DESCRIPTION_SHORT_ ? "`description_short` = '".$product['description_short']."', " : "") 
			.(_COPY_LINK_REWRITE_ ? "`link_rewrite` = '".$product['link_rewrite']."', " : "") 
			.(_COPY_META_DESCRIPTION_ ? "`meta_description` = '".$product['meta_description']."', " : "") 
			.(_COPY_META_KEYWORDS_ ? "`meta_keywords` = '".$product['meta_keywords']."', " : "") 
			.(_COPY_META_TITLE_ ? "`meta_title` = '".$product['meta_title']."'" : "") 
		."WHERE `id_product` = ".(int)$product['id_product']." 
			AND `id_lang` IN ("._COPY_TO_.");";
		
	if (_COPY_IMAGES_LEGEND_)		
	{
		$sql = "SELECT i.`id_image` FROM `ps_image` i
			WHERE i.`id_product` = ".(int)$product['id_product'];
		$images = $db->query($sql);
		
		foreach ($images as $image)
		{
			$query_to_exec .= "
				UPDATE `ps_image_lang` SET 
					`legend` = '".$product['legend']."'
				WHERE `id_image` = ".(int)$image['id_image']." 
					AND `id_lang` IN ("._COPY_TO_.");";
		}
	}
	
	if (_COPY_TAGS_)		
	{
		$tags = Tag::getProductTags($product['id_product']);
		$tags_to_copy = implode(',', $tags[_COPY_FROM_]);
	
		foreach ($COPY_TO as $id_lang)
			Tag::addTags($id_lang, $product['id_product'], $tags_to_copy, ',');
	}
}

$db->exec($query_to_exec);

file_put_contents('counter.txt', $counter+$limit);

$db = null;

if (_AUTO_COPY_) echo '<meta http-equiv="refresh" content="0;url=?">';

?>

<div style="text-align: center; padding-top: 300px; font-size: 54px;"><br>
	<p><progress value="<?php echo ($counter+$limit) ?>" style="width:90%"
		max="<?php echo _PRODUCTS_COUNT_ ?>"></progress></p>
	<p><?php echo ($counter+$limit) . '/' . _PRODUCTS_COUNT_ ?></p>
	<p><?php echo round((($counter+$limit)/_PRODUCTS_COUNT_)*100, 2) ?>%</p>
</div>
