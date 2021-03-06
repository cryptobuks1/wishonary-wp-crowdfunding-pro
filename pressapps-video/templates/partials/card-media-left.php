<li class="<?php echo implode(" ", $classes); ?>">
    <div class="uk-card uk-card-small uk-card-default uk-grid-collapse uk-box-shadow-small uk-box-shadow-hover-medium" data-uk-grid>
        <?php if (has_post_thumbnail($post->ID)) { ?>
            <div class="uk-card-media-left uk-cover-container uk-inline uk-width-1-3@s">
                <img src="<?php echo $image[0]; ?>" alt="<?php the_title(); ?>" data-uk-cover>
                <canvas width="200" height="150"></canvas>
                <div class="overlay-primary uk-position-cover uk-transition-fade"></div>
                <div class="uk-position-center uk-transition-fade">
                    <span data-uk-icon="icon: play-circle; ratio: 2"></span>
                </div>
            </div>
        <?php } ?>
        <div class="uk-width-expand@s">
            <div class="uk-card-body uk-text-small">
                <h3 class="uk-card-title uk-margin-remove-top"><?php the_title(); ?></h3>
                <?php the_excerpt(); ?>
            </div>
        </div>
        <?php if ($pressapps_video_data[ 'lightbox' ] == true) { ?>
            <div data-uk-lightbox<?php echo $autoplay; ?>>
                <a href="<?php echo $lightbox_video; ?>" class="uk-position-cover"></a>
            </div>
        <?php } else { ?>
            <a href="#pavi-container" data-video-id="<?php the_ID(); ?>" class="uk-position-cover pavi-load-video" data-uk-scroll="offset: 100"></a>
        <?php } ?>
    </div>
</li>