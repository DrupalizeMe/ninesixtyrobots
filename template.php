<?php
// $Id$

/**
 * Implmentation of hook_theme().
 */
function ninesixtyrobots_theme() {
  return array(
    // Add our own function to override the default node form for story.
    'story_node_form' => array(
      'arguments' => array('form' => NULL),
    ),
  );
}

function ninesixtyrobots_story_node_form($form) {
  $published = drupal_render($form['options']['status']);
  $buttons = drupal_render($form['buttons']);
  $everything_else = drupal_render($form);
  return $everything_else . $published . $buttons;  
}

function ninesixtyrobots_preprocess_node(&$vars) {
  $node = $vars['node'];
  $vars['date_day'] = format_date($node->created, 'custom', 'j');
  $vars['date_month'] = format_date($node->created, 'custom', 'M');
  $vars['date_year'] = format_date($node->created, 'custom', 'Y');
}

function ninesixtyrobots_preprocess_page(&$vars) {
  $use_twitter = theme_get_setting('use_twitter');
  if (is_null($use_twitter)) {
    $use_twitter = 1;
  }

  if ($use_twitter) {
    if ($cache = cache_get('ninesixtyrobots_tweets')) {
      $data = $cache->data;
    }
    else {
      $query = theme_get_setting('twitter_search_term');
      if (is_null($query)) {
        $query = 'lullabot';
      }
      $query = drupal_urlencode($query);

      $response = drupal_http_request('http://search.twitter.com/search.json?q=' . $query);
      if ($response->code == 200) {
        $data = json_decode($response->data);
        // Set a 5 minute cache on retrieving tweets.
        // Note if this isn't updating on your site *run cron*.
        cache_set('ninesixtyrobots_tweets', $data, 'cache', 300);
      }
    }
    $tweet = $data->results[array_rand($data->results)];
    $vars['site_slogan'] = check_plain(html_entity_decode($tweet->text));
  }
}

function ninesixtyrobots_preprocess(&$vars, $hook) {
  $vars['classes'] = $hook;
}

function ninesixtyrobots_breadcrumb($breadcrumb) {
  if (!empty($breadcrumb)) {
    $breadcrumb[] = drupal_get_title();
    $delimiter = theme_get_setting('breadcrumb_delimiter');
    if (is_null($delimiter)) {
      $delimiter = ' » ';
    }
    return '<div class="breadcrumb">'. implode($delimiter, $breadcrumb) .'</div>';
  }
}

function ninesixtyrobots_username($object) {
  if ($object->uid && $object->name && module_exists('profile')) {
    
    profile_load_profile($object);
    if (!empty($object->profile_real_name)) {
      $object->name = $object->profile_real_name;
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

function ninesixtyrobots_search_theme_form($form) {
  $form['submit']['#type'] = 'image_button';
  $form['submit']['#src'] = drupal_get_path('theme', 'ninesixtyrobots') . '/images/search.png';
  $form['submit']['#attributes']['class'] = 'btn';
  return '<div id="search" class="container-inline">' . drupal_render($form) . '</div>';
}