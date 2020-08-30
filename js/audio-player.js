var wavesurfer;
var facebookURL;
var twitterURL;
var youtubeURL;
var soundcloudURL;

jQuery(document).ready(function() {
  if (jQuery('#audio-player #waveform').length > 0) {
    wavesurfer = WaveSurfer.create({
      container: '#audio-player #waveform',
      backgroundColor: '#291A3A',
      progressColor: '#8132E8',
      waveColor: '#707070',
      barWidth: 1,
      height: 48,
      maxCanvasWidth: 488
    });

    wavesurfer.on("ready", function () {

      wavesurfer.play();

      var totalTime = wavesurfer.getDuration();

      jQuery('.timestamp span').text(sec2time(totalTime));

      jQuery('.audio-btn .play-icon').addClass('hide');
      jQuery('.audio-btn .pause-icon').removeClass('hide');
    });
  }

  jQuery(document).on("click", ".audio-play-btn", function () {
    jQuery('.embed-input').val("");

    var $plain_category_name = "";
    if(jQuery(".current-cat a").length)
    {
      var audio_block_mode = jQuery('.current-cat').parent().attr('class');
      if (audio_block_mode == "jingle-theme-container")
        $plain_category_name = "2+";
      else
        $plain_category_name = "0+";
      
      $plain_category_name += jQuery('.current-cat a').attr('data-init-cat') + "+" + jQuery('.current-cat a').attr('data-init-cat-id') + "+" + jQuery(".current-cat a").attr("data-parents") + "+" + jQuery('.current-cat a').attr('data-parent-ids') + "+" + jQuery(".current-cat a").attr("current-category") + "+" + jQuery('.current-cat a').attr('current-cat_id') + "+" + jQuery('.current-cat a').attr('extra-mode');
    }
    else {
      $plain_category_name = "1+";
      $plain_category_name += jQuery(".audio-list-category-navbar li.active a").attr("data-cat");
    }

    $crypt_category_name = window.btoa($plain_category_name)
    share_link = jQuery(this).attr('data-audio-post')+window.location.pathname + "?category=" + $crypt_category_name;
    title = jQuery(this).attr('data-audio-title');
    url = jQuery(this).attr('data-audio-src');
    
    facebookURL = "https://www.facebook.com/sharer.php?u=" + share_link;
    twitterURL = "https://twitter.com/share?url=" + share_link + "&text=" + title;

    description = jQuery(this).attr('data-audio-description');
    if (url !== '' && wavesurfer) {
      wavesurfer.load(url);
    }
    if (jQuery('#audio-player').hasClass('close')) {
      jQuery('#audio-player').removeClass('close');
    }
    if (jQuery('#audio-player .share-box').hasClass('active')) {
      jQuery('#audio-player .share-box').removeClass('active');
    }

    jQuery('#audio-player .meta-info h5').html(title);
    jQuery('#audio-player .meta-info p').html(description);
  });

  jQuery('.audio-player-controller-button').click(function () {
      jQuery('.embed-input').val("");
      jQuery('#audio-player').addClass('close');
      jQuery('#audio-player .share-box').removeClass('active');
  });

  jQuery('.btn-share').click(function () {
      jQuery('#audio-player .share-box').toggleClass('active');
  });

  jQuery('.share-button').on("click", function () {
    switch (jQuery(this).attr('id'))
    {
      case "facebook":
        jQuery('.embed-input').val(facebookURL);
        break;
      case "twitter":
        jQuery('.embed-input').val(twitterURL);
        break;
    }
  });
  jQuery('.copy-button').click(function() {
    $share_link = jQuery('.embed-input').val();
    if ($share_link == "")
      return;
    jQuery('.embed-input').focus();
    jQuery('.embed-input').select();
    document.execCommand('copy');
    window.open(jQuery('.embed-input').val());
  });
  wavesurfer.on('finish', function () {
    jQuery('.audio-btn .play-icon').removeClass('hide');
    jQuery('.audio-btn .pause-icon').addClass('hide');
  });

  if (jQuery('.audio-wave').length)
  {
    var wavesurferArray = [];
    jQuery('.audio-wave').each(function(i) {

      var audio_wave = jQuery(this);
      var audio_location = jQuery(this).attr('data-src');

      wavesurferArray[i] = WaveSurfer.create({
        container: this,
        progressColor: '#8132E8',
        waveColor: '#ABBBCA',
        barWidth: 2,
        barHeight: 1,
        barGap: 4,
        height: 96,
        maxCanvasWidth: 200,
        cursorWidth: 0,
        responsive: true,
      });

      wavesurferArray[i].load(audio_location);

      wavesurferArray[i].on("ready", function () {
        var totalTime = wavesurferArray[i].getDuration();
        audio_wave.next().find('span').text(sec2time(totalTime));
      });

      wavesurferArray[i].on('audioprocess', function (){
        var currentTime = wavesurferArray[i].getCurrentTime();
        audio_wave.find('.current-time span').text(sec2time(currentTime));
        audio_wave.find('.current-time').css('left', (45 + parseInt( audio_wave.find('wave wave').css('width') )));
      });

      wavesurferArray[i].on('seek', function () {
        var currentTime = wavesurferArray[i].getCurrentTime();
        audio_wave.find('.current-time span').text(sec2time(currentTime));
        audio_wave.find('.current-time').css('left', (45 + parseInt( audio_wave.find('wave wave').css('width') )));
      });     

       wavesurferArray[i].on('finish', function () {
        jQuery('.play-action-button').eq(i).find('.play-icon').removeClass('hide');
        jQuery('.play-action-button').eq(i).find('.pause-icon').addClass('hide');
      });
    });    

    jQuery('.play-action-button').on('click', function () {
      for (var i = wavesurferArray.length - 1; i >= 0; i--) {
        if (i == jQuery('.play-action-button').index(this))
          continue;
        wavesurferArray[i].pause();
        jQuery('.play-action-button').eq(i).find('.play-icon').removeClass('hide');
        jQuery('.play-action-button').eq(i).find('.pause-icon').addClass('hide');
      }
      wavesurferArray[jQuery('.play-action-button').index(this)].playPause();
      jQuery(this).find('.play-icon').toggleClass('hide');
      jQuery(this).find('.pause-icon').toggleClass('hide');
      
    });

  }  

})

function audioPlayPause() {
  if (wavesurfer) {
    wavesurfer.playPause();
    jQuery('.audio-btn .play-icon').toggleClass('hide');
    jQuery('.audio-btn .pause-icon').toggleClass('hide');
  }
}

function sec2time(timeInSeconds) {
    var pad = function(num, size) { return ('000' + num).slice(size * -1); },
    time = parseInt(timeInSeconds);
    minutes = Math.floor(time / 60) % 60;
    seconds = Math.floor(time - minutes * 60);

    return pad(minutes, 2) + ':' + pad(seconds, 2);
}