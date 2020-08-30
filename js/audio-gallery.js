jQuery(document).ready(function() {
  let pageNumber        = 0; 	//number of page
  let pageSize          = 3; 	//size of page - number of audios that show on one page.
  let init_cat          = '';	//initial category name
  let init_cat_id       = 0;	//initial category id
  let category          = ''; //current category name
  let cat_id            = 0; 	//current category id
  let parent_id         = -1;	//parent category ids.
  let parent            = '';	//parent category names.
  let isReset           = false;	
  let extra_layout_mode = 0;	//mode that show audios - 0: default, 1: thumbnail

  $ids          = jQuery('.current-cat a').attr('data-init-cat-id');
  $categories   = jQuery('.current-cat a').attr('data-init-cat');
  if ($ids && $ids !== '') {
    $ids          = $ids.split(',');
    $categories   = $categories.split(',');
    init_cat_id   = $ids.pop();
    init_cat      = $categories.pop();
    cat_id        = init_cat_id;
    category      = init_cat;
  }
  
  function expandCategory() {

    if (jQuery('#audio-player').length > 0) {
      if (!jQuery('#audio-player').hasClass('close')) {
        jQuery('#audio-player').addClass('close');
        jQuery('#audio-player .share-box').removeClass('active');
        if (wavesurfer) {
          wavesurfer.stop(url);
        }
      }
    }
    
    pageNumber++;
    let q = '&show_mode=' + extra_layout_mode + '&cat_id=' + cat_id + '&paged=' + pageNumber + '&page_size=' + pageSize + '  &action=expand_category_ajax';
    jQuery('.audio-categories .lds-dual-ring').css('visibility', 'visible');

    jQuery.ajax({
      type: "POST",
      dataType: 'html',
      url: ajax_posts.ajaxurl,
      data: q,
      success: function (data) {
        let $data = jQuery(data);
        if (pageNumber < 2)
          jQuery('.audio-categories .wrapper').empty();
        jQuery('.audio-categories .wrapper .show-more').remove();
        jQuery('.audio-categories .wrapper').append($data);
        jQuery('.audio-categories .current-cat a').attr('current-cat_id', cat_id);
        jQuery('.audio-categories .current-cat a').attr('current-category', category);
        jQuery('.audio-categories .current-cat a').attr('extra-mode', extra_layout_mode);

        if (!isReset && parent_id != -1) {
          $parents = jQuery('.audio-categories .current-cat a').attr('data-parents');
          $parent_ids = jQuery('.audio-categories .current-cat a').attr('data-parent-ids');
          if (parent !== '')
            $parents += ($parents === '') ? parent : ',' + parent;
          $parent_ids += ($parent_ids === '') ? parent_id : ',' + parent_id;
          jQuery('.audio-categories .current-cat a').attr('data-parents', $parents);
          jQuery('.audio-categories .current-cat a').attr('data-parent-ids', $parent_ids);
          parent = '';
          parent_id = -1;
        }
        if (isReset) {
          jQuery('.audio-categories .current-cat a').attr('data-parents', '');
          jQuery('.audio-categories .current-cat a').attr('data-parent-ids', '');
          isReset = false;
        }
        if (cat_id != init_cat_id) {
          jQuery('.audio-categories .current-cat h3').html(category);
          jQuery('.audio-categories .current-cat').css('visibility', 'visible');
        } else {
          jQuery('.audio-categories .current-cat').css('visibility', 'hidden');
        }
        jQuery('.audio-categories .lds-dual-ring').css('visibility', 'hidden');

        var allListElements = jQuery( 'h3' );
        if ( jQuery('.audio-gallery-section').find('.audio-item').length || extra_layout_mode != '')
        {
          jQuery('.audio-gallery-section').addClass("audio-section");
          jQuery('.audio-section').find(allListElements).attr("style", "color: #FFF !important");
        }
        else
        {          
          jQuery('.audio-gallery-section').removeClass("audio-section");
          jQuery('.audio-gallery-section').find(allListElements).removeAttr("style");
        }
      },
      error: function () {
        console.error('Error while loading posts');
        jQuery('.audio-categories .lds-dual-ring').css('visibility', 'hidden');
      }
    })

  }

  jQuery(document).on("click", ".audio-categories .expand-category", function() {
    $term_id = jQuery(this).attr('data-term-id');
    $term = jQuery(this).attr('data-term');
    pageNumber = 0;
    parent_id = cat_id;
    parent = category;
    category = $term;
    cat_id = $term_id;
    
    expandCategory();
  });

  jQuery('.audio-categories .current-cat a').click(function () {
    pageNumber = 0;
    $ids = jQuery(this).attr('data-parent-ids');
    $categories = jQuery(this).attr('data-parents');
    if ($ids === '')
      return;
    $ids = $ids.split(',');
    $categories = $categories.split(',');
    $cat_id = $ids.pop();
    $category = $categories.pop();
    if ($cat_id === '')
      return;
    cat_id = $cat_id;
    category = $category;
    expandCategory();
    $ids = $ids.join(',');
    $categories = $categories.join(',');
    jQuery(this).attr('data-parent-ids', $ids);
    jQuery(this).attr('data-parents', $categories);
  });

  jQuery('.category-navbar ul li a').click(function () {
    id = jQuery(this).attr('data-cat-id')
    if (id !== '') {
      pageNumber = 0;
      init_cat_id = id;
      init_cat = jQuery(this).attr('data-cat');
      cat_id = init_cat_id;
      category = init_cat;
      parent_id = -1;
      parent = '';
      $ids = jQuery('.audio-categories .current-cat a').attr('data-init-cat-id', init_cat_id);
      $categories = jQuery('.audio-categories .current-cat a').attr('data-init-cat', init_cat);
      isReset = true;

      jQuery('.category-navbar ul li').removeClass('active');
      jQuery(this).parent().addClass('active');

      extra_layout_mode = jQuery(this).attr('data-extra-mode');
      expandCategory();
    }
  });

  jQuery(document).on('click', '.show-more .show-more-button', function() {
    expandCategory();
  })

  
  function expandThumbnailCategory() {

    if (jQuery('#audio-player').length > 0) {
      if (!jQuery('#audio-player').hasClass('close')) {
        jQuery('#audio-player').addClass('close');
        jQuery('#audio-player .share-box').removeClass('active');
        if (wavesurfer) {
          wavesurfer.stop(url);
        }
      }
    }

    pageNumber++;
    let q = '&cat_id=' + cat_id + '&paged=' + pageNumber + '&page_size=' + pageSize + '&show_mode=' + extra_layout_mode + '  &action=expand_jingle_theme_ajax';
    jQuery('.jingle-theme-container .lds-dual-ring').css('visibility', 'visible');

    jQuery.ajax({
      type: "POST",
      dataType: 'html',
      url: ajax_posts.ajaxurl,
      data: q,
      success: function (data) {
        let $data = jQuery(data);
        if (pageNumber < 2)
          jQuery('.jingle-theme-container .theme-wrapper').empty();
        jQuery('.jingle-theme-container .theme-wrapper .show-more').remove();
        jQuery('.jingle-theme-container .theme-wrapper').append($data);
        jQuery('.jingle-theme-container .jingle-themes').css('display', 'none');
        jQuery('.jingle-theme-container .current-cat a').attr('current-cat_id', cat_id);
        jQuery('.jingle-theme-container .current-cat a').attr('current-category', category);
        jQuery('.jingle-theme-container .current-cat a').attr('extra-mode', extra_layout_mode);
        if (!isReset && parent_id != -1) {
          $parents = jQuery('.jingle-theme-container .current-cat a').attr('data-parents');
          $parent_ids = jQuery('.jingle-theme-container .current-cat a').attr('data-parent-ids');
          if (parent !== '')
            $parents += ($parents === '') ? parent : ',' + parent;
          $parent_ids += ($parent_ids === '') ? parent_id : ',' + parent_id;
          jQuery('.jingle-theme-container .current-cat a').attr('data-parents', $parents);
          jQuery('.jingle-theme-container .current-cat a').attr('data-parent-ids', $parent_ids);
          parent = '';
          parent_id = -1;
        }
        if (isReset) {
          jQuery('.jingle-theme-container .current-cat a').attr('data-parents', '');
          jQuery('.jingle-theme-container .current-cat a').attr('data-parent-ids', '');
          isReset = false;
        }
        if (cat_id != init_cat_id) {
          jQuery('.jingle-theme-container .current-cat h3').html(category);
          jQuery('.jingle-theme-container .current-cat').css('visibility', 'visible');
        } else {
          jQuery('.jingle-theme-container .current-cat').css('visibility', 'hidden');
        }
        jQuery('.jingle-theme-container .lds-dual-ring').css('visibility', 'hidden');

        var allListElements = jQuery( 'h3' );
        jQuery('.jingle-theme-section').addClass("audio-section");
        jQuery('.audio-section').find(allListElements).attr("style", "color: #FFF !important");
      },
      error: function () {
        console.error('Error while loading posts');
        jQuery('.jingle-theme-container .lds-dual-ring').css('visibility', 'hidden');
      }
    })

  }

  jQuery(document).on("click", ".jingle-theme-container .expand-category", function() {
    $term_id = jQuery(this).attr('data-term-id');
    $term = jQuery(this).attr('data-term');
    pageNumber = 0;
    parent_id = cat_id;
    parent = category;
    category = $term;
    cat_id = $term_id;
    
    extra_layout_mode = jQuery(this).attr('data-extra-mode');

    expandThumbnailCategory();
  });

  jQuery(document).on("click", ".jingle-theme-container .back-to-jingle-theme", function() {

    pageNumber = 0;
    init_cat_id = jQuery(this).attr('data-init-cat-id');
    $ids = jQuery(this).attr('data-parent-ids');
    $categories = jQuery(this).attr('data-parents');
    if ($ids === '')
      return;
    $ids = $ids.split(',');
    $categories = $categories.split(',');
    $cat_id = $ids.pop();
    $category = $categories.pop();
    if ($cat_id === '')
      return;
    cat_id = $cat_id;
    category = $category;
    if (init_cat_id == $cat_id)
    {
      if (jQuery('#audio-player').length > 0) {
        if (!jQuery('#audio-player').hasClass('close')) {
          jQuery('#audio-player').addClass('close');
          jQuery('#audio-player .share-box').removeClass('active');
          if (wavesurfer) {
            wavesurfer.stop(url);
          }
        }
      }

      jQuery(this).attr('data-parent-ids', '');
      jQuery(this).attr('data-parents', '');

      jQuery('.jingle-theme-container .theme-wrapper').empty();
      jQuery('.jingle-theme-container .jingle-themes').css('display', 'flex');

      var allListElements = jQuery( 'h3' ); 
      jQuery('.jingle-theme-section').removeClass("audio-section");
      jQuery('.jingle-theme-section').find(allListElements).removeAttr("style");
      jQuery('.jingle-theme-container .current-cat').css('visibility', 'hidden');
    }
    else
    {
      expandThumbnailCategory();
      $ids = $ids.join(',');
      $categories = $categories.join(',');
      jQuery(this).attr('data-parent-ids', $ids);
      jQuery(this).attr('data-parents', $categories);
    }
  });

  jQuery('.audio-list-category-navbar ul li a').click(function () {
    id = jQuery(this).attr('data-cat-id');
    if (id !== '') {
      
      jQuery('.audio-list-category-navbar ul li').removeClass('active');
      jQuery(this).parent().addClass('active');
      
      jQuery('.audio-list').css('display', 'none');
      jQuery('.audio-list.audio-list-'+id).css('display', 'block');
    }
  });


  /**
   * Display Audio Gallery from social share link
   * @param array currentURI          social share link param
   * @param array uriParams           split the share link to params for .current-cat a
   * @param number audio_block_mode   If 0 then Home page, If 1 then DJ DROPS page, If 2 then JINGLES page
   */
   
  var currentURI = decodeURIComponent(window.location.search.substring(1)).split('=');
  
  if (currentURI[0] == "category")
  {
    try {
      var uriParams = window.atob(currentURI[1]).split('+');
      var audio_block_mode = uriParams[0];

      if (audio_block_mode == 0)
      {
        console.log("default");

        init_cat          = uriParams[1];
        init_cat_id       = uriParams[2];
        parent            = uriParams[3];
        parent_id         = uriParams[4];
        category          = uriParams[5];
        cat_id            = uriParams[6];
        extra_layout_mode = uriParams[7];

        jQuery('.current-cat a').attr('data-init-cat',    uriParams[1]);
        jQuery('.current-cat a').attr('data-init-cat-id', uriParams[2]);
        jQuery('.current-cat a').attr('data-parents',     uriParams[3]);
        jQuery('.current-cat a').attr('data-parent-ids',  uriParams[4]);
        jQuery('.current-cat a').attr('current-category', uriParams[5]);
        jQuery('.current-cat a').attr('current-cat_id',   uriParams[6]);
        jQuery('.current-cat a').attr('extra-mode',       uriParams[7]);

        jQuery('.category-navbar ul li').removeClass('active');

        jQuery('.category-navbar ul li').each(function(i){
          var category_id = jQuery(this).find('a').attr('data-cat-id');
          if (init_cat_id == category_id)
            jQuery(this).addClass('active');
        })
        expandCategory();
      }
      else if (audio_block_mode == 1) 
      {
        console.log("list");
      }
      else if (audio_block_mode == 2)
      {
        console.log("extra");

        init_cat          = uriParams[1];
        init_cat_id       = uriParams[2];
        parent            = uriParams[3];
        parent_id         = uriParams[4];
        category          = uriParams[5];
        cat_id            = uriParams[6];
        extra_layout_mode = uriParams[7];

        jQuery('.current-cat a').attr('data-init-cat',    uriParams[1]);
        jQuery('.current-cat a').attr('data-init-cat-id', uriParams[2]);
        jQuery('.current-cat a').attr('data-parents',     uriParams[3]);
        jQuery('.current-cat a').attr('data-parent-ids',  uriParams[4]);
        jQuery('.current-cat a').attr('current-category', uriParams[5]);
        jQuery('.current-cat a').attr('current-cat_id',   uriParams[6]);
        jQuery('.current-cat a').attr('extra-mode',       uriParams[7]);

        expandThumbnailCategory();
      }
    } catch (err) {
      console.log(false);
    }
  } 

});