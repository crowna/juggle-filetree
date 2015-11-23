<?php
/*
Plugin Name: Juggle Filetree
Plugin URI: http://crowna.co.nz/juggle-filetree/
Description: File library system. It fits a drag'n drop sortable tree structure for files of a host page plus the files of a nominated page and its subpages.
Author: Jeremy Crowe
Version: 1.02
Author URI: http://crowna.co.nz/
*/


/**
 * Handles Activation/Deactivation/Install
 *
 * register_activation_hook( __FILE__, array( 'juggle_Init', 'on_activate' ) );
 * register_deactivation_hook( __FILE__, array( 'juggle_Init', 'on_deactivate' ) );
 * register_uninstall_hook( __FILE__, array( 'juggle_Init', 'on_uninstall' ) );
 */
 

function can_author(){
	$author = current_user_can('author') || current_user_can('administrator') ;
	$author = apply_filters('juggle_filetree_author',  $author);
	return $author ;
}

//standard plugin behaviour
add_shortcode('juggle_filetree', 'juggle_filetree_func');
add_action( 'wp_enqueue_scripts', 'juggle_filetree_css' );


/**
 * enqueue scripts and styles
 */
function juggle_filetree_css() {
    //add our css
    wp_enqueue_style('juggle_filetree_css', plugins_url('/css/juggle-filetree.css', __FILE__));
    /*
     * Swap this for your theme-level style by adding this to your theme's function.php
     *  wp_dequeue_style('juggle_filetree_css');
     *  wp_enqueue_style('juggle_filetree_css', get_template_directory_uri() . '/local.css' );
     */
}

 

 
//[juggle_filetree parent_path="page-1" uploads="false"]    parent_path is required
function juggle_filetree_func( $attr ){
	
	/**
	* used on search page
	*
	* Jeremy 150703
	*
	* Files to be sorted may be:
	*  - stored to host page (new)
	*  - found in children pages of a different page (this was project specific to pull from milestones, forum replies, events)
	*
	* Storage will update page by putting display tree content directly before the [juggle_filetree]
	*
	*    'uploads', (boolean) can the author upload new files to the library. If this is set to yes, the plugin 'WP Multi File Uploader' is required for managing the frontend file uploads. Default 'false'.
	*    'parent_path', (string) this is the path to a parent page. The file tree will display the parent's files plus the files of any child pages in addition to the host page. E.g. 'country/norway' will display files in 'country/norway', 'country/norway/oslo', 'country/norway/bergen' etc.
	*    'hide_new_files', (boolean) hide unsorted files. Non-authors won't see unsorted files. Default 'false'.
	*    'use_host_as_suffix' (boolean) This would append the slug of the shortcode host page to the parent_path. It is used in template pages. Example: you create the page with the slug 'norway' in the document section of your site. Its template has the shortcode [juggle_filetree parent_path="country" use_host_as_suffix="true"]. This will have the same effect as using the parent_path 'country/norway', but you don't need to adjust the shortcode for each different country, the shortcode will be using the slug of the host page. Default 'false'.
	*
	*/

    // Allow plugins/themes to override/alter the default input parameters.
    $attr = apply_filters('juggle_filetree_attr',  $attr);

    //get path of parent holding sub-pages
        $parent_path = '';
        $uploads = false;
        $use_host_as_suffix = false ;
        $hide_new_files = true ;
    extract(shortcode_atts(array(
        'parent_path' => '',
        'uploads' => false,
        'use_host_as_suffix' => false,
        'hide_new_files' => true,
    ), $attr, 'juggle_filetree'));

    global $post ;

    $output = '';

    //default empty content
    $content = '<section id="filehandler"><ol class="sortable ui-sortable"></ol><p id="files_found">Files found: <span></span></p></section>';
	
    //make tools
    //fit tools forms
    if( can_author() && $uploads == "true" ) {
        if(function_exists('wp_multi_file_uploader')) {
            $upload_html = wp_multi_file_uploader(array(), true);
			//replace wpmfu_script
			wp_dequeue_script('wpmfu_script');
			wp_register_script( 'wpmfu_script_juggle_filetree', plugins_url( 'js/fineuploader_jf.min.js' , __FILE__ ), array( 'jquery' ), '1.1.4', true );
			wp_enqueue_script('wpmfu_script_juggle_filetree');
        }else{
            $upload_html = "<span>You have selected to upload files but you don't have the extra required. plugin '<b>wp-multi-file-uploader</b>'.</span>";
        }
		//allow for other file uploaders to be used.
		$upload_html = apply_filters('juggle_filetree_uploader',  $upload_html);
    }else{
        $upload_html = '<div class="qq-upload-button-empty" ></div>' ;
    }
    $juggle_filetreetools = '<section id="filehandlertools">
        <div id="save-div"><button>Save changes</button></div>
        ' . $upload_html . '
        <input type="submit" name="folder" id="folder" value="New Folder" />
        <div id="foldernameForm" style="display: none;">
            <input type="text" name="foldername" id="foldername" value="" placeholder="folder name" />
            <input type="submit" name="foldercreate" id="foldercreate" value="create" />
            <input type="submit" name="foldernameclose" id="foldernameclose" value="close" />
        </div>
	</section>';

    // Allow plugins/themes to override/alter the default tools. Note:this could cause bugs with any jquery.
    $juggle_filetreetools = apply_filters('juggle_filetree_filetreetools',  $juggle_filetreetools);


    //fit post id for use in scripts
    $output .= '<script>var pid="' . $post->ID . '",base="' . site_url() . '";</script>';

    if ( can_author() ) {
		
		//add our js
		wp_enqueue_script('juggle_filetree_ui_core', plugins_url('/js/jquery.ui.core.min.js', __FILE__));
		wp_enqueue_script('juggle_filetree_ui-352', 'http://code.jquery.com/ui/1.10.3/jquery-ui.js' , array(), '3.5.2', true);
		
		wp_enqueue_script('juggle_filetree_ui_widget', plugins_url('/js/jquery.ui.widget.min.js', __FILE__));
		wp_enqueue_script('juggle_filetree_ui_mouse', plugins_url('/js/jquery.ui.mouse.min.js', __FILE__));
		wp_enqueue_script('juggle_filetree_ui_touch', plugins_url('/js/jquery.ui.touch-punch.js', __FILE__));
		wp_enqueue_script('juggle_filetree_mjs_sort', plugins_url('/js/jquery.mjs.nestedSortable_new.js', __FILE__));
		
        wp_enqueue_script('juggle_filetree_5', plugins_url('/js/jquery-juggle-filetree_admin.js', __FILE__), array(), '1.0.1', true);
    } else {
        wp_enqueue_script('juggle_filetree_5', plugins_url('/js/jquery-juggle-filetree_view.js', __FILE__), array(), '1.0.1', true);
    }
	//assemble output
	
	//fit default if page content doesn't exist
	if( stristr( $post->post_content, 'filehandler' ) === FALSE )
		$output.= $content ;

	//get files
	//find any files from milestones, forum topics or replies, events
	if ($parent_path == ""){
		$parent = $post ;
    }elseif ( $use_host_as_suffix == 'true' ){
        $parent = get_page_by_path( $parent_path.'/'.$post->post_name );
    }else{
        $parent = get_page_by_path( $parent_path );
    }
    
	$output.= juggle_get_children_files( $parent->ID ); //milestones/processes
    // TODO needs a mechanism to include attachments in P4H forums and events

	//fit tools forms
	if( can_author() ){
		$output.= $juggle_filetreetools;
	}else{
		if($hide_new_files == 'true'){
			$output.= '<script>var nsn = true;</script>'; //jQuery will hide new files for non-administrators
		}
	}

    // Allow plugins/themes to override/alter output
    $output = apply_filters('juggle_filetree_output', $output,  $attr);

    return $output;
}



//get children files of the parent country or global
function juggle_get_children_files ( $pid ) {
	/**
	* returns a piece of js
	**/

    if( $pid == "" ){
        return '<script>alert("No parent page found.");</script>';
    }
	
	GLOBAL $post;
	
	//get sub-pages of the parent
	$args = array(
		'child_of' => $pid,
		'sort_column' => 'post_date',
		'sort_order' => 'desc',
		'date_format' => 'd.m.y',
	);	
	$subpages = get_pages($args ) ;
	
	//add parent attachments
	if ( $post->ID != $pid ){
		$subpages[] = get_post($pid) ; 
	}
	
	//add attachments of host page
	$subpages[] = $post ;

    $wp_upload_dir = wp_upload_dir();
    $wp_upload_dir =  $wp_upload_dir['baseurl'] ;

	//process sub-pages
    $cnt=0;
    $rtn = '<script>var fileset = [';
	$debug = 'var debug_juggle = {parent_id:"'. $pid .'"",subpages:[';
	foreach ( $subpages as $subpage ) {
								
		//add attachments
		$args = array(
			'post_type' => 'attachment',
			'numberposts' => null,
			'post_status' => null,
			'post_parent' => $subpage->ID
		);
        $debug .= '{subpage_id:'. $subpage->ID .',attch_id:[';

		$attachments = get_posts($args);
		if ($attachments) {
			foreach ($attachments as $attachment) {

				$svrurl = get_attached_file( $attachment->ID );
				$shorturl = substr($svrurl , strrpos($svrurl , '/wp-content/') ) ;

				$attachment_found = strpos($post->post_content , $shorturl  ) === false ? false : true ;

				// ID of attachment
				$attachment_name = str_replace( '_', ' ' , $attachment->post_title  );
				$attachment_format = substr($attachment->guid , strrpos($attachment->guid , '.')+1 );
				$attachment_size = FileSizeConvert_jf(
					file_get_size_jf(
						str_replace( $wp_upload_dir, '', get_attached_file( $attachment->ID ) )
					)
				); //JC function
				
				$weburl = site_url() . $shorturl  ;

				//write content
				if($attachment_size!=""){
					if($cnt!=0) $rtn .= ',';
					$rtn .= '["'.$attachment_name.'","'.$weburl.'","'.$attachment_format.'","'.$attachment_size.'",'.$attachment_found.']';
					$cnt++;
				}
				$debug .=  $attachment->ID.',';
			}
		}
        $debug .= ']},';
	}
	$debug .= ']};';
	if ( !isset( $_GET['debug']) ) $debug = ''; //hide debug if not called
    $rtn .= '];'. $debug .'</script>';
	return $rtn ;
}


//store filetree into page
function juggle_store_funct() {
		
	$pid = $_POST['pid'];
	$cont = $_POST['cont'];
	$hid_files = $_POST['hid_files'] ;
	// frontend -> ajax -> update THEN respond
	
	//find and test page for [juggle_filetree]	
	$post = get_post($pid) ;
	$post_cont = $post->post_content ;
	
	
	//prepare juggle filetree section of post original content to be replaced
	if(stripos( $post_cont , '<section id="filehandler">' ) !== false){
		$old_tree = get_substr('<section id="filehandler">','</section>', $post_cont ) ; //there may be this
		$post_cont = str_replace( $old_tree , '' , $post_cont ); //remove old tree
	}
	
	$shortcode_details = get_substr('[juggle_filetree',']', $post_cont ) ; //there MUST be this
	
	
	if( $shortcode_details != "" && can_author() ){
		if ( $hid_files != "" ) $hid_files = '<p id="hidden_files">' . $hid_files . '</p>' ;
		$cont = '<section id="filehandler"><ol class="sortable ui-sortable">' . $cont . '</ol><p id="files_found">Files found: <span></span></p>' . $hid_files . '</section>' . $shortcode_details ;
		
		$cont = str_replace( $shortcode_details , $cont , $post_cont ); //replace old 
		
		$my_post = array(
		  'ID'           => $pid,
		  'post_content' => $cont,
		);

		// Update the post into the database
		//wp_update_post( $my_post , true);
		$return = wp_update_post( $my_post , true);
		
		if( is_wp_error( $return ) ) {
			echo $return->get_error_message();
		}else{
			echo 'done';
		}
	}else{
		echo 'Not found.' ;
	}
}
add_action('wp_ajax_juggle_store','juggle_store_funct' );

//sets the attachment parent to out page
function juggle_set_parent_funt() {
	
	$pid = $_POST['pid'];
	$aid = $_POST['aid'];
	
	if( can_author() ){		
		// Update post 37
		$attachment_post = array(
			'ID'           => $aid,
			'post_parent'   => $pid
		);

		// Update the post into the database
		$post_id = wp_update_post( $attachment_post, true );	
		
		if (is_wp_error($post_id)) {
			$errors = $post_id->get_error_messages(); 
			foreach ($errors as $error) { echo $error ; }
		} else {
			echo "done" ;
		}
	}
}
add_action('wp_ajax_juggle_attach','juggle_set_parent_funt' );

/**
 * @param $start
 * @param $end
 * @param $string
 * @param int $exclude
 * @param int $debug
 * @return string
 */
function get_substr($start,$end,$string,$exclude=0,$debug=0){
/**
     * returns substring by strings
     * $string="Blah<br/> dribble, last active 16 seconds ago. Mouse <span>";
     * $start=", last ";
     * $end=" ago.";
     *
     *get_substr($start,$end,$string); 
     *", last active 16 seconds ago."
     *
     * exclude removes start and end if set to 1
     * get_substr($start,$end,$string,1);
     *    "active 16 seconds"
     * debug shows found start position and end position if set to 1
     * get_substr($start,$end,$string,0,1);
     *  ", last active 16 seconds ago./494/29/"
     */

	if($exclude==1) {
		$s_pos = strpos($string, $start)+strlen($start);
		$e_pos = strpos($string, $end,$s_pos)-$s_pos;
	}else{
		$s_pos = strpos($string, $start);
		$e_pos = strpos($string, $end, $s_pos) + strlen($end) - $s_pos;
	}
	$debug_str = $debug? '/'.$s_pos.'/'.$e_pos .'/':'';

	return substr($string, $s_pos , $e_pos ). $debug_str ;
}


/** auxiliary functions *
 * @param $file
 * @return string
 */
function file_get_size_jf( $file ) {
    //open file
    $fh = fopen( $file , "r" );
    //declare some variables
    // $size = "0";
    // $char = "";
    //set file pointer to 0; I'm a little bit paranoid, you can remove this
    fseek( $fh, 0, SEEK_SET );
    //set multiplicator to zero
    $count = 0;
    while (true) {
        //jump 1 MB forward in file
        fseek($fh, 1048576, SEEK_CUR);
        //check if we actually left the file
        if (($char = fgetc($fh)) !== false) {
            //if not, go on
            $count ++;
        } else {
            //else jump back where we were before leaving and exit loop
            fseek($fh, -1048576, SEEK_CUR);
            break;
        }
    }
    //we could make $count jumps, so the file is at least $count * 1.000001 MB large
    //1048577 because we jump 1 MB and fgetc goes 1 B forward too
    $size = bcmul("1048577", $count);
    //now count the last few bytes; they're always less than 1048576 so it's quite fast
    $fine = 0;
    while(false !== ($char = fgetc($fh))) {
        $fine ++;
    }
    //and add them
    $size = bcadd($size, $fine);
    fclose($fh);
    return $size;
}

/**
 * @param $bytes
 * @return float|string
 */
function FileSizeConvert_jf($bytes){
    $result = '';
    $bytes = floatval($bytes);
    $arBytes = array(
        0 => array(
            "UNIT" => "TB",
            "VALUE" => pow(1024, 4)
        ),
        1 => array(
            "UNIT" => "GB",
            "VALUE" => pow(1024, 3)
        ),
        2 => array(
            "UNIT" => "MB",
            "VALUE" => pow(1024, 2)
        ),
        3 => array(
            "UNIT" => "KB",
            "VALUE" => 1024
        ),
        4 => array(
            "UNIT" => "B",
            "VALUE" => 1
        ),
    );

	foreach($arBytes as $arItem)
	{
		if($bytes >= $arItem["VALUE"])
		{
			$result = $bytes / $arItem["VALUE"];
		  //  $result = str_replace(".", "," , strval(round($result, 2)))." ".$arItem["UNIT"];
			$result =  strval(round($result, 2))." ".$arItem["UNIT"];
			break;
		}
	}
	return $result;
}