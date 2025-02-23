<?php
include_once(PEM_PATH . 'include/functions.inc.php');

function get_user_lang_id()
{
  global $user;

  $query = '
  SELECT id_language
    FROM '.PEM_LANG_TABLE.' AS l
    LEFT JOIN '.PEM_EXT_TRANS_TABLE.'  AS et
      ON l.id_language = et.idx_language
    WHERE l.code = "'.$user['language'].'"
     GROUP BY id_language
  ;';
  
  $result = pwg_db_fetch_assoc(pwg_query($query));
  $id_lang = $result['id_language'];

  return $id_lang;
}

/* this file contains all functions for extensions management */

/**
 * retutns an associative array (id=>name) of all versions available
 */
function get_version_name_of()
{
  $version_name_of = array();

  $query = '
SELECT id_version,
       version
  FROM '.PEM_VER_TABLE.'
;';
  $result = pwg_query($query);

  while ($row = pwg_db_fetch_assoc($result))
  {
    $version_name_of[ $row['id_version'] ] = $row['version'];
  }

  return $version_name_of;
}

/**
 * returns available versions ids for each revision
 */
function get_version_ids_of_revision($revision_ids)
{
  if (count($revision_ids) == 0)
  {
    return array();
  }
  
    // Get list of compatibilities
  $version_ids_of = array();
  
  $query = '
SELECT idx_version,
       idx_revision
  FROM '.PEM_COMP_TABLE.'
  WHERE idx_revision IN ('.implode(',', $revision_ids).')
;';
  
  $result = pwg_query($query);
  
  while ($row = pwg_db_fetch_assoc($result))
  {
    if (!isset($version_ids_of[ $row['idx_revision'] ]))
    {
      $version_ids_of[ $row['idx_revision'] ] = array();
    }
    
    array_push(
      $version_ids_of[ $row['idx_revision'] ],
      $row['idx_version']
      );
  }

  return $version_ids_of;
}

/**
 * returns available versions names for each revision
 */
function get_versions_of_revision($revision_ids)
{
  $versions_of = array();
  $version_ids_of = get_version_ids_of_revision($revision_ids);
  $version_name_of = get_version_name_of();

  foreach ($revision_ids as $revision_id)
  {
    $versions_of[$revision_id] = array();

    if (!isset($version_ids_of[$revision_id])) {
      $versions_of[$revision_id] = array('none');
      continue;
    }

    foreach ($version_ids_of[$revision_id] as $version_id)
    {
      array_push(
        $versions_of[$revision_id],
        $version_name_of[$version_id]
        );
    }

    $versions_of[$revision_id] = array_reverse(versort($versions_of[$revision_id]));
  }

  return $versions_of;
}

/**
 * returns available versions ids for each extension
 */
function get_version_ids_of_extension($extension_ids)
{  
  // first we find the revisions associated to each extension
  $query = '
SELECT id_revision,
       idx_extension
  FROM '.PEM_REV_TABLE.'
  WHERE idx_extension IN ('.implode(',', $extension_ids).')
;';

  $revision_ids = array();
  $revisions_of = array();

  $result = pwg_query($query);
  while ($row = pwg_db_fetch_assoc($result))
  {
    // add the revision id to the list of all revisions
    array_push($revision_ids, $row['id_revision']);

    // add the revision id to the list of revision to a particular extension.
    if (!isset($revisions_of[ $row['idx_extension'] ]))
    {
      $revisions_of[ $row['idx_extension'] ] = array();
    }
    array_push(
      $revisions_of[ $row['idx_extension'] ],
      $row['id_revision']
      );
  }

  $version_ids_of_revision = get_version_ids_of_revision($revision_ids);
  $version_ids_of_extension = array();

  foreach ($extension_ids as $extension_id)
  {
    $version_ids_of_extension[$extension_id] = array();

    if (isset($revisions_of[$extension_id])) {
      foreach ($revisions_of[$extension_id] as $revision_id)
      {
        if (isset($version_ids_of_revision[$revision_id])) {
          $version_ids_of_extension[$extension_id] = array_merge(
            $version_ids_of_extension[$extension_id],
            $version_ids_of_revision[$revision_id]
            );
        }
      }
    }

    $version_ids_of_extension[$extension_id] =
      array_unique($version_ids_of_extension[$extension_id]);
  }

  return $version_ids_of_extension;
}

/**
 * returns available versions names for each extension
 */
function get_versions_of_extension($extension_ids)
{
  $versions_of = array();
  $version_ids_of = get_version_ids_of_extension($extension_ids);
  $version_name_of = get_version_name_of();

  foreach ($extension_ids as $extension_id)
  {
    $versions_of[$extension_id] = array();

    foreach ($version_ids_of[$extension_id] as $version_id)
    {
      array_push(
        $versions_of[$extension_id],
        $version_name_of[$version_id]
        );
    }

    natcasesort($versions_of[$extension_id]);
  }

  return $versions_of;
}

/**
 * returns basic infos of each revision
 */
function get_revision_infos_of($revision_ids)
{
  $revision_infos_of = array();

  $id_lang = get_user_lang_id();

  // retrieve revisions information
  $query = '
SELECT
    id_revision,
    version,
    date,
    idx_extension,
    r.description AS default_description,
    r.idx_language,
    url,
    accept_agreement,
    author,
    rt.description
  FROM '.PEM_REV_TABLE.' AS r
    LEFT JOIN '.PEM_REV_TRANS_TABLE.' AS rt
    ON r.id_revision = rt.idx_revision
    AND rt.idx_language = '.$id_lang.'
  WHERE id_revision IN ('.implode(',', $revision_ids).')
;';

  $result = query2array($query);

  //Original function in pem uses while
  foreach ($result as $item)
  {
    if (empty($item['description']))
    {
      $item['description'] = $item['default_description'];
    }
    $revision_infos_of[ $item['id_revision'] ] = $item;
  }

  return $revision_infos_of;
}

/**
 * return basic infos of each extension
 */
function get_extension_infos_of($extension_ids)
{
  global $user;

  $extension_infos_of = array();

  $ids_string = '';
  if (is_array($extension_ids))
  {
    $ids_string = implode(',', $extension_ids);
  }
  else
  {
    $ids_string = $extension_ids;
  }
  
  $query = '
SELECT 
    COUNT(1) AS count,
    idx_extension
  FROM '.PEM_REVIEW_TABLE.'
  WHERE 
    idx_extension IN ('.$ids_string.')
    '.(get_user_status() =='admin' ? 'AND validated = "true"' : null).'
  GROUP BY idx_extension
;';

  $nb_reviews = query2array($query, 'idx_extension', 'count');
  
  $id_lang = get_user_lang_id();

  $query = '
SELECT id_extension,
       name,
       rating_score,
       idx_user,
       svn_url,
       git_url,
       e.description AS default_description,
       et.description
  FROM '.PEM_EXT_TABLE.' AS e
  LEFT JOIN '.PEM_EXT_TRANS_TABLE.' AS et
    ON e.id_extension = et.idx_extension
    AND et.idx_language = '.$id_lang.'
  WHERE id_extension IN ('.$ids_string.')
;';
  $result = pwg_query($query);

  while ($row = pwg_db_fetch_assoc($result))
  {
    $row['nb_reviews'] = !empty($nb_reviews[ $row['id_extension'] ]) ? $nb_reviews[ $row['id_extension'] ] : 0;
    if (empty($row['description']))
    {
      $row['description'] = $row['default_description'];
    }
    if (is_array($extension_ids))
    {
      $extension_infos_of[ $row['id_extension'] ] = $row;
    }
    else
    {
      return $row;
    }
  }
  
  return $extension_infos_of;
}

/**
 * returns ids of extensions that don't have any revision
 */
function get_extension_ids_without_revision()
{
  $query = '
SELECT id_extension
  FROM '.PEM_EXT_TABLE.'
;';
  $all_extension_ids = query2array($query, null, 'id_extension');

  $query = '
SELECT DISTINCT idx_extension
  FROM '.PEM_REV_TABLE.'
;';
  $non_empty_extension_ids = query2array($query, null, 'idx_extension');

  return array_diff($all_extension_ids, $non_empty_extension_ids);
}

/**
 * delete revisions and associated informations (version compatibilities)
 */
function delete_revisions($revision_ids)
{
  if (count($revision_ids) == 0)
  {
    return false;
  }

  $revision_infos_of = get_revision_infos_of($revision_ids);

  foreach ($revision_ids as $revision_id)
  {
    @unlink(
      get_revision_src(
        $revision_infos_of[$revision_id]['idx_extension'],
        $revision_id,
        $revision_infos_of[$revision_id]['url']
        )
      );
  }

  $query = '
DELETE
  FROM '.PEM_COMP_TABLE.'
  WHERE idx_revision IN ('.implode(',', $revision_ids).')
;';
  pwg_query($query);

  $query = '
DELETE
  FROM '.PEM_REV_TABLE.'
  WHERE id_revision IN ('.implode(',', $revision_ids).')
;';
  pwg_query($query);
}

/**
 * gets the path of the extension directory
 */
function get_extension_dir($extension_id)
{
  global $conf;
  return PHPWG_ROOT_PATH.$conf['upload_dir'].'extension-'.$extension_id;
}

/**
 * gets the path of the revision directory
 */
function get_revision_src($extension_id, $revision_id, $url)
{
  global $conf;

  return get_extension_dir($extension_id)
    .'/revision-'.$revision_id
    .'/'.$url
  ;
}

/**
 * gets extension thumbnail url
 */
function get_extension_thumbnail_src($extension_id)
{
  return get_extension_dir($extension_id).'/thumbnail.jpg';
}

/**
 * gets extension screenshot url
 */
function get_extension_screenshot_src($extension_id)
{ 
  return get_extension_dir($extension_id).'/screenshot.jpg';
}

/**
 * gets extension thumbnail and screenshot urls
 */
function get_extension_screenshot_infos($extension_id)
{
  $thumbnail_src  = get_extension_thumbnail_src($extension_id);
  $screenshot_src = get_extension_screenshot_src($extension_id);

  if (is_file($thumbnail_src) and is_file($screenshot_src))
  {
    $stat = stat( get_extension_dir($extension_id).'/screenshot.jpg');
    return array(
      'thumbnail_src'  => $thumbnail_src,
      'screenshot_url' => $screenshot_src.'?'.$stat['mtime'],
      );
  }
  else
  {
    return false;
  }
}

/**
 * increments the number of downloads for a revision
 */
function log_download($revision_id)
{  
  $revision_infos_of = get_revision_infos_of(array($revision_id));

  if (count($revision_infos_of) == 0) {
    return false;
  }

  $query = '
SELECT CURDATE()
;';
  list($curdate) = pwg_db_fetch_row(pwg_query($query));
  list($curyear, $curmonth, $curday) = explode('-', $curdate);

  $query = '
INSERT INTO '.PEM_DOWNLOAD_LOG_TABLE.'
  (
    year,
    month,
    day,
    IP,
    idx_revision
  )
  VALUES
  (
    '.$curyear.',
    '.$curmonth.',
    '.$curday.',
    \''.$_SERVER['REMOTE_ADDR'].'\',
    '.$revision_id.'
  )
;';
  pwg_query($query);

  $query = '
UPDATE '.PEM_REV_TABLE.'
  SET nb_downloads = nb_downloads + 1
  WHERE id_revision = '.$revision_id.'
;';
  pwg_query($query);
}

/**
 * returns the number of downloads for an extension
 */
function get_download_of_extension($extension_ids) {
  global $user;

  if (count($extension_ids) == 0) {
    return array();
  }

  $downloads_of_extension = array();

  foreach ($extension_ids as $id) {
    $downloads_of_extension[$id] = 0;
  }

  $query = '
SELECT
    idx_extension AS extension_id,
    SUM(nb_downloads) AS sum_downloads
  FROM '.PEM_REV_TABLE.'
  WHERE idx_extension IN ('.implode(',', $extension_ids).')
  GROUP BY idx_extension
;';

  $result = pwg_query($query);

  while ($row = pwg_db_fetch_assoc($result)) {
    if("fr_FR" == $user['language'])
    {
      $downloads_of_extension[ $row['extension_id'] ] = number_format($row['sum_downloads'], 0, ',', ' ');
    }
    else{
      $downloads_of_extension[ $row['extension_id'] ] = number_format($row['sum_downloads']);
    }
  }

  return $downloads_of_extension;
}

/**
 * returns the number of downloads for a revision
 */
function get_download_of_revision($revision_ids) {  
  global $user;

  if (count($revision_ids) == 0) {
    return array();
  }

  $downloads_of_revision = array();

  foreach ($revision_ids as $id) {
    $downloads_of_revision[$id] = 0;
  }

  $query = '
SELECT
    id_revision,
    nb_downloads
  FROM '.PEM_REV_TABLE.'
  WHERE id_revision IN ('.implode(',', $revision_ids).')
;';
  $result = pwg_query($query);

  while ($row = pwg_db_fetch_assoc($result)) {
    if("fr_FR" == $user['language'])
    {
      $downloads_of_revision[ $row['id_revision'] ] = number_format($row['nb_downloads'], 0, ',', ' ');
    }
    else{
      $downloads_of_revision[ $row['id_revision'] ] = number_format($row['nb_downloads']);
    }
  }

  return $downloads_of_revision;
}

/**
 * returns extensions published in these categories (and/or mode)
 */
function get_extension_ids_for_categories($category_ids, $mode=null)
{
  if (count($category_ids) == 0)
  {
    return array();
  }

  if (!in_array($mode, array('or', 'and')))
  {
    $mode = 'and';
  }

  // strategy is to list images associated to each category
  $eids_for_category = array();

  if ($mode == 'and') {
    foreach ($category_ids as $cid) {
      $query = '
SELECT idx_extension
  FROM '.PEM_EXT_CAT_TABLE.'
  WHERE idx_category = '.$cid.'
;';
      $eids_for_category[$cid] = query2array($query, null, 'idx_extension');
    }
  
    // then we calculate the intersection, the images that are associated to
    // every tags
    $eids = array_shift($eids_for_category);
    foreach ($eids_for_category as $category_ids) {
      $eids = array_intersect($eids, $category_ids);
    }
  }
  else {
    $query = '
SELECT
    DISTINCT(idx_extension)
  FROM '.PEM_EXT_CAT_TABLE.'
  WHERE idx_category IN ('.implode(',', $category_ids).')
;';
    $eids = query2array($query, null, 'idx_extension');
  }

  return array_unique($eids);
}

/**
 * returns extensions published with these tags (and/or mode)
 */
function get_extension_ids_for_tags($tag_ids, $mode=null) {
  if (count($tag_ids) == 0) {
    return array();
  }

  if (!in_array($mode, array('or', 'and'))) {
    $mode = 'and';
  }

  // strategy is to list images associated to each category
  $eids_for_tag = array();

  if ($mode == 'and') {
    foreach ($tag_ids as $tid) {
      $query = '
SELECT idx_extension
  FROM '.PEM_EXT_TAG_TABLE.'
  WHERE idx_tag = '.$tid.'
;';
      $eids_for_tag[$tid] = query2array($query, null, 'idx_extension');
    }
  
    // then we calculate the intersection, the images that are associated to
    // every tags
    $eids = array_shift($eids_for_tag);
    foreach ($eids_for_tag as $tag_ids) {
      $eids = array_intersect($eids, $tag_ids);
    }
  }
  else {
    $query = '
SELECT
    DISTINCT(idx_extension)
  FROM '.PEM_EXT_TAG_TABLE.'
  WHERE idx_tag IN ('.implode(',', $tag_ids).')
;';
    $eids = query2array($query, null, 'idx_extension');
  }

  return array_unique($eids);
}

/**
 * returns categories ids of a set of extensions
 */
function get_categories_of_extension($extension_ids) {  
  $cat_list_for = array();

  $id_lang = get_user_lang_id();

  $query = '
SELECT
    id_category,
    c.name AS default_name,
    ct.name,
    idx_extension
  FROM '.PEM_EXT_CAT_TABLE.' AS ec
    JOIN '.PEM_CAT_TABLE.' AS c
      ON id_category = ec.idx_category
    LEFT JOIN '.PEM_CAT_TRANS_TABLE.' AS ct
      ON id_category = ct.idx_category
      AND ct.idx_language = '.$id_lang.'
  WHERE idx_extension IN ('.implode(',', $extension_ids).')
;';

  $result = pwg_query($query);
  while ($row = pwg_db_fetch_assoc($result))
  {

    if(empty($row['idx_extension']))
    {
      continue;
    }

    $id_extension = $row['idx_extension'];

    if (empty($row['name']))
    {
      $row['name'] = $row['default_name'];
    }
    
    if (!isset($cat_list_for[$id_extension])) {
      $cat_list_for[$id_extension] = array();
    }

    $cat_list_for[$id_extension]['id_category']= $row['id_category'];
    $cat_list_for[$id_extension]['default_name']= $row['default_name'];
    $cat_list_for[$id_extension]['name']= $row['name'];

  }
  
  $categories_of_extension = array();
  foreach ($extension_ids as $extension_id) {
    if (isset($cat_list_for[$extension_id]))
    {
      $categories_of_extension[$extension_id] = $cat_list_for[$extension_id];
    }
  }

  return $categories_of_extension;
}

/**
 * returns tags id of a set of extensions
 */
function get_tags_of_extension($extension_ids) {  
  $tag_list_for = array();
  
  $query = '
SELECT idx_extension,
       id_tag,
       name
  FROM '.PEM_TAG_TABLE.' AS t
  LEFT JOIN '.PEM_EXT_TAG_TABLE.' AS et
    ON et.idx_tag = t.id_tag
  WHERE et.idx_extension IN ('.implode(',', $extension_ids).')
;';
  $result = pwg_query($query);
  
  while ($row = pwg_db_fetch_assoc($result)) {

    $id_extension = $row['idx_extension'];

    if (!isset($tag_list_for[$id_extension])) {
      $tag_list_for[$id_extension] = array();
    }

    array_push( $tag_list_for[$id_extension],$row);
  }

  $tags_of_extension = array();

  if (!empty($tag_list_for))
  {
    foreach ($extension_ids as $extension_id) {
      if (!empty($tag_list_for[$extension_id]))
      {
        $tags_of_extension[$extension_id] = $tag_list_for[$extension_id];
      }
    }
  }

  return $tags_of_extension;
}

/**
 * returns extensions available for a version
 */
function get_extension_ids_for_version($id_version) {
  $query = '
SELECT
    DISTINCT id_extension
  FROM '.PEM_EXT_TABLE.' AS e
    JOIN '.PEM_REV_TABLE.' AS r ON r.idx_extension = e.id_extension
    JOIN '.PEM_COMP_TABLE.' AS c ON c.idx_revision = r.id_revision
  WHERE idx_version = '.$id_version.'
;';
  return query2array($query, null, 'id_extension');
}

/**
 * returns extensions published by an user
 */
function get_extension_ids_for_user($user_id) {
  $query = '
  SELECT
      id_extension
    FROM '.PEM_EXT_TABLE.'
    WHERE idx_user = '.$user_id.'
  UNION ALL
  SELECT
      idx_extension AS id_extension
    FROM '.PEM_AUTHORS_TABLE.'
    WHERE idx_user = '.$user_id.'
  ;';

  return array_unique(query2array($query, null, 'id_extension'));
}

/**
 * returns authors of an extension
 */
function get_extension_authors($extension_id)
{
  $authors = array();

  $query = '
SELECT idx_user
  FROM '.PEM_EXT_TABLE.'
  WHERE id_extension = '.$extension_id.'
UNION
SELECT idx_user
  FROM '.PEM_AUTHORS_TABLE.'
  WHERE idx_extension = '.$extension_id.'
;';

  return query2array($query, null, 'idx_user');
}

/**
 * returns available languages ids for a set of revisions
 */
function get_language_ids_of_revision($revision_ids)
{
  if (count($revision_ids) == 0)
  {
    return array();
  }
  
  $languages_of = array();

  $query = '
SELECT rv.idx_revision,
       l.id_language
  FROM '.PEM_REV_LANG_TABLE.' AS rv
  INNER JOIN '.PEM_LANG_TABLE.' AS l
    ON rv.idx_language = l.id_language
  WHERE idx_revision IN ('.implode(',', $revision_ids).')
  ORDER BY l.name
;';
  
  $result = query2array($query);

  //Original function in pem uses while
  foreach($result as $item)
  {
    $languages_of[ $item['idx_revision'] ][] = $item['id_language'];
  }

  return $languages_of;
}

/**
 * returns available languages names for a set of revisions
 */
function get_languages_of_revision($revision_ids)
{
  $languages_of = array();
  $languages_ids_of = get_language_ids_of_revision($revision_ids);

  $query = 'SELECT id_language, code, name FROM '.PEM_LANG_TABLE.';';
  $result = query2array($query);

  $languages_data = array();

  //Original function in pem uses while
  foreach($result as $item)
  {
    $languages_data[ $item['id_language'] ] = $item;
  }

  foreach ($revision_ids as $revision_id)
  {
    if (!empty($languages_ids_of[$revision_id]))
    {
      $languages_of[$revision_id] = array();

      foreach ($languages_ids_of[$revision_id] as $language_id)
      {
        array_push(
          $languages_of[$revision_id],
          $languages_data[$language_id]
          );
      }
    }
  }

  return $languages_of;
}

/**
 * returns new languages of each revisions of an extension
 */
function get_diff_languages_of_extension($extension_id)
{  
  $query = '
SELECT id_revision, id_language, code, name
  FROM '.PEM_REV_TABLE.' r
    INNER JOIN '.PEM_REV_LANG_TABLE.' rl ON rl.idx_revision = r.id_revision
    INNER JOIN '.PEM_LANG_TABLE.' l ON l.id_language = rl.idx_language
  WHERE idx_extension = '.$extension_id.'
  ORDER BY r.date ASC
;';
  $result = query2array($query);
  
  $existing_lang = array();
  $languages_of = array();
  
  foreach ($result as $revsion)
  {
    if (!in_array($revsion['id_language'], $existing_lang))
    {
      $existing_lang[] = $revsion['id_language'];
      $languages_of[ $revsion['id_revision'] ][] = $revsion;
    }
  }
  
  return $languages_of;
}

/**
 * find extensions matching the search string
 *
 * search is performed on 
 *   extension name (100 points), 
 *   tags (8 pts),
 *   author name (8pts), 
 *   description (6 pts), 
 *   revision note (4 pts)
 * one point is removed every month of antiquity
 */
function get_extension_ids_for_search($search) {
  global $conf;
  $search_result = array();

  // Split words
  $replace_by = array(
    '-' => ' ', '^' => ' ', '$' => ' ', ';' => ' ', '#' => ' ', '&' => ' ',
    '(' => ' ', ')' => ' ', '<' => ' ', '>' => ' ', '`' => '', '\'' => '',
    '"' => ' ', '|' => ' ', ',' => ' ', '@' => ' ', '_' => '', '?' => ' ',
    '%' => ' ', '~' => ' ', '.' => ' ', '[' => ' ', ']' => ' ', '{' => ' ',
    '}' => ' ', ':' => ' ', '\\' => '', '/' => ' ', '=' => ' ', '\'' => ' ',
    '!' => ' ', '*' => ' ',
    );
  $words = array_unique(
    preg_split(
      '/\s+/',
      str_replace(
        array_keys($replace_by),
        array_values($replace_by),
        $search
        )
      )
    );
  $add_bracked = function (string &$s) {
    $s = "(" . $s . ")";
  };
  
  // search on extension name
  $word_clauses = array();
  foreach ($words as $word) {
    array_push($word_clauses, "e.name LIKE '%".$word."%'");
  }
  array_walk(
    $word_clauses,
    $add_bracked
    );
  $query = '
SELECT
    id_extension
  FROM '.PEM_EXT_TABLE.' AS e
  WHERE '.implode("\n    AND ", $word_clauses).'
;';
  $result = query2array($query, null, 'id_extension');
  foreach ($result as $ext_id) {
    if (!empty($search_result[$ext_id])) {
      $search_result[$ext_id]+= 10;
    } else {
      $search_result[$ext_id] = 10;
    }
  }
  
  // search on tags
  $word_clauses = array();
  foreach ($words as $word) {
    array_push($word_clauses, "LOWER(t.name) LIKE '%".strtolower($word)."%'");
  }
  array_walk(
    $word_clauses,
    $add_bracked
    );
  $query = '
SELECT
    idx_extension
  FROM '.PEM_EXT_TAG_TABLE.' AS et
    LEFT JOIN '.PEM_TAG_TABLE.' AS t
      ON et.idx_tag = t.id_tag
  WHERE '.implode("\n    OR ", $word_clauses).'
;';
  $result = query2array($query, null, 'idx_extension');
  foreach ($result as $ext_id) {
    if (!empty($search_result[$ext_id])) {
      $search_result[$ext_id]+= 8;
    } else {
      $search_result[$ext_id] = 8;
    }
  }
  
  // search on author names
  $word_clauses = array();
  foreach ($words as $word) {
    array_push($word_clauses, "LOWER(u1.".$conf['user_fields']['username'].") LIKE '%".strtolower($word)."%'");
    array_push($word_clauses, "LOWER(u2.".$conf['user_fields']['username'].") LIKE '%".strtolower($word)."%'");
  }
  array_walk(
    $word_clauses,
    $add_bracked
    );
  // query with 2 JOIN to users table to find both extension owner AND authors
  $query = '
SELECT 
    DISTINCT(id_extension)
  FROM '.PEM_EXT_TABLE.' AS e
    LEFT JOIN '.USERS_TABLE.' AS u1
      ON u1.'.$conf['user_fields']['id'].' = e.idx_user
    LEFT JOIN '.PEM_AUTHORS_TABLE.' AS a 
      ON a.idx_extension = e.id_extension 
      LEFT JOIN '.USERS_TABLE.' AS u2 
        ON u2.'.$conf['user_fields']['id'].' = a.idx_user
  WHERE '.implode("\n    OR ", $word_clauses).'
;';
  $result = query2array($query, null, 'id_extension');
  foreach ($result as $ext_id) {
    if (!empty($search_result[$ext_id])) {
      $search_result[$ext_id]+= 8;
    } else {
      $search_result[$ext_id] = 8;
    }
  }
  $id_lang = get_user_lang_id();
  
  // search on extension description
  $word_clauses = array();
  foreach ($words as $word) {
    $field_clauses = array();
    foreach (array('e.description', 'et.description') as $field) {
      array_push($field_clauses, $field." LIKE '%".$word."%'");
    }
    array_push(
      $word_clauses,
      implode("\n          OR ", $field_clauses)
      );
  }
  array_walk(
    $word_clauses,
    $add_bracked
    );
  $query = '
SELECT
    id_extension
  FROM '.PEM_EXT_TABLE.' AS e
    LEFT JOIN '.PEM_EXT_TRANS_TABLE.' AS et
      ON e.id_extension = et.idx_extension
      AND et.idx_language = '.$id_lang.'
  WHERE '.implode("\n    AND ", $word_clauses).'
;';
  $result = query2array($query, null, 'id_extension');
  foreach ($result as $ext_id) {
    if (!empty($search_result[$ext_id])) {
      $search_result[$ext_id]+= 6;
    } else {
      $search_result[$ext_id] = 6;
    }
  }
  
  // search on revision description
  $word_clauses = array();
  foreach ($words as $word) {
    $field_clauses = array();
    foreach (array('r.description', 'rt.description') as $field) {
      array_push($field_clauses, $field." LIKE '%".$word."%'");
    }
    array_push(
      $word_clauses,
      implode("\n          OR ", $field_clauses)
      );
  }
  array_walk(
    $word_clauses,
    $add_bracked
    );
  $query = '
SELECT
    DISTINCT(idx_extension) AS id_extension
  FROM '.PEM_REV_TABLE.' AS r
    LEFT JOIN '.PEM_REV_TRANS_TABLE.' AS rt
      ON r.id_revision = rt.idx_revision
      AND rt.idx_language = '.$id_lang.'
  WHERE '.implode("\n    AND ", $word_clauses).'
;';
  $result = query2array($query, null, 'id_extension');
  foreach ($result as $ext_id) {
    if (!empty($search_result[$ext_id])) {
      $search_result[$ext_id]+= 4;
    } else {
      $search_result[$ext_id] = 4;
    }
  }
  
  // minor rank by the date of last revision (remove 1 point for every month)
  if (count($search_result)) {
    $time = time();
    $query = '
SELECT
    idx_extension,
    MAX(date) AS date
  FROM '.PEM_REV_TABLE.'
  WHERE idx_extension IN ('.implode(',', array_keys($search_result)).')
  GROUP BY idx_extension
;';
    $result = pwg_query($query);
    while ($row = pwg_db_fetch_assoc($result))
    {
      $search_result[ $row['idx_extension'] ]-= ($time - $row['date']) / (60*60*24*7*30);
    }
    
    arsort($search_result);
  }
  
  return array_keys($search_result);
}

/**
 * perform the filter
 */
function get_filtered_extension_ids($filter) {

  $filtered_sets = array();

  if (isset($filter['id_version'])) {
    $filtered_sets['id_version'] = get_extension_ids_for_version($filter['id_version']);
  }

  if (isset($filter['search'])) {
    $filtered_sets['search'] = get_extension_ids_for_search($filter['search']);
  }

  if (isset($filter['category_ids'])) {
    $filtered_sets['category_ids'] = get_extension_ids_for_categories(
      $filter['category_ids'],
      $filter['category_mode']
      );
  }
  
  if (isset($filter['tag_ids'])) {
    $filter['tag_ids'] = explode(',',$filter['tag_ids'][0]);

    $filtered_sets['tag_ids'] = get_extension_ids_for_tags(
      $filter['tag_ids'],
      $filter['tag_mode']
      );
  }

  if (isset($filter['id_user'])) {
    $filtered_user_sets['id_user'] = get_extension_ids_for_user($filter['id_user']);
  }

  if (isset($filter['user_ids'])) {
    foreach($filter['user_ids'] as $user_id)
    {
      $filtered_user_sets['user_ids_'.$user_id] = get_extension_ids_for_user($user_id);
    } 
  }

  $filtered_extension_ids = array_shift($filtered_sets);

  foreach ($filtered_sets as $set) 
  {
    $filtered_extension_ids = array_intersect(
      $filtered_extension_ids,
      $set
    );
  }

  // User filter is seperated to be able to combine the extensions from different users
  // We get extensions from one or the other user, not extensions by both
  if (isset($filtered_user_sets))
  {
    $filtered_user_extension_ids = array();
    foreach ($filtered_user_sets as $set) 
    {
      foreach($set as $id){
        array_push($filtered_user_extension_ids,$id);

      }
    }

    $filtered_extension_ids = array_intersect(
      $filtered_extension_ids,
      $filtered_user_extension_ids
    );
  }

  return array_unique($filtered_extension_ids);
}

/**
 * rate an extension (take care to not accept multiple rates)
 */
function rate_extension($extension_id, $rate)
{
  global $user;

  if ( !isset($rate) or !isset($extension_id) )
  {
    return false;
  }

  // get user infos
  $user_anonymous = empty($user['id']);
  $user_id = $user_anonymous ? 0 : $user['id'];

  $ip_components = explode('.', $_SERVER["REMOTE_ADDR"]);
  if (count($ip_components) > 3)
  {
    array_pop($ip_components);
  }
  $anonymous_id = implode('.', $ip_components);

  if ($user_anonymous)
  {
    $save_anonymous_id = !empty($_SESSION['anonymous_rater']) ? $_SESSION['anonymous_rater'] : $anonymous_id;

    if ($anonymous_id != $save_anonymous_id)
    { // client has changed his IP adress or he's trying to fool us
      $query = '
SELECT idx_extension
  FROM '.PEM_RATE_TABLE.'
  WHERE
    idx_user = '.$user_id.'
    AND anonymous_id = "'.$anonymous_id.'"
;';
      $already_there = query2array($query, null, 'idx_extension');

      if (count($already_there) > 0)
      {
        $query = '
DELETE
  FROM '.PEM_RATE_TABLE.'
  WHERE 
    idx_user = '.$user_id.'
    AND anonymous_id = "'.$save_anonymous_id.'"
    AND idx_extension IN ('.implode(',', $already_there).')
;';
         pwg_query($query);
       }

       $query = '
UPDATE '.PEM_RATE_TABLE.'
  SET anonymous_id = "'.$anonymous_id.'"
  WHERE 
    idx_user = '.$user_id.'
    AND anonymous_id = "'.$save_anonymous_id.'"
;';
       pwg_query($query);
    } // end client changed ip

    $_SESSION['anonymous_rater'] = $anonymous_id;
  } // end anonymous user

  // insert/update rate
  $query = '
DELETE
  FROM '.PEM_RATE_TABLE.'
  WHERE 
    idx_extension = '.$extension_id.'
    AND idx_user = '.$user_id.'
';
  if ($user_anonymous)
  {
    $query.= '
    AND anonymous_id = "'.$anonymous_id.'"';
  }
  $query.= '
;';
  pwg_query($query);
  
  if ($rate != '')
  {
    $query = '
INSERT
  INTO '.PEM_RATE_TABLE.' (
    idx_user,
    idx_extension,
    anonymous_id,
    rate,
    date
  )
  VALUES (
    '.$user_id.',
    '.$extension_id.',
    "'.$anonymous_id.'",
    '.$db->escape($rate).',
    NOW()
  )
;';
    pwg_query($query);
  }
  
  // update extension rating score
  $query = '
SELECT rate
  FROM '.PEM_RATE_TABLE.'
  WHERE idx_extension = '.$extension_id.'
;';
  $rates = query2array($query, null, 'rate');
  
  $query = '
UPDATE '.PEM_EXT_TABLE.'
  SET rating_score = '.(count($rates)>0 ? array_sum($rates)/count($rates) : 'NULL').'
  WHERE id_extension = '.$extension_id.'
;';
  pwg_query($query);
}

/**
 * insert an user review, with anti-spam rules, and mails admins if moderation needed
 */
function insert_user_review(&$comm)
{
  global $conf, $user, $db;
  
  // check required fields
  if ( empty($comm['author']) or empty($comm['email']) or empty($comm['content']) or empty($comm['rate']) )
  {
    $comm['action'] = 'reject';
    $comm['message'] = l10n('One or more required fields are empty');
    return;
  }
  
  // check email validity
  if (filter_var($comm['email'], FILTER_VALIDATE_EMAIL) === false)
  {
    $comm['action'] = 'reject';
    $comm['message'] = l10n('Mail address must be like xxx@yyy.eee (example : jack@altern.org)');
    return;
  }
  
  // check spam with Akismet
  if (!empty($conf['askimet_key']))
  {
    include_once(PEM_PATH . 'include/akismet.class.php');
    $akismet = new Akismet(get_absolute_root_url(), $conf['askimet_key'], $comm);
    
    if ( !$akismet->errorsExist() and $akismet->isSpam() )
    {
      $comm['action'] = 'reject';
      $comm['message'] = l10n('Spammer!');
      return;
    }
  }
  
  // check with bans ip table
  if (!empty($conf['bans_table']))
  {
    $query = 'SELECT ip FROM '. $conf['bans_table'] .' WHERE ip = "'. $db->escape($_SERVER["REMOTE_ADDR"]) .'";';
    $result = pwg_query($query);
    if ($db->num_rows($result))
    {
      $comm['action'] = 'reject';
      $comm['message'] = l10n('Spammer!');
      return;
    }
  }
  
  // remove all html tags
  $comm['content'] = strip_tags($comm['content']);
  
  // anonymous id = ip address
  $user_anonymous = empty($user['id']);
  $user_id = $user_anonymous ? 0 : $user['id'];

  $ip_components = explode('.', $_SERVER["REMOTE_ADDR"]);
  if (count($ip_components) > 3)
  {
    array_pop($ip_components);
  }
  $anonymous_id = implode('.', $ip_components);
  
  // comment validation and anti-spam
  if ( !$conf['comments_validation'] or is_admin(@$user['id']) )
  {
    $comm['action'] = 'validate';
  }
  else if ($conf['anti-flood_time'] > 0)
  {
    $query = '
SELECT COUNT(1) 
  FROM '.PEM_REVIEW_TABLE.'
  WHERE 
    date > SUBDATE(NOW(), INTERVAL '.$conf['anti-flood_time'].' SECOND)
    AND idx_user = '.$user_id;
  if ($user_anonymous)
  {
    $query.= '
    AND anonymous_id = "'.$anonymous_id.'"';
  }
  $query.= '
;';

    list($counter) = pwg_db_fetch_row(pwg_query($query));
    if ($counter > 0)
    {
      $comm['message'] = l10n('Anti-flood system : please wait for a moment before trying to post another review');
      $comm['action'] = 'reject';
      return;
    }
    else
    {
      $comm['action'] = 'moderate';
    }
  }
  else
  {
    $comm['action'] = 'moderate';
  }
  
  // insert comment
  if ($comm['action'] != 'reject')
  {
    if (empty($comm['title']))
    {
      $comm['title'] = substr($comm['content'], 0, 64);
    }
    
    if (empty($comm['idx_language']))
    {
      $comm['idx_language'] = 0;
    }
  
    $query = '
INSERT INTO '.PEM_REVIEW_TABLE.' (
    idx_user,
    idx_extension,
    idx_language,
    date,
    author,
    email,
    title,
    content, 
    rate,
    anonymous_id,
    validated
  )
  VALUES (
    '.$user_id.',
    '.$comm['idx_extension'].',
    "'.$comm['idx_language'].'",
    NOW(),
    "'. $db->escape($comm['author']) .'",
    "'. $comm['email'].'",
    "'. $db->escape($comm['title']) .'",
    "'. $db->escape($comm['content']) .'",
    '. $db->escape($comm['rate']) .',
    "'.$anonymous_id.'",
    "'.($comm['action']=='validate' ? 'true':'false').'"
  )
';

    pwg_query($query);
    $comm['id'] = $db->insert_id();
    rate_extension($comm['idx_extension'], $comm['rate']);
    
    if ( $conf['email_admin_on_comment_validation'] !== false and $comm['action'] == 'moderate' )
    {
      $u_delete = get_absolute_home_url().'admin/reviews.php?delete_review='.$comm['id'];
      $u_validate = get_absolute_home_url().'admin/reviews.php?validate_review='.$comm['id'];
      $extension_infos = get_extension_infos_of($comm['idx_extension']);
      
      $content = '
<i>Extension:</i> '.$extension_infos['name'].'<br>
<i>Author:</i> '.$comm['author'].'<br>
<i>Email:</i> '.$comm['email'].'<br>
<i>IP:</i> '.$_SERVER["REMOTE_ADDR"].'<br>
<i>Date:</i> '.date('r').'<br>
<br>
<b>'.$comm['title'].'</b><br>
'.nl2br($comm['content']).'<br>
<br>
<a href="'.$u_delete.'">Delete</a> | <a href="'.$u_validate.'">Validate<a><br>
';

      send_mail(
        implode(',', get_admin_email()),
        'A new review on "'.$extension_infos['name'].'" by "'.$comm['author'].'"',
        $content,
        array('content_format'=>'text/html')
        );
    }
  }
}

/**
 * delete user review
 */
function delete_user_review($id)
{  
  $query = '
DELETE FROM '.PEM_REVIEW_TABLE.'
  WHERE id_review = '.$id.'
;';
  pwg_query($query);
  
  return $db->affected_rows() > 0;
}

/**
 * validate user review and send a mail to user
 */
function validate_user_review($id)
{
  global $conf;
  
  $query = '
SELECT
    email,
    idx_extension,
    author
  FROM '.PEM_REVIEW_TABLE.'
  WHERE
    id_review = '.$id.'
    AND validated = "false"
';
  $result = pwg_query($query);
  
  if ($db->num_rows($result))
  {
    list($comm['email'], $comm['idx_extension'], $comm['author']) = pwg_db_fetch_row($result);
    
    $query = '
UPDATE '.PEM_REVIEW_TABLE.'
  SET validated = "true"
  WHERE id_review = '.$id.'
;';
    pwg_query($query);
    
    
    $u_extension = get_absolute_home_url().'extension_view.php?eid='.$comm['idx_extension'];
    $extension_infos = get_extension_infos_of($comm['idx_extension']);
    
    $content = '
Hello '.$comm['author'].',<br>
Your review about <a href="'.$u_extension.'">'.$extension_infos['name'].'</a> has been validated by an administrator.<br>
<br>
'.$conf['page_title'].',<br>
please do not answer to this automated email.
';

    send_mail(
      $comm['email'],
      'Review validated on '.$conf['page_title'],
      $content,
      array('content_format'=>'text/html')
      );
      
    return true;
  }
  else
  {
    return false;
  }
}

/**
 * returns the name of a tag
 */
function get_tag_name_from_id($id)
{  
  $query = '
SELECT
    name
  FROM '.PEM_TAG_TABLE.'
  WHERE id_tag = '.$id.'
;';
  $result = pwg_query($query);
  
  list($name) = pwg_db_fetch_row($result);
  return $name;
}

/**
 * returns count of extensions
 * can filter by count of extension by category
 */
function pem_extensions_get_count($category_id = null)
{
  if (isset($category_id))
  {
    $category_ids[$category_id] = $category_id;
    $filtered_extension_ids =  get_extension_ids_for_categories($category_ids);
    $filtered_extension_ids_string = implode(
      ',',
      $filtered_extension_ids
    );
  }

  $query = '
  SELECT
      idx_extension
    FROM '.PEM_REV_TABLE.' ';
  if (isset($filtered_extension_ids)) {
    if (count($filtered_extension_ids) > 0) {
      $query.= '
    WHERE idx_extension IN ('.$filtered_extension_ids_string.')';
    }
  }
  $query.= '
    GROUP BY idx_extension
  ;';

  $extension_ids = query2array($query);
  $count_of_extensions = count($extension_ids);
  
  return $count_of_extensions;
}

?>