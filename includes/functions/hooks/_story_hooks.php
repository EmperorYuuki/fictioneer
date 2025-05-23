<?php

// =============================================================================
// STATISTICS FOR ALL STORIES
// =============================================================================

/**
 * Outputs the statistics section for all stories
 *
 * Renders a statistics block with the number of published stories as well as
 * word count, comments, and the estimated reading time for all stories. The
 * reading time divisor can be changed under Fictioneer > General (default: 200).
 *
 * Note: Added conditionally in the `wp` action hook (priority 10).
 *
 * @since 5.0.0
 * @see stories.php
 *
 * @param int      $args['current_page']  Current page number of pagination or 1.
 * @param int      $args['post_id']       The post ID.
 * @param WP_Query $args['stories']       Paginated query of all published stories.
 * @param string   $args['queried_type']  The queried post type ('fcn_story').
 */

function fictioneer_stories_statistics( $args ) {
  // Look for cached value (purged after each update, should never be stale)
  $statistics = get_transient( 'fictioneer_stories_statistics' );

  // Compute statistics if necessary
  if ( ! $statistics ) {
    $words = fictioneer_get_stories_total_word_count();

    $statistics = array(
      'stories' => array(
        'label' => __( 'Stories', 'fictioneer' ),
        'content' => number_format_i18n( wp_count_posts( 'fcn_story' )->publish )
      ),
      'words' => array(
        'label' => _x( 'Words', 'Word count caption in statistics.', 'fictioneer' ),
        'content' => fictioneer_shorten_number( $words )
      ),
      'comments' => array(
        'label' => __( 'Comments', 'fictioneer' ),
        'content' => number_format_i18n(
          get_comments(
            array(
              'post_type' => 'fcn_chapter',
              'status' => 1,
              'count' => true,
              'update_comment_meta_cache' => false
            )
          )
        )
      ),
      'reading' => array(
        'label' => __( 'Reading', 'fictioneer' ),
        'content' => fictioneer_get_reading_time_nodes( $words )
      ),
    );

    // Apply filter
    $statistics = apply_filters( 'fictioneer_filter_stories_statistics', $statistics, $args );

    // Cache for next time
    set_transient( 'fictioneer_stories_statistics', $statistics, HOUR_IN_SECONDS );
  }

  // Start HTML ---> ?>
  <div class="stories__statistics statistics spacing-top">
    <?php foreach ( $statistics as $stat ) : ?>
      <div class="statistics__inline-stat">
        <strong><?php echo $stat['label']; ?></strong>
        <span><?php echo $stat['content']; ?></span>
      </div>
    <?php endforeach; ?>
  </div>
  <?php // <--- End HTML
}
add_action( 'fictioneer_stories_after_content', 'fictioneer_stories_statistics', 10 );

// =============================================================================
// LIST OF ALL STORIES
// =============================================================================

/**
 * Outputs the paginated card list for all stories
 *
 * Note: Added conditionally in the `wp` action hook (priority 10).
 *
 * @since 5.0.0
 * @see stories.php
 *
 * @param int        $args['current_page']  Current page number of pagination or 1.
 * @param int        $args['post_id']       The post ID of the page.
 * @param WP_Query   $args['stories']       Paginated query of all published stories.
 * @param string     $args['queried_type']  The queried post type ('fcn_story').
 * @param array      $args['query_args']    The query arguments used.
 * @param string     $args['order']         Current order. Default 'desc'.
 * @param string     $args['orderby']       Current orderby. Default 'modified'.
 * @param int|string $args['ago']           Current date query argument part. Default 0.
 */

function fictioneer_stories_list( $args ) {
  // Start HTML ---> ?>
  <section class="stories__list spacing-top container-inline-size">
    <ul id="list-of-stories" class="scroll-margin-top card-list">

      <?php if ( $args['stories']->have_posts() ) : ?>

        <?php
          // Card arguments
          $card_args = array(
            'cache' => fictioneer_caching_active( 'card_args' ) && ! fictioneer_private_caching_active(),
            'order' => $args['order'] ?? 'desc',
            'orderby' => $args['orderby'] ?? 'modified',
            'ago' => $args['ago'] ?? 0
          );

          // Filter card arguments
          $card_args = apply_filters( 'fictioneer_filter_stories_card_args', $card_args, $args );

          while ( $args['stories']->have_posts() ) {
            $args['stories']->the_post();

            if ( get_post_meta( get_the_ID(), 'fictioneer_story_hidden', true ) ) {
              get_template_part( 'partials/_card-hidden', null, $card_args );
            } else {
              get_template_part( 'partials/_card-story', null, $card_args );
            }
          }

          // Actions at end of results
          do_action( 'fictioneer_stories_end_of_results', $args );
        ?>

      <?php else : ?>

        <?php do_action( 'fictioneer_stories_no_results', $args ); ?>

        <li class="no-results">
          <span><?php _e( 'No stories found.', 'fictioneer' ); ?></span>
        </li>

      <?php endif; wp_reset_postdata(); ?>

      <?php
        $pag_args = array(
          'current' => max( 1, get_query_var( 'paged' ) ),
          'total' => $args['stories']->max_num_pages,
          'prev_text' => fcntr( 'previous' ),
          'next_text' => fcntr( 'next' ),
          'add_fragment' => '#list-of-stories'
        );
      ?>

      <?php if ( $args['stories']->max_num_pages > 1 ) : ?>
        <li class="pagination"><?php echo fictioneer_paginate_links( $pag_args ); ?></li>
      <?php endif; ?>

    </ul>
  </section>
  <?php // <--- End HTML
}
add_action( 'fictioneer_stories_after_content', 'fictioneer_stories_list', 30 );

// =============================================================================
// STORY COPYRIGHT NOTICE
// =============================================================================

/**
 * Outputs the HTML for the story page copyright notice
 *
 * Note: Added conditionally in the `wp` action hook (priority 10).
 *
 * @since 5.0.0
 *
 * @param array $args['story_data']  Collection of story data.
 * @param int   $args['story_id']    The story post ID.
 */

function fictioneer_story_copyright_notice( $args ) {
  // Setup
  $copyright_notice = get_post_meta( $args['story_id'], 'fictioneer_story_copyright_notice', true );

  // Abort conditions...
  if ( empty( $copyright_notice ) ) {
    return;
  }

  // Start HTML ---> ?>
  <section class="story__copyright-notice">
    <i class="fa-regular fa-copyright"></i> <?php echo $copyright_notice; ?>
  </section>
  <?php // <--- End HTML
}
add_action( 'fictioneer_story_after_content', 'fictioneer_story_copyright_notice', 10 );

// =============================================================================
// STORY TAGS & WARNINGS
// =============================================================================

/**
 * Outputs the HTML for the story page tags and warnings
 *
 * Note: Added conditionally in the `wp` action hook (priority 10).
 *
 * @since 5.0.0
 *
 * @param array $args['story_data']  Collection of story data.
 * @param int   $args['story_id']    The story post ID.
 */

function fictioneer_story_tags_and_warnings( $args ) {
  // Abort conditions...
  $tags_shown = $args['story_data']['tags'] &&
    ! get_option( 'fictioneer_hide_tags_on_pages' ) &&
    ! get_post_meta( $args['story_id'], 'fictioneer_story_no_tags', true );
  $warnings_shown = $args['story_data']['warnings'] && ! get_option( 'fictioneer_hide_content_warnings_on_pages' );

  if ( ! $tags_shown && ! $warnings_shown ) {
    return;
  }

  // Setup
  $tag_args = [];

  if ( $tags_shown ) {
    $tag_args['tags'] = $args['story_data']['tags'];
  }

  if ( $warnings_shown ) {
    $tag_args['warnings'] = $args['story_data']['warnings'];
  }

  // Start HTML ---> ?>
  <section class="story__tags-and-warnings tag-group"><?php
    echo fictioneer_get_taxonomy_pills( $tag_args, 'story_after_content', '_secondary' );
  ?></section>
  <?php // <--- End HTML
}
add_action( 'fictioneer_story_after_content', 'fictioneer_story_tags_and_warnings', 20 );

// =============================================================================
// STORY ACTIONS ROW
// =============================================================================

/**
 * Outputs the HTML for the story page actions row
 *
 * Note: Added conditionally in the `wp` action hook (priority 10).
 *
 * @since 5.0.0
 *
 * @param array      $args['story_data']         Collection of story data.
 * @param int        $args['story_id']           The story post ID.
 * @param bool|null  $args['password_required']  Whether the post is unlocked or not.
 */

function fictioneer_story_actions( $args ) {
  // Abort conditions...
  if ( $args['password_required'] ?? post_password_required() ) {
    return;
  }

  // Start HTML ---> ?>
  <section class="story__after-summary">
    <?php echo fictioneer_get_media_buttons(); ?>
    <div class="story__actions"><?php echo fictioneer_get_story_buttons( $args ); ?></div>
  </section>
  <?php // <--- End HTML
}
add_action( 'fictioneer_story_after_content', 'fictioneer_story_actions', 30 );

// =============================================================================
// STORY FILTERS
// =============================================================================

/**
 * Output the HTML for the story filter reel row.
 *
 * Note: Added conditionally in the `wp` action hook (priority 10).
 *
 * @since 5.29.0
 *
 * @param array      $args['story_data']         Collection of story data.
 * @param int        $args['story_id']           The story post ID.
 * @param bool|null  $args['password_required']  Whether the post is unlocked or not.
 */

function fictioneer_story_filter_reel( $args ) {
  // Abort conditions...
  if ( $args['password_required'] ?? post_password_required() ) {
    return;
  }

  // Setup
  $filters = get_post_meta( $args['story_id'] ?? 0, 'fictioneer_story_filters', true ) ?: [];

  if ( ! is_array( $filters ) || empty( $filters ) ) {
    return;
  }

  $image_ids = array_filter( array_column( $filters, 'image_id' ) );

  if ( ! empty( $image_ids ) ) {
    new WP_Query(
      array(
        'post_type' => 'attachment', // Query all attachments at once to prepare cache
        'post_status' => 'inherit',
        'post__in' => $image_ids, // Must not be empty!
        'posts_per_page' => -1,
        'ignore_sticky_posts' => true,
        'update_post_term_cache' => false, // Improve performance
        'no_found_rows' => true // Improve performance
      )
    );
  }

  $splide = array(
    'type' => 'slide',
    'gap' => '1rem',
    'arrows' => false,
    'fixedWidth' => '7.25rem',
    'clones' => 0,
    'autoplay' => false,
    'pagination' => true,
    'perPage' => 2,
    'mediaQuery' => 'min',
    'breakpoints' => array(
      '400' => array(
        'perPage' => 3
      ),
      '550' => array(
        'perPage' => 4
      ),
      '768' => array(
        'perPage' => 5
      ),
      '1024' => array(
        'perPage' => 6
      )
    )
  );

  $splide = apply_filters( 'fictioneer_filter_filter_reel_splide', $splide, $filters, $args );

  // HTML
  echo '<section class="story__filter-reel splide" data-fictioneer-story-target="filterReel" data-splide="' . esc_attr( json_encode( $splide ) ) . '"><button type="button" class="story__filter-reel-toggle" data-fictioneer-story-target="filterToggle"></button><div class="story__filter-reel-wrapper splide__track"><ul class="story__filter-reel-list splide__list">';

  foreach ( $filters as $filter ) {
    $image_id = ( $filter['image_id'] ?? 0 ) ?: get_theme_mod( 'default_story_cover', 0 );
    $label = $filter['label'] ?? '';

    if ( $image_id ) {
      printf(
        '<div class="story__filter-reel-item %s splide__slide" data-fictioneer-story-target="filterItem">%s%s<a href="%s" class="story__filter-reel-item-button _lightbox" %s>%s</a>%s</div>',
        $label ? '' : '_no-label',
        wp_get_attachment_image(
          $image_id,
          'snippet',
          false,
          array( 'class' => 'story__filter-reel-item-img', 'loading' => 'lazy' )
        ),
        $label ? '<div class="story__filter-reel-item-label"><span class="truncate _2-2">' . $label .'</span></div>' : '',
        wp_get_attachment_image_url( $image_id, 'full' ),
        get_option( 'fictioneer_enable_lightbox' ) ? 'data-lightbox' : 'target="_blank"',
        '<i class="fa-solid fa-magnifying-glass-plus icon"></i>',
        ( empty( $filter['groups'] ) && empty( $filter['ids'] ) ) ? '' :
          sprintf(
            '<button type="button" class="story__filter-reel-item-button _filter" data-action="click->fictioneer-story#selectFilter" data-fictioneer-story-groups-param="%s" data-fictioneer-story-ids-param="%s"></button>',
            implode( ',', $filter['groups'] ),
            implode( ',', $filter['ids'] )
          )
      );
    }
  }

  echo '</ul></div></section>';
}

if ( get_option( 'fictioneer_enable_story_filter_reel' ) ) {
  add_action( 'fictioneer_story_after_content', 'fictioneer_story_filter_reel', 41 );
}

// =============================================================================
// STORY TABS
// =============================================================================

/**
 * Outputs the HTML for the story tabs
 *
 * Note: Added conditionally in the `wp` action hook (priority 10).
 *
 * @since 5.9.0
 *
 * @param array      $args['story_data']         Collection of story data.
 * @param int        $args['story_id']           The story post ID.
 * @param bool|null  $args['password_required']  Whether the post is unlocked or not.
 */

function fictioneer_story_tabs( $args ) {
  // Abort conditions...
  if ( $args['password_required'] ?? post_password_required() ) {
    return;
  }

  // Setup
  $story_id = $args['story_id'];
  $story = $args['story_data'];
  $custom_pages = get_post_meta( $story_id, 'fictioneer_story_custom_pages', true );
  $blog_posts = fictioneer_get_story_blog_posts( $story_id );
  $tab_pages = [];

  if ( is_array( $custom_pages ) ) {
    foreach ( $custom_pages as $page_id ) {
      if ( get_post_status( $page_id ) === 'publish' ) {
        $tab_pages[] = [$page_id, get_post_meta( $page_id, 'fictioneer_short_name', true ), get_the_content( null, false, $page_id )];
      }
    }
  }

  // Start HTML ---> ?>
  <section id="tabs-<?php echo $story_id; ?>" class="story__tabs tabs-wrapper" data-fictioneer-story-target="tabSection" data-current="chapters" data-order="asc" data-view="list">

    <div class="tabs">
      <button class="tabs__item _current" data-fictioneer-story-target="tab" data-fictioneer-story-tab-name-param="chapters" data-action="click->fictioneer-story#toggleTab" data-target="chapters" tabindex="0"><?php
        if ( $story['status'] === 'Oneshot' ) {
          echo fcntr( 'Oneshot' );
        } else {
          printf(
            _x( '%1$s %2$s', 'Story chapter tab with count.', 'fictioneer' ),
            $story['chapter_count'],
            _n( 'Chapter', 'Chapters', $story['chapter_count'], 'fictioneer' )
          );
        }
      ?></button>

      <?php if ( $blog_posts->have_posts() ) : ?>
        <button class="tabs__item" data-fictioneer-story-target="tab" data-fictioneer-story-tab-name-param="blog" data-action="click->fictioneer-story#toggleTab" data-target="blog" tabindex="0"><?php echo fcntr( 'story_blog' ); ?></button>
      <?php endif; ?>

      <?php
        if ( $custom_pages ) {
          $index = 1;

          foreach ( $tab_pages as $page ) {
            if ( empty( $page[1] ) ) {
              continue;
            }

            echo "<button class='tabs__item' data-fictioneer-story-target='tab' data-fictioneer-story-tab-name-param='tab-page-{$index}' data-action='click->fictioneer-story#toggleTab' data-target='tab-page-{$index}' tabindex='0'>{$page[1]}</button>";

            $index++;

            if ( $index > FICTIONEER_MAX_CUSTOM_PAGES_PER_STORY ) {
              break; // Only show 4 custom tabs
            }
          }
        }
      ?>
    </div>

    <?php if ( $story['status'] !== 'Oneshot' || $story['chapter_count'] > 1 ) : ?>
      <div class="story__chapter-list-toggles">
        <button data-action="click->fictioneer-story#toggleChapterView" class="list-button story__toggle _view" data-view="list" tabindex="0" aria-label="<?php esc_attr_e( 'Toggle between list and grid view', 'fictioneer' ); ?>">
          <?php fictioneer_icon( 'grid-2x2', 'on' ); ?>
          <i class="fa-solid fa-list off"></i>
        </button>
        <button data-action="click->fictioneer-story#toggleChapterOrder" class="list-button story__toggle _order" data-order="asc" tabindex="0" aria-label="<?php esc_attr_e( 'Toggle between ascending and descending order', 'fictioneer' ); ?>">
          <i class="fa-solid fa-arrow-down-1-9 off"></i>
          <i class="fa-solid fa-arrow-down-9-1 on"></i>
        </button>
      </div>
    <?php endif; ?>

  </section>
  <?php // <--- End HTML
}
add_action( 'fictioneer_story_after_content', 'fictioneer_story_tabs', 40 );

// =============================================================================
// STORY SCHEDULED CHAPTER
// =============================================================================

/**
 * Outputs the HTML for the scheduled chapter
 *
 * Note: Added conditionally in the `wp` action hook (priority 10).
 *
 * @since 5.9.0
 *
 * @param array      $args['story_data']         Collection of story data.
 * @param int        $args['story_id']           The story post ID.
 * @param bool|null  $args['password_required']  Whether the post is unlocked or not.
 */

function fictioneer_story_scheduled_chapter( $args ) {
  // Abort conditions...
  if ( $args['password_required'] ?? post_password_required() ) {
    return;
  }

  // Setup
  $scheduled_chapters = get_posts(
    array(
      'post_type' => 'fcn_chapter',
      'post_status' => 'future',
      'posts_per_page' => 1,
      'orderby' => 'date',
      'order' => 'ASC',
      'update_post_term_cache' => false, // Improve performance
      'update_post_meta_cache' => false, // Improve performance
      'no_found_rows' => true, // Improve performance
      'meta_query' => array(
        array(
          'key' => 'fictioneer_chapter_story',
          'value' => $args['story_id'],
        ),
      )
    )
  );

  // Abort if no chapters are scheduled
  if ( empty( $scheduled_chapters ) ) {
    return;
  }

  // Start HTML ---> ?>
  <section class="story__tab-target story__scheduled-chapter _current" data-fictioneer-story-target="tabContent" data-tab-name="chapters">
    <i class="fa-solid fa-calendar-days"></i>
    <span><?php
      printf(
        _x( 'Next Chapter: %1$s, %2$s', 'Scheduled chapter note with date and time.', 'fictioneer' ),
        get_the_date( '', $scheduled_chapters[0] ),
        get_the_time( '', $scheduled_chapters[0] )
      );
    ?></span>
  </section>
  <?php // <--- End HTML
}
add_action( 'fictioneer_story_after_content', 'fictioneer_story_scheduled_chapter', 41 );

// =============================================================================
// STORY PAGES
// =============================================================================

/**
 * Outputs the HTML for the story custom pages
 *
 * Note: Added conditionally in the `wp` action hook (priority 10).
 *
 * @since 5.9.0
 *
 * @param array      $args['story_data']         Collection of story data.
 * @param int        $args['story_id']           The story post ID.
 * @param bool|null  $args['password_required']  Whether the post is unlocked or not.
 */

function fictioneer_story_pages( $args ) {
  // Abort conditions...
  if ( $args['password_required'] ?? post_password_required() ) {
    return;
  }

  // Setup
  $story_id = $args['story_id'];
  $custom_pages = get_post_meta( $story_id, 'fictioneer_story_custom_pages', true );
  $tab_pages = [];

  if ( is_array( $custom_pages ) ) {
    foreach ( $custom_pages as $page_id ) {
      if ( get_post_status( $page_id ) === 'publish' ) {
        $tab_pages[] = [$page_id, get_post_meta( $page_id, 'fictioneer_short_name', true ), get_the_content( null, false, $page_id )];
      }
    }
  }

  // Output
  if ( $custom_pages ) {
    $index = 1;

    foreach ( $tab_pages as $page ) {
      if ( empty( $page[1] ) ) {
        continue;
      }

      // Start HTML ---> ?>
      <section class="story__tab-target content-section background-texture" data-fictioneer-story-target="tabContent" data-tab-name="tab-page-<?php echo $index; ?>">
        <div class="story__custom-page"><?php echo apply_filters( 'the_content', $page[2] ); ?></div>
      </section>
      <?php // <--- End HTML

      $index++;

      if ( $index > FICTIONEER_MAX_CUSTOM_PAGES_PER_STORY ) {
        break; // Only show 4 custom tabs
      }
    }
  }
}
add_action( 'fictioneer_story_after_content', 'fictioneer_story_pages', 42 );

// =============================================================================
// STORY CHAPTERS
// =============================================================================

/**
 * Outputs the HTML for the story chapters
 *
 * Note: Added conditionally in the `wp` action hook (priority 10).
 *
 * @since 5.9.0
 *
 * @param array      $args['story_data']         Collection of story data.
 * @param int        $args['story_id']           The story post ID.
 * @param bool|null  $args['password_required']  Whether the post is unlocked or not.
 */

function fictioneer_story_chapters( $args ) {
  // Abort conditions...
  if ( $args['password_required'] ?? post_password_required() ) {
    return;
  }

  $enable_transients = fictioneer_enable_chapter_list_transients( $args['story_id'] );

  // Check for cached chapters output
  if ( $enable_transients ) {
    $transient_cache = get_transient( 'fictioneer_story_chapter_list_html_' . $args['story_id'] );

    if ( $transient_cache ) {
      echo $transient_cache;
      return;
    }
  }

  // Setup
  $story_id = $args['story_id'];
  $story = $args['story_data'];
  $prefer_chapter_icon = get_option( 'fictioneer_override_chapter_status_icons' );
  $hide_icons = get_post_meta( $story_id, 'fictioneer_story_hide_chapter_icons', true ) ||
    get_option( 'fictioneer_hide_chapter_icons' );
  $disable_folding = get_post_meta( $story_id, 'fictioneer_story_disable_collapse', true );
  $collapse_groups = get_option( 'fictioneer_collapse_groups_by_default' );

  // Capture output
  ob_start();

  // Start HTML ---> ?>
  <section class="story__tab-target _current story__chapters" data-fictioneer-story-target="tabContent" data-tab-name="chapters" data-order="asc" data-view="list">
    <?php
      $chapters = fictioneer_get_story_chapter_posts( $story_id );
      $chapter_groups = fictioneer_prepare_chapter_groups( $story_id, $chapters );
      $group_classes = [];

      // Hide icons?
      if ( $hide_icons ) {
        $group_classes[] = '_no-icons';
      }

      // Pre-collapse?
      if ( $collapse_groups && count( $chapter_groups ) > 1 ) {
        $group_classes[] = '_closed';
      }

      // Build HTML
      if ( ! empty( $chapter_groups ) ) {
        $group_index = 0;
        $has_groups = count( $chapter_groups ) > 1 && get_option( 'fictioneer_enable_chapter_groups' );

        // Loop over groups (or one group for all if disabled)...
        foreach ( $chapter_groups as $key => $group ) {
          $group_index++;

          $group = apply_filters( 'fictioneer_filter_chapter_group', $group, $group_index, $story_id );

          $index = 0;
          $reverse_order = 99999;
          $group_item_count = count( $group['data'] );
          $chapter_folding = ! $disable_folding && ! get_option( 'fictioneer_disable_chapter_collapsing' );
          $chapter_folding = $chapter_folding && count( $group['data'] ) >= FICTIONEER_CHAPTER_FOLDING_THRESHOLD * 2 + 3;
          $aria_label = __( 'Toggle chapter group: %s', 'fictioneer' );

          // Start HTML ---> ?>
          <div id="chapter-group-<?php echo $key ?: 'unassigned'; ?>" class="chapter-group <?php echo implode( ' ', array_merge( $group_classes, $group['classes'] ?? [] ) ); ?>" data-folded="true" data-fictioneer-story-target="chapterGroup">

            <?php if ( $has_groups ) : ?>
              <button
                class="chapter-group__name <?php echo implode( ' ', $group['classes'] ?? [] ); ?>"
                aria-label="<?php echo esc_attr( sprintf( $aria_label, $group['group'] ) ); ?>"
                data-item-count="<?php echo esc_attr( $group_item_count ); ?>"
                data-group-index="<?php echo esc_attr( $group_index ); ?>"
                data-action="click->fictioneer#toggleChapterGroup"
                tabindex="0"
              >
                <i class="<?php echo $group['toggle_icon']; ?> chapter-group__heading-icon"></i>
                <span data-item-count="<?php echo esc_attr( $group_item_count ); ?>"><?php
                  echo $group['group'];
                ?></span>
              </button>
            <?php endif; ?>

            <ol class="chapter-group__list">
              <?php foreach ( $group['data'] as $chapter ) : ?>
                <?php
                  $index++;
                  $extra_classes = "_{$chapter['status']} ";

                  // Must account for extra toggle row and start at 1
                  $is_folded = $chapter_folding && $index > FICTIONEER_CHAPTER_FOLDING_THRESHOLD &&
                    $index < ( $group_item_count + 2 - FICTIONEER_CHAPTER_FOLDING_THRESHOLD );

                  if ( $is_folded ) {
                    $extra_classes .= ' _foldable';
                  }

                  if ( $chapter['password'] ) {
                    $extra_classes .= ' _password';
                  }
                ?>

                <?php if ( $chapter_folding && $index == FICTIONEER_CHAPTER_FOLDING_THRESHOLD + 1 ) : ?>
                  <li class="chapter-group__list-item _folding-toggle" style="order: <?php echo $reverse_order - $index; ?>" data-group="<?php echo $key; ?>">
                    <button class="chapter-group__folding-toggle" data-action="click->fictioneer-story#unfoldChapters" tabindex="0">
                      <?php
                        printf(
                          __( 'Show %s more', 'fictioneer' ),
                          $group_item_count - FICTIONEER_CHAPTER_FOLDING_THRESHOLD * 2
                        );
                      ?>
                    </button>
                  </li>
                  <?php $index++; ?>
                <?php endif; ?>

                <li
                  class="chapter-group__list-item <?php echo $extra_classes; ?>"
                  style="order: <?php echo $reverse_order - $index; ?>"
                  data-group="<?php echo $key; ?>"
                  data-post-id="<?php echo $chapter['id']; ?>"
                >

                  <?php
                    if ( ! $hide_icons ) {
                      // Icon hierarchy: password > scheduled > text > normal
                      if ( ! $prefer_chapter_icon && $chapter['password'] ) {
                        $icon = '<i class="fa-solid fa-lock chapter-group__list-item-icon"></i>';
                      } elseif ( ! $prefer_chapter_icon && $chapter['status'] === 'future' ) {
                        $icon = '<i class="fa-solid fa-calendar-days chapter-group__list-item-icon"></i>';
                      } elseif ( $chapter['text_icon'] ) {
                        $icon = "<span class='chapter-group__list-item-icon _text text-icon'>{$chapter['text_icon']}</span>";
                      } else {
                        $icon = $chapter['icon'] ?: FICTIONEER_DEFAULT_CHAPTER_ICON;
                        $icon = "<i class='{$icon} chapter-group__list-item-icon'></i>";
                      }

                      echo apply_filters( 'fictioneer_filter_chapter_icon', $icon, $chapter['id'], $story_id );
                    }
                  ?>

                  <a
                    <?php echo $chapter['link'] ? "href='{$chapter['link']}'" : ''; ?>
                    class="chapter-group__list-item-link truncate _1-1 <?php echo $chapter['password'] ? '_password' : ''; ?>"
                  ><?php

                    $title_output = '';

                    // Non-published chapter prefixes
                    if ( in_array( $chapter['status'], ['future', 'trash', 'private'] ) ) {
                      $status_prefix = fcntr( "{$chapter['status']}_prefix" );

                      if ( $status_prefix ) {
                        $title_output .= '<span class="chapter-group__list-item-status">' . $status_prefix . '</span> '; // Mind the space
                      }
                    }

                    $chapter['prefix'] = apply_filters(
                      'fictioneer_filter_list_chapter_prefix',
                      $chapter['prefix'], $chapter['id'], 'story'
                    );

                    if ( ! empty( $chapter['prefix'] ) ) {
                      // Mind space between prefix and title
                      $title_output .= $chapter['prefix'] . ' ';
                    }

                    if ( ! empty( $chapter['list_title'] ) && $chapter['title'] !== $chapter['list_title'] ) {
                      $title_output .= sprintf(
                        ' <span class="chapter-group__list-item-title list-view">%s</span><span class="grid-view">%s</span>',
                        $chapter['title'],
                        wp_strip_all_tags( $chapter['list_title'] )
                      );
                    } else {
                      $title_output .= $chapter['title'];
                    }

                    echo apply_filters(
                      'fictioneer_filter_list_chapter_title_row',
                      $title_output, $chapter['id'], $chapter['prefix'], $chapter['password'], 'story'
                    );

                  ?></a>

                  <?php if ( $chapter['password'] ) : ?>
                    <i class="fa-solid fa-lock icon-password grid-view"></i>
                  <?php endif; ?>

                  <?php echo fictioneer_get_list_chapter_meta_row( $chapter, array( 'grid' => true ) ); ?>

                  <?php if ( get_option( 'fictioneer_enable_checkmarks' ) ) : ?>
                    <button
                      class="checkmark chapter-group__list-item-checkmark only-logged-in"
                      data-fictioneer-checkmarks-target="chapterCheck"
                      data-fictioneer-checkmarks-story-param="<?php echo $story_id; ?>"
                      data-fictioneer-checkmarks-chapter-param="<?php echo $chapter['id']; ?>"
                      data-action="click->fictioneer-checkmarks#toggleChapter"
                      role="checkbox"
                      aria-checked="false"
                      aria-label="<?php
                        printf(
                          esc_attr__( 'Chapter checkmark for %s.', 'fictioneer' ),
                          esc_attr( wp_strip_all_tags( $chapter['title'] ) )
                        );
                      ?>"
                    ><i class="fa-solid fa-check"></i></button>
                  <?php endif; ?>

                </li>
              <?php endforeach; ?>
            </ol>

          </div>
          <?php // <--- End HTML
        }
      } elseif ( $story['status'] !== 'Oneshot' ) {
        // Start HTML ---> ?>
        <div class="chapter-group <?php echo implode( ' ', $group_classes ); ?>">
          <ol class="chapter-group__list">
            <li class="chapter-group__list-item _empty">
              <span><?php _e( 'No chapters published yet.', 'fictioneer' ); ?></span>
            </li>
          </ol>
        </div>
        <?php // <--- End HTML
      }
    ?>
  </section>
  <?php // <--- End HTML

  // Store output
  $chapters_html = ob_get_clean();

  // Compress output
  $chapters_html = fictioneer_minify_html( $chapters_html );

  // Flush buffered output
  echo $chapters_html;

  // Cache for next time (24 hours)
  if ( $enable_transients ) {
    set_transient( 'fictioneer_story_chapter_list_html_' . $story_id, $chapters_html, 86400 );
  }
}
add_action( 'fictioneer_story_after_content', 'fictioneer_story_chapters', 43 );

// =============================================================================
// STORY BLOG
// =============================================================================

/**
 * Outputs the HTML for the story blog
 *
 * Note: Added conditionally in the `wp` action hook (priority 10).
 *
 * @since 5.9.0
 *
 * @param array      $args['story_data']         Collection of story data.
 * @param int        $args['story_id']           The story post ID.
 * @param bool|null  $args['password_required']  Whether the post is unlocked or not.
 */

function fictioneer_story_blog( $args ) {
  // Abort conditions...
  if ( $args['password_required'] ?? post_password_required() ) {
    return;
  }

  // Setup
  $story_id = $args['story_id'];
  $blog_posts = fictioneer_get_story_blog_posts( $story_id );

  // Start HTML ---> ?>
  <section class="story__blog story__tab-target" data-fictioneer-story-target="tabContent" data-tab-name="blog">
    <ol class="story__blog-list">
      <?php
        if ( $blog_posts->have_posts() ) {
          while ( $blog_posts->have_posts() ) {
            $blog_posts->the_post();
            // Start HTML ---> ?>
            <li class="story__blog-list-item">
              <div class="story__blog-list-item-wrapper">
                <span class="story__blog-title">
                  <a class="story__blog-link" href="<?php the_permalink(); ?>"><?php the_title(); ?></a>:
                </span>
                <span class="story__blog-content"><?php echo fictioneer_get_limited_excerpt( 160 ); ?></span>
              </div>
            </li>
            <?php // <--- End HTML
          }
        }
        wp_reset_postdata();
      ?>
    </ol>
  </section>
  <?php // <--- End HTML
}
add_action( 'fictioneer_story_after_content', 'fictioneer_story_blog', 44 );

// =============================================================================
// STORY COMMENTS
// =============================================================================

/**
 * Outputs the HTML for the story page comments
 *
 * Note: Added conditionally in the `wp` action hook (priority 10).
 *
 * @since 5.0.0
 * @since 5.14.0 - Merged partial into function.
 *
 * @param array       $args['story_data']  Collection of story data.
 * @param int         $args['story_id']    The story post ID.
 * @param string|null $args['classes']     Optional. Additional CSS classes, separated by whitespace.
 * @param bool|null   $args['header']      Optional. Whether to show the heading with count. Default true.
 * @param string|null $attr['style']       Optional. Inline style applied to the wrapper element.
 * @param bool|null   $args['shortcode']   Optional. Whether the render context is a shortcode. Default false.
 */

function fictioneer_story_comments( $args ) {
  // Setup
  $story = $args['story_data'];
  $classes = $args['classes'] ?? '';
  $style = $args['style'] ?? '';
  $args['header'] = filter_var( $args['header'] ?? 1, FILTER_VALIDATE_BOOLEAN );

  // Abort conditions...
  if ( post_password_required() || $story['comment_count'] < 1 ) {
    return;
  }

  // Start HTML ---> ?>
  <section class="comment-section fictioneer-comments <?php echo esc_attr( $classes ); ?>" style="<?php echo esc_attr( $style ); ?>">
    <?php if ( $args['header'] ) : ?>
      <h2 class="fictioneer-comments__title"><?php
        printf(
          _n(
            '<span>%s</span> <span>Comment</span>',
            '<span>%s</span> <span>Comments</span>',
            $story['comment_count'],
            'fictioneer'
          ),
          number_format_i18n( $story['comment_count'] )
        );
      ?></h2>
    <?php endif; ?>
    <?php do_action( 'fictioneer_story_before_comments_list', $args ); ?>
    <div class="fictioneer-comments__list" data-fictioneer-story-target="commentsWrapper">
      <ul>
        <li class="load-more-list-item" data-fictioneer-story-target="commentsList">
          <button class="load-more-comments-button" data-action="click->fictioneer-story#loadComments"><?php
            $load_n = $story['comment_count'] < get_option( 'comments_per_page' ) ?
              $story['comment_count'] : get_option( 'comments_per_page' );

            printf(
              _n(
                'Load latest comment (may contain spoilers)',
                'Load latest %s comments (may contain spoilers)',
                $load_n,
                'fictioneer'
              ),
              $load_n
            );
          ?></button>
        </li>
        <div class="comments-loading-placeholder hidden" data-fictioneer-story-target="commentsPlaceholder"><i class="fa-solid fa-spinner spinner"></i></div>
      </ul>
    </div>
  </section>
  <?php // <--- End HTML
}
add_action( 'fictioneer_story_after_article', 'fictioneer_story_comments', 10 );
