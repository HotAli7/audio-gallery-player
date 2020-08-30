# audio-gallery-player
Wordpress Plugin that show custom type post LFM Audios and Categores as Grid, List, Box and create Audio Player using wavesurfer.js

This plugin works with WORDPRESS Ajax and Shortcodes such as 
<br />
<br />
<pre>
Audio Grid       -       [audio_items_grid parent_cats=category1_slug,category2_slug extra_layouts=category3_slug,category4_slug]
Audio Box        -       [audio_items_box parent_cats=category1_slug,category2_slug ]
Audio List       -       [audio_items_list parent_cats=category1_slug,category2_slug posts_per_page=8]
Audio Player     -       [audio_player audio_location=https://demo.music.com/demo.mp3]
</pre>
<br />
<br />                             
If you want to see how to work this plugin, please click <a href="https://dev.lfmaudio.com">Here</a>.

If you want to use this plugin, 
<br />

First, please download this repo as archive - zip.
Second, Please upload this zip file to your wordpress website with LFMAudio custom post  type to install this plugin.
Third, Activate this plugin and use above shortcodes in your pages, posts, theme customiztaion.
<br />

If you want to change custom post type in your mind, please update below code in audio-gallery-player.php
<br />
<code>
    'taxonomy'      => 'lfmaudio_category'
</code>
to
<code>
    'taxonomy'      => 'your_custom_post_slug'
</code>