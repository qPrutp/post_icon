<?php
/*
Plugin Name: Post icon
Description: A simple wordpress plugin
Version: 1.0
Author: Vasyl Savitskyy
*/
?>

<?php
$true_page = 'myparameters.php'; // это часть URL страницы, рекомендую использовать строковое значение, т.к. в данном случае не будет зависимости от того, в какой файл вы всё это вставите

/*
 * Функция, добавляющая страницу в пункт меню Настройки
 */
function true_options() {
	global $true_page;
	add_options_page( 'Post icon', 'Post icon', 'manage_options', $true_page, 'true_option_page');  
}
add_action('admin_menu', 'true_options');

require_once( plugin_dir_path( __FILE__ ) . 'includes/dashicons.php' );

global $wpdb;
global $jal_db_version;
$jal_db_version = "1.0";

function create_table_status() {
	global $wpdb;
	$table_name = $wpdb->prefix . "plugin_status";
	if($wpdb->get_var("show tables like '$table_name'") != $table_name) {
      
      $sql = "CREATE TABLE " . $table_name . " ( plugin_status BOOLEAN not null default 1 );";

      require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
      dbDelta($sql);

      $rows_affected = $wpdb->insert( $table_name, array('plugin_status' => 1 ) );
 
      add_option("jal_db_version", $jal_db_version);

   }
}

function create_table_for_posts() {
	global $wpdb;
	$table_name = $wpdb->prefix . "post_icon";
	if($wpdb->get_var("show tables like '$table_name'") != $table_name) {
      
      $sql = "CREATE TABLE " . $table_name . " ( id mediumint(9) NOT NULL AUTO_INCREMENT, id_post MEDIUMINT not null, icon_name VARCHAR(30) not null, position_icon BOOLEAN not null default 1, PRIMARY KEY  (id) );";

      require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
      dbDelta($sql);

   }
}

register_activation_hook( __FILE__, 'create_table_status' );
register_activation_hook( __FILE__, 'create_table_for_posts' );

function update_plugin_status($status) {
	global $wpdb;
	$rows_affected = $wpdb->get_results( "UPDATE wp_plugin_status SET plugin_status = " . $status );
}

function update_post_icon_table($status) {
	global $wpdb;
	$rows_affected = $wpdb->get_var('SELECT id_post FROM wp_post_icon WHERE id_post = '.$status['posts_id']);
	if ($rows_affected) {
		$rows = $wpdb->get_results("update wp_post_icon set icon_name = '".$status['icon']."', position_icon = ".$status['siting_icon']." where id_post = ".$status['posts_id']);
	}else{
		$rows = $wpdb->insert('wp_post_icon', array('id_post' => $status['posts_id'], 'icon_name' => $status['icon'], 'position_icon' => $status['siting_icon']));
	}
}

if ( isset($_POST['status_plugin']) ) {
    update_plugin_status($_POST["status_plugin"]);
}

if ( isset($_POST['posts_id']) && isset($_POST['icon']) && isset($_POST['siting_icon']) ) {
	update_post_icon_table(array('posts_id' => $_POST['posts_id'], 'icon' => $_POST['icon'], 'siting_icon' => $_POST['siting_icon']));
}

$status_plugin = $wpdb->get_var("SELECT plugin_status FROM wp_plugin_status;");


function add_icon_title($title) {
	global $wpdb;
	$publish_post_list_id = $wpdb->get_results('SELECT a.id_post, a.icon_name, a.position_icon FROM wp_post_icon a, wp_posts b WHERE a.id_post = b.ID AND b.post_status = "publish";');
	// echo "add_icon_title is working!";

	//Check if we are in the loop
	if (!in_the_loop()){
        return $title;
    }
    
    echo "<script type=\"text/javascript\">";
    	echo "function a() {";
	    	echo "let array = ".json_encode($publish_post_list_id).";";
	    	echo "for (let i = 0; i < array.length; i++) {";
		    	echo "let element_id = document.getElementById(\"post-\"+array[i].id_post).children[0].children[2];";
		    	echo "console.log(element_id);";
		    	echo "if(element_id) {";
			    	echo "if(array[i].position_icon == 0) {";
			    		echo "element_id.innerHTML = \"<span class='dashicons \"+array[i].icon_name+\"'></span>\"+element_id.innerHTML";
			    	echo "} else {";
			    		echo "element_id.innerHTML = element_id.innerHTML+\"<span class='dashicons \"+array[i].icon_name+\"'></span>\"";
			    	echo "}";
		    	echo "}";
	    	echo "}";
	    	echo "console.log(array);";
	    	echo "";
	    	echo "";
	    	echo "";
    	echo "}";
    	echo "a();";
    	echo "console.log('/////////////////////////////////////////////');";
    echo "</script>";

	return "Filter is working ". $title;
}

if($status_plugin == 1) {
	add_filter('the_title', 'add_icon_title');
}
 
/**
 * Возвратная функция (Callback)
 */ 
function true_option_page(){
	global $true_page;
	global $wpdb;
	global $status_plugin;

	$publish_post_list_id = $wpdb->get_results('SELECT a.id_post, a.icon_name, a.position_icon FROM wp_post_icon a, wp_posts b WHERE a.id_post = b.ID AND b.post_status = "publish";');

	?><div class="wrap">
		<h2>Post icon (додавання іконки до заголовку поста)</h2>
		<h3>Стан плагіну <?php if ($status_plugin) {
			echo "<span class='dashicons dashicons-unlock'></span>";
		}else{
			echo "<span class='dashicons dashicons-lock'></span>";
		}?></h3>
		<form method="post" >
			<label><input type='radio' name='status_plugin' value='1' <?php if($status_plugin) echo "checked='checked'"?> />Активований</label>
			<label><input type='radio' name='status_plugin' value='0' <?php if(!$status_plugin) echo "checked='checked'"?> />Деактивований</label>
			<br />
			<input type="submit" class="button-primary" value="<?php _e('Save') ?>" />
		</form>

		<div class="wrap">
		<form method="post" >
			<?php 
			settings_fields('true_options');
			do_settings_sections($true_page);
			?>
			<p class="submit">
				<input type="submit" class="button-primary" value="<?php _e('Save') ?>" />  
			</p>
		</form>
	</div><?php
}
 
/*
 * Регистрируем настройки
 * Мои настройки будут храниться в базе под названием true_options (это также видно в предыдущей функции)
 */
function posts_icon_settings() {
	global $wpdb;
	$post_list = $wpdb->get_col('SELECT post_title FROM `wp_posts` WHERE post_status = "publish" AND comment_status = "open" AND ping_status = "open"');
	$post_list_id = $wpdb->get_col('SELECT ID FROM `wp_posts` WHERE post_status = "publish" AND comment_status = "open" AND ping_status = "open"');
	global $true_page;
	global $dashicons;
	// Присваиваем функцию валидации ( true_validate_settings() ). Вы найдете её ниже
	register_setting( 'posts_icon', 'posts_icon', 'true_validate_settings' ); // true_options

	// ----------------------------------------------------------------------------------------------------------------------------------
	// Добавляэмо секції
	add_settings_section( 'posts_section_1', '', '', $true_page );
 
	// Створюємо список постів
	$true_field_params = array(
		'type'      => 'posts_list',
		'id'        => 'posts_list',
		'desc'      => 'Випадаючий список постів.',
		'content'	=> $post_list,
		'id_posts'	=> $post_list_id
	);
	add_settings_field( 'posts_list_select_field','Вибір посту', 'true_option_display_settings', $true_page, 'posts_section_1', $true_field_params );
	// Створюємо список іконок
	$true_field_params = array(
		'type'      => 'icons_list',
		'id'        => 'icons_list',
		'desc'      => 'Випадаючий список іконок.',
		'vals'		=> $dashicons
	);
	add_settings_field( 'icons_list_select_field','Вибір іконки', 'true_option_display_settings', $true_page, 'posts_section_1', $true_field_params );
	// Создадим радио-кнопку
	$true_field_params = array(
		'type'      => 'siting_icon',
		'id'      	=> 'siting_icon',
		'vals'		=> array( '0' => 'Ліворуч', '1' => 'Праворуч')
	);
	add_settings_field( 'my_radio', 'Розташування іконки в заголовку поста', 'true_option_display_settings', $true_page, 'posts_section_1', $true_field_params );
	// ----------------------------------------------------------------------------------------------------------------------------------
 
}
add_action( 'admin_init', 'posts_icon_settings' );
 
/*
 * Функция отображения полей ввода
 * Здесь задаётся HTML и PHP, выводящий поля
 */
function true_option_display_settings($args) {
	extract( $args );
 
	$option_name = 'posts_icon';
 
	$o = get_option( $option_name );
 
	switch ( $type ) {
		case 'posts_list':
			echo "<select id='$id' name='posts_id'>";
			foreach($content as $v=>$l){
				$selected = ($o[$id] == $v) ? "selected='selected'" : '';  
				echo "<option value='$id_posts[$v]' $selected>$l</option>";
			}
			echo ($desc != '') ? $desc : "";
			echo "</select>";
		break;
		case 'icons_list':
			echo "<div id=\"drowIcon\" style=\"margin: 0 0 -22px -65px; height: 17px;\"></div>";
			echo "<select id='$id' name='icon' onchange='drowIcon(this.value)'>";
			foreach($vals as $v=>$l){
				$selected = ($o[$id] == $v) ? "selected='selected'" : '';  
				echo "<option value='$l' $selected>$l</option>";
			}
			echo ($desc != '') ? $desc : "";
			echo "</select>";
			wp_enqueue_script('test', plugin_dir_url(__FILE__) . 'includes/assets/app.js');
		break;
		case 'siting_icon':
			echo "<fieldset>";
			foreach($vals as $v=>$l){
				$checked = ($o[$id] == $v) ? "checked='checked'" : '';
				echo "<label><input type='radio' name='siting_icon' value='$v' $checked />$l</label><br />";
			}
			echo "</fieldset>";  
		break;
	}
}
 
/*
 * Функция проверки правильности вводимых полей
 */
function true_validate_settings($input) {
	foreach($input as $k => $v) {
		$valid_input[$k] = trim($v);
 
		/* Вы можете включить в эту функцию различные проверки значений, например
		if(! задаем условие ) { // если не выполняется
			$valid_input[$k] = ''; // тогда присваиваем значению пустую строку
		}
		*/
	}
	return $valid_input;
}