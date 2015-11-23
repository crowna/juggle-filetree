/**
 * Created by crowe on 16/10/2015.
 */




(function($){
    if(typeof jQuery == "undefined"){ function jQuery(){var zip=0;}}
    if(typeof jQuery.ui.isOverAxis == "undefined"){ jQuery.ui.isOverAxis = function (e,t,n){return e>t&&e<t+n} ; }

    if(typeof fileset != "undefined") {
        /**
         * the file set is delivered by the server
         *  and will be integrated into the existing OL
         **/
        process_files(fileset)
    }

	//correction for WP wysiwyg editor
	$('div.movable').each(function(){
		if ( !$(this).children(":first").hasClass('disclose') ){
			$(this).prepend('<span class="disclose"><span></span></span>');
		}
	});


    //if adimn: start
    $('.sortable li div').css('cursor','move');
	
	show_admin_hidden();

    $('ol.sortable').nestedSortable({
        forcePlaceholderSize: true,
        handle: 'div',
        helper:	'clone',
        items: 'li',
        opacity: .6,
        placeholder: 'placeholder',
        revert: 250,
        tabSize: 25,
        tolerance: 'pointer',
        toleranceElement: '> div',
        maxLevels: 5,

        isTree: true,
        expandOnHover: 700,
        startCollapsed: true
    });
    //if adimn: end

    $('.disclose').on('click', function() {
		var li = $(this).closest('li');
		/*
		var ol = li.find('ol').first();
		if (li.hasClass('mjs-nestedSortable-collapsed') ){
			ol.show(300);
		}else{
			ol.attr('style','display: block;');
			ol.hide(300);
		}
		*/
		li.toggleClass('mjs-nestedSortable-collapsed').toggleClass('mjs-nestedSortable-expanded');
		//ol.attr('style','');
	});

    //new folder
    $('#folder').click(function(){
        $('#foldernameForm').toggle();
        $('#foldername').val('');
    });
    $('#foldernameclose').click(function(){
        $('#foldernameForm').toggle();
    });
    $('#foldercreate').click(function(){
        var name = $('#foldername').val().trim(), item_number = $('li').length + 1;
        if(name != ""){
            $('#foldernameForm').toggle();
            $('.sortable').append('<li id="list_'+item_number+'"><div class="movable"><span class="disclosex"><span></span></span><span class="juggle_folder">'+name+'</span></div></li>');
			
            makeactive();
            marktosave();
        }
    });


    $('#filehandler').hover(function(){
		if(typeof juggle_filetree_cont =="undefined" || juggle_filetree_cont =="" ) {
			juggle_filetree_cont = $('ol.sortable').nestedSortable('serialize');
		}

		if($('.need-to-save').length==0 && juggle_filetree_cont != $('.sortable').nestedSortable('serialize')){
			marktosave();
		}
    });

    $('#save-div').click( function(){
        /**
        * collect data
        * store content
        * respond to browser
        **/

        if( $(this).hasClass('need-to-save')){ //must be marked to save
            $('.fb_edit').remove(); // remove the edit buttons before uploading
			$('.new-file').removeClass('new-file');
			//$('.mjs-nestedSortable-expanded').removeClass('mjs-nestedSortable-expanded');  //this line removes the class from our display and we don't want that!
            var input = $('ol.sortable').html().replace(/\r|\t|\n|/g,'').replace(/mjs-nestedSortable-expanded/g,'mjs-nestedSortable-collapsed'),
			hid_files = $('#hidden_files').html();

            if(typeof pid == 'undefinded'){
                alert('No PID. Contact administrator.');
            }else{
                $(this).removeClass('need-to-save').addClass('waiting');
				$('#save-div').hide( "slow");
                $.ajax({
                    type: "POST",
					url: base +"/wp-admin/admin-ajax.php",
                    data: { action:'juggle_store', pid:pid, cont:input, hid_files:hid_files }
                })
                .done(function( msg ) {
                    if(msg=="done0"){
                        juggle_filetree_cont = $('ol.sortable').nestedSortable('serialize');
                        if(typeof juggle_success == "function"){
                            juggle_success($(this));
                        } else {
                            alert('Done');
                        }
                        $('#save-div').removeClass('need-to-save');
                        fit_edit_button();
                    }else {
                        if(typeof juggle_failed == "function") juggle_failed($(this)); else alert('Failed');
                    }
                })
                .fail(function( msg ) {
                    alert( "Not saved.");
                });
            }
        }
    });

    function juggle_success(element){
        $('#save-div').hide( "slow", function() {
            // Animation complete.
            $('#save-div').show("slow");
        });
    }

    fit_edit_button();
	
})(jQuery);


function show_admin_hidden(){
	//un hide these
	jQuery('.new-file').css('display','block');
	jQuery('#hidden_files').css('display','block');
}

function fit_edit_button(){
    jQuery('.movable').each(function(){ //edit button for folder names
        var folder = jQuery(this);
        if ( folder.has('button').length == 0 ){
            var txt = folder.has('a').length == 0 ? folder.text() : folder.find('a').text() ;
            folder.append('<button class="fb_edit" onclick="juggle_filetree_edit_folder(this)" data="'+txt+'"></button>');
        }
    });
}

function dump(arr,level) {
	var dumped_text = "";
	if(!level) level = 0;

	//The padding given at the beginning of the line.
	var level_padding = "";
	for(var j=0;j<level+1;j++) level_padding += "    ";

	if(typeof(arr) == 'object') { //Array/Hashes/Objects
		for(var item in arr) {
			var value = arr[item];

			if(typeof(value) == 'object') { //If it is an array,
				dumped_text += level_padding + "'" + item + "' ...";
				dumped_text += dump(value,level+1);
			} else {
				dumped_text += level_padding + "'" + item + "' => \"" + value + "\"";
			}
		}
	} else { //Strings/Chars/Numbers etc.
		dumped_text = "===>"+arr+"<===("+typeof(arr)+")";
	}
	return dumped_text;
}

function makeactive(){
	jQuery('.disclosex').on('click', function() {
		jQuery(this).closest('li').toggleClass('mjs-nestedSortable-collapsed').toggleClass('mjs-nestedSortable-expanded');
		jQuery('#save-div span').addClass('need-to-save');
	}).addClass('disclose').removeClass('disclosex');
	if ( jQuery('#files_found span').length == 0 ) {
		jQuery('#files_found').append('<span></span>');
	}
	jQuery('#files_found span').text( jQuery('.msfile').length ); //file count update
}

function marktosave(){
	jQuery('#save-div').addClass('need-to-save');
	jQuery('#save-div').css('display','inline');
	jQuery('#files_found span').text( jQuery('.msfile').length ); //file count update
}

function add_file( name, link, format, size, item_number){
	//adds formated file to our display list
	var item_number = jQuery('li').length + 1;
	if(name != ""){
		jQuery('.sortable').append('<li id="list_'+item_number+'" class="mjs-nestedSortable-leaf new-file"><div class="movable"><span class="disclosex"><span></span></span><a class="msfile '+format+'" target="_blank" href="'+link+'">'+name+'</a><span class="file-size"> - '+ size +'</span><button data="'+name+'" onclick="juggle_filetree_edit_folder(this)" class="fb_edit"></button></div></li>');
	}
}


function juggle_filetree_edit_folder( button ){
    /**
     * display div containing
     *  input type text
     *  save button
     *  close button
     *
     * function save:
     *  check for content in name: not empty (trimmed), A-Za-z plus -_ "" only
     *      failed: alert
     *      succeed: write to parent, set "needs saving flag", remove popup.
     *
     */
    juggle_filetree_close(); //close any open forms

    var fb = jQuery(button); //folder button
    var txt = fb.attr('data');
    fb.parent().addClass('fbedit');
    if ( txt != "" && fb.parent().has('div').length == 0){
        fb.parent().append('<div id="fw"><input id="writefolder_name" value="'+txt+'"><button value="save" onclick="juggle_filetree_writefolder_n_close()">go</button><button  onclick="juggle_filetree_close()" value="close">back</button><button  onclick="juggle_filetree_hide()" value="hide" title="Un list">X</button></div>');
    }
}

function juggle_filetree_writefolder_n_close(){
    var div = jQuery ('#writefolder_name'),
    div_val = div.val().trim() ;
    var noway = /<|>|\?/g ;
    if( div_val.match(noway) == null ){
        div.parent().parent().find('.juggle_folder').text( div_val ); //folder name
        div.parent().parent().find('.msfile').text( div_val ); // file name
        div.parent().parent().find('button').attr('data', div_val );
        marktosave();
        juggle_filetree_close();
    }else {
        alert('Illegal characters used!');
    }
}

function juggle_filetree_close(){
    jQuery('#fw').remove(); //delete folder-edit form
    jQuery('.fbedit').removeClass('fbedit'); //remove editing state class from folder
}

function juggle_filetree_hide(){
    //alert('no action set for juggle_filetree_hide()');

    //check it has no children , action alert
    if( jQuery('.fbedit').parent().find('ol li').length == 0 ){

        //check for #hidden_files span
        if( jQuery('#hidden_files').length == 0){
			//create within #filehandler
			jQuery('#filehandler').append('<p id="hidden_files" class="hf"><span>Hidden files</span></p>');
			show_admin_hidden();
        }

        var length = jQuery('#hidden_files a').length ;
          
		//fit to our store unless its a dead file (file not found)
		if (  !jQuery('.fbedit').hasClass('file_not_found')) {
			jQuery('#hidden_files').append('<a href="javascript:nix()" onclick="juggle_filetree_unhide(\''+jQuery('.fbedit a').attr('href')+'\',\''+length+'\')" id="hid_'+length+'">'+jQuery('.fbedit a').text()+'</a>');
        }
        jQuery('.fbedit').parent().remove();
        //add our file_name to the list
          //set page "to save"
          //remove from LI from ol.sortable
        marktosave();
    }else{
        alert('Remove its sub files first.');
    }
}
function juggle_filetree_unhide( file_name_hidden , id){
    // will add_file(), and remove from #hidden_files list
    var item_number = jQuery('li').length + 1;

    for( var i=0; i<fileset.length; i++ ){
        //the server checked for the file in the page content and marked it in this JS list
        if( fileset[i][1] == file_name_hidden )
            add_file( fileset[i][0], fileset[i][1], fileset[i][2], fileset[i][3], item_number);
    }
    jQuery('#hid_'+id).remove();
	show_admin_hidden();
    makeactive();
    marktosave();
}

/**
 * 1) list/display files found that aren't already displayed
 * 2) mark files displayed that don't exist on the server
 * @param fileset
 */
function process_files(fileset){

    // 1)
    var item_number = jQuery('li').length + 1,
        fileset_names = '';

    for( var i=0; i<fileset.length; i++ ){
        if( !fileset[i][4] ){
        //the server checked for the file in the page content and marked it in this JS list
            add_file( fileset[i][0], fileset[i][1], fileset[i][2], fileset[i][3], item_number);
            item_number++;
        }
		
        fileset_names += fileset[i][1] + ',';
    }

    // 2)
    jQuery('.msfile').each(function(){
        if( fileset_names.indexOf ( jQuery(this).attr('href') ) == -1 ){
            jQuery(this).parent().addClass('file_not_found').attr('title','FILE MISSING or NOT REGISTERED IN WP DB');
        }
    });

    //update mechanism
    makeactive();
}


function nix(){
    /*do nothing*/
    var t=0;
}

function aux_wpmfu( r ){
   /**
    * This action is triggerd by the plugin "wp-multi-file-uploader" on ajax completion of uploading.
	* It responds with the info on each file and then adds the file to our list.
	*
	* The ajax success action needs the new row at line 30 "if(typeof aux_wpmfu=='function') aux_wpmfu(response);" or ""function"==typeof aux_wpmfu&&aux_wpmfu(i),"
	*	must be patched into the file wp-multi-file-uploader\assets\js\fineuploader-execute.js
	* 	wp-multi-file-uploader\assets\js\fineuploader.min.js
	 */
	 jQuery('.qq-upload-list li').each(function(){ 
		var li = jQuery(this);
		if( li.hasClass('qq-upload-success') && !li.hasClass('processing') ){
			
			li.addClass('processing');
			
			var file = li.find('.qq-upload-file').text(),
				suf = file.substr( file.lastIndexOf('.')+1) ,
				size = li.find('.qq-upload-size').text();
			file = file.substr( 0, file.lastIndexOf('.') );
			file = file.replace('_',' ');
			//add item to monitor
			
			//display
			add_file(  file , r.success.url , suf , size, jQuery('li').length + 1 );
			
			//fit ajax here to set the attachment owner
			jQuery.ajax({
				type: "POST",
				url: base +"/wp-admin/admin-ajax.php",
				data: { action:'juggle_attach', pid:pid, aid:r.attachmentId }
			})
			.done(function( msg ) {
				if(msg=="done0"){
					li.addClass('done');
				}else {
					if(typeof juggle_failed == "function"){
						juggle_failed($(this));
					}else{
						alert('Parent set error pid:'+pid+', aid:'+r.attachmentId+'\n'+msg);
					}
				}
			})
			.fail(function( msg ) {
				alert( 'Not moved - pid:'+pid+', aid:'+r.attachmentId);
			});
			
			
		}
	 });
	show_admin_hidden();
    marktosave();
}