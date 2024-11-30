 <?php

// +-----------------------------------------------------------------------+
// |                           Initialization                              |
// +-----------------------------------------------------------------------+

global $template, $user;

// +-----------------------------------------------------------------------+
// |                           Form submission                             |
// +-----------------------------------------------------------------------+
if (isset($_POST['pem_action']) and isset($_POST['submit']) and "edit_authors" == $_POST['pem_action'])
{
  if (is_a_guest()) return; 

  //Get list of extension authors
  $authors = get_extension_authors($current_extension_page_id);
  
  if (isset($user['id']) and (is_Admin() or in_array($user['id'], $authors)))
  {

    // $authors = implode(',',$_POST['authors']);
    $query = '
SELECT '.$conf['user_fields']['id'].' AS id
  FROM '.USERS_TABLE.'
  WHERE '.$conf['user_fields']['id'].' = '. pwg_db_real_escape_string($_POST['author']) .'
;';

    list($author_id) = pwg_db_fetch_array(pwg_query($query));

    if (empty($author_id))
    {
      $page['errors'][] = l10n('This user does not exist in database.');
    }
    else
    {
      $authors = get_extension_authors($_GET['eid']);

      if (!in_array($author_id, $authors))
      {
        $query = '
INSERT INTO '.PEM_AUTHORS_TABLE.' (idx_extension, idx_user)
  VALUES ('.$_GET['eid'].', '.$author_id.')
;';
        pwg_query($query);
      }

      // $country_code = geoip_country_code_by_name($_SERVER['REMOTE_ADDR']);
      // $country_name = geoip_country_name_by_name($_SERVER['REMOTE_ADDR']);

      $country_code = 'unkown';
      $country_name = 'unkown';
      
      notify_mattermost('[pem] user #'.$user['id'].' ('.$user['username'].') updated authors for extension #'.$params['extension_id'].' , IP='.$_SERVER['REMOTE_ADDR'].' country='.$country_code.'/'.$country_name);


      $template->assign(
        array(
          'MESSAGE' => 'Extension authors succefully updated.',
          'MESSAGE_TYPE' => 'success'
        )
      );
    }
  }
  else
  {
    return;
  }
}

?>