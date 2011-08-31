<?php
/**
 * Implmentation of hook_theme().
 */
function ninesixtyrobots_theme() {
  return array(
    // Add our own function to override the default node form for story.
    'article_node_form' => array(
      'render element' => 'form',
    ),
  );
}

/**
 * Implements hook_form_FORM_ID_alter().
 */
function ninesixtyrobots_form_article_node_form_alter(&$form, &$form_state) {
  // Move the status element outside of the options fieldset so that it doesn't
  // get taken over by the vertical tabs #pre_render operation. If the status
  // element is not moved here you'll end up with duplicates when trying to
  // render the status element on it's own below.
  $form['status'] = $form['options']['status'];
  unset($form['options']['status']);
}

/**
 * Custom function to pull the Published check box out and make it obvious.
 */
function ninesixtyrobots_article_node_form($variables) {
  $form = $variables['form'];
  $published = drupal_render($form['status']);
  $buttons = drupal_render($form['actions']);
  // Make sure we also render the rest of the form, not just our custom stuff.
  $everything_else = drupal_render_children($form);
  return $everything_else . $published . $buttons;
}

/**
 * Add custom PHPTemplate variables into the node template.
 */
function ninesixtyrobots_preprocess_node(&$vars) {
  // Grab the node object.
  $node = $vars['node'];
  // Make individual variables for the parts of the date.
  $vars['date_day'] = format_date($node->created, 'custom', 'j');
  $vars['date_month'] = format_date($node->created, 'custom', 'M');
  $vars['date_year'] = format_date($node->created, 'custom', 'Y');

  // Add the .post class to all nodes.
  $vars['classes_array'][] = 'post';

  // Change the theme function used for rendering the list of tags.
  $vars['content']['field_tags']['#theme'] = 'links';
}

/**
 * Add custom PHPTemplate variable into the page template
 */
function ninesixtyrobots_preprocess_page(&$vars) {
  // Check if the theme is using Twitter.
  $use_twitter = theme_get_setting('use_twitter');
  if (is_null($use_twitter)) {
    $use_twitter = 1;
  }

  // If the theme uses Twitter pull it in and display it in the slogan.
  if ($use_twitter) {
    if ($cache = cache_get('ninesixtyrobots_tweets')) {
      $data = $cache->data;
    }
    else {
      $query = theme_get_setting('twitter_search_term');
      if (is_null($query)) {
        $query = 'lullabot';
      }
      $query = drupal_encode_path($query);

      $response = drupal_http_request('http://search.twitter.com/search.json?q=' . $query);
      if ($response->code == 200) {
        $data = json_decode($response->data);
        // Set a 5 minute cache on retrieving tweets.
        // Note if this isn't updating on your site *run cron*.
        cache_set('ninesixtyrobots_tweets', $data, 'cache', 300);
      }
    }
    $tweet = $data->results[array_rand($data->results)];
    // Create the actual variable finally.
    $vars['site_slogan'] = check_plain(html_entity_decode($tweet->text));
  }
}

/**
 * Override the breadcrumb to allow for a theme delimiter setting.
 */
function ninesixtyrobots_breadcrumb($variables) {
  $breadcrumb = $variables['breadcrumb'];
  $delimiter = theme_get_setting('breadcrumb_delimiter');
  if (is_null($delimiter)) {
    $delimiter = ' » ';
  }

  if (!empty($breadcrumb)) {
    $output = '<h2 class="element-invisible">' . t('You are here') . '</h2>';

    $output .= '<div class="breadcrumb">' . implode($delimiter, $breadcrumb) . '</div>';
    return $output;
  }
}

/**
 * Override the username display to automatically swap out username for a 
 * Profile module field called real_name, if it exists.
 */
/*
TODO: needs to be rewritten for Field API on a user object.
function ninesixtyrobots_username($object) {
  if ($object->uid && $object->name) {
    
    // Check if Profile module is enabled.
    if (module_exists('profile')) {
      profile_load_profile($object);
      // See if it has a real_name field and add that as the name instead.
      if (!empty($object->profile_real_name)) {
        $object->name = $object->profile_real_name;
      }
    }
    
    // Shorten the name when it is too long or it will break many tables.
    if (drupal_strlen($object->name) > 20) {
      $name = drupal_substr($object->name, 0, 15) .'...';
    }
    else {
      $name = $object->name;
    }

    if (user_access('access user profiles')) {
      $output = l($name, 'user/'. $object->uid, array('attributes' => array('title' => t('View user profile.'))));
    }
    else {
      $output = check_plain($name);
    }
  }
  else if ($object->name) {
    // Sometimes modules display content composed by people who are
    // not registered members of the site (e.g. mailing list or news
    // aggregator modules). This clause enables modules to display
    // the true author of the content.
    if (!empty($object->homepage)) {
      $output = l($object->name, $object->homepage, array('attributes' => array('rel' => 'nofollow')));
    }
    else {
      $output = check_plain($object->name);
    }

    $output .= ' ('. t('not verified') .')';
  }
  else {
    $output = variable_get('anonymous', t('Anonymous'));
  }

  return $output;
}
*/

/**
 * Implements hook_form_FORM_ID_alter().
 *
 * Override the search box to add our pretty graphic instead of the button.
 */
function ninesixtyrobots_form_search_block_form_alter(&$form, &$form_state) {
  $form['actions']['submit']['#type'] = 'image_button';
  $form['actions']['submit']['#src'] = drupal_get_path('theme', 'ninesixtyrobots') . '/images/search.png';
  $form['actions']['submit']['#attributes']['class'][] = 'btn';
}
