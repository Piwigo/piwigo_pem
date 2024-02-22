function display_changelog(revision_id)
{
  var element = document.getElementById( 'changelog_' + revision_id );
  
  if( element.style.display == 'none' )
  {
    element.style.display = 'block';
  }
  else
  {
    element.style.display = 'none';
  }
}

// Collapse and expand the selected revision
function revToggleDisplay(headerId, contentId)
{
  var revHeader = document.getElementById(headerId);
  var revContent = document.getElementById(contentId);

  if (revContent.style.display == 'none')
  {
    revContent.style.display = 'block';
    revHeader.className = 'changelogRevisionHeaderExpanded pb-3';

    var revArrow = jQuery('#'+headerId+' i.icon-chevron-right');
    revArrow.removeClass('icon-chevron-right')
    revArrow.addClass('icon-chevron-down')
  }
  else
  {
    revContent.style.display = 'none';
    revHeader.className = 'changelogRevisionHeaderCollapsed pb-0';

    var revArrow = jQuery('#'+headerId+' i.icon-chevron-down');
    revArrow.addClass('icon-chevron-right')
    revArrow.removeClass('icon-chevron-down')
  }
}

jQuery("#edit_mode").change(function() {
  jQuery('.edit_mode').toggle();
  jQuery('.related_links').toggle();
});

jQuery(document).ready(function () {
  // Selectize modal inputs
  jQuery('.extension_author_select').selectize()

  jQuery('.extension_tag_select').selectize({
    plugins: ["remove_button"],
  })
  
  jQuery('.extension_lang_desc_select').selectize({
    plugins: ["remove_button"],
  })

  jQuery('#addRevisionModal .revison_languages').selectize({
    plugins: ["remove_button"],
    items : extensions_languages_ids,
    valueField: 'id_language',
    labelField: 'name',
    searchField: 'name',
    maxItems: null,
    options:ALL_LANGUAGES,
  })

  jQuery('#addRevisionModal .revision_compatible_versions').selectize({
    plugins: ["remove_button"],
  })

  showHideDetectLang();

  // Depending on file type hide detectLang option
  jQuery("input[type=radio][name=file_type]").change(function()
  {
    showOnlyThisChild('upload_types', this.value+'_type');
    showHideDetectLang();
  });

  // Used to display different textArea for revision description
  $('input[name="default_description"]').click(function () {
    set_default_description(this.value);
  });

  // Hide all description blocks, display the one linked to the selected language
  // For add revision modal
  $("#addRevisionModal .desc").hide();
  var selected_desc_lang = jQuery('#addRevisionModal #lang_desc_select').val();
  jQuery('#addRevisionModal #desc_block_'+selected_desc_lang).show()

  jQuery('#addRevisionModal #lang_desc_select').on('change', function() {
    $("#addRevisionModal .desc").hide();
    var selected_desc_lang = jQuery('#addRevisionModal #lang_desc_select').val();
    jQuery('#addRevisionModal #desc_block_'+selected_desc_lang).show()
  });

  // Hide all description blocks, display the one linked to the selected language
  // For Edit revision modal
  $("#revisionInfoModal .desc").hide();
  var selected_desc_lang = jQuery('#revisionInfoModal #lang_desc_select').val();
  jQuery('#revisionInfoModal #desc_block_'+selected_desc_lang).show()

  jQuery('#revisionInfoModal #lang_desc_select').on('change', function() {
    $("#revisionInfoModal .desc").hide();
    var selected_desc_lang = jQuery('#revisionInfoModal #lang_desc_select').val();
    jQuery('#revisionInfoModal #desc_block_'+selected_desc_lang).show()
  });

  // Hide all description blocks, display the one linked to the selected language
  // For general edit info revision modal
  $("#generalInfoForm .desc").hide();
  var selected_desc_lang = jQuery('#generalInfoForm #lang_desc_select').val();
  jQuery('#generalInfoForm #desc_block_'+selected_desc_lang).show()

  jQuery('#generalInfoForm #lang_desc_select').on('change', function() {
    $("#generalInfoForm .desc").hide();
    var selected_desc_lang = jQuery('#generalInfoForm #lang_desc_select').val();
    jQuery('#generalInfoForm #desc_block_'+selected_desc_lang).show()
  });

});

// Ajax request to delete an author, found in the edit_authors_form.tpl modal 
function deleteAuthor(userId, extensionId)
{
  jQuery.ajax({
    type: 'GET',
    dataType: 'json',
    async: false,
    url: 'ws.php?format=json&method=pem.extensions.deleteAuthor&extension_id=' + extensionId + '&user_id=' + userId + '&pwg_token=' + pwg_token,
    data: { ajaxload: 'true' },
    success: function (data) {
      window.location.reload(); 
    }
  });
}

// Ajax request to set the owner of an extension, found in the edit_authors.tpl modal
function setOwner(userId, extensionId)
{
  jQuery.ajax({
    type: 'GET',
    dataType: 'json',
    async: false,
    url: 'ws.php?format=php&method=pem.extensions.setOwner&extension_id=' + extensionId + '&user_id=' + userId + '&pwg_token=' + pwg_token ,
    data: { ajaxload: 'true' },
    success: function (data) {
      window.location.reload(); 
    }
  });
}

// Ajax request to delete an link associated to an extension, found in single_view.tpl
function deleteLink(linkId, extensionId)
{
  jQuery.ajax({
    type: 'GET',
    dataType: 'json',
    async: false,
    url: 'ws.php?format=json&method=pem.extensions.deleteLink&extension_id=' + extensionId + '&link_id=' + linkId + '&pwg_token=' + pwg_token,
    data: { ajaxload: 'true' },
    success: function (data) {
      window.location.reload(); 
    }
  });
}

//Ajax requet to delete SVN/Git config
function deleteSVNGitConfig(extensionId){
  jQuery.ajax({
    type: 'GET',
    dataType: 'json',
    async: false,
    url: 'ws.php?format=json&method=pem.extensions.deleteSvnGitConfig&extension_id=' + extensionId + '&pwg_token=' + pwg_token,
    data: { ajaxload: 'true' },
    success: function (data) {
      window.location.reload(); 
    }
  });
}

// Ajax request to delete an extension
function deleteExtension(extensionId, link)
{
  jQuery.ajax({
    type: 'GET',
    dataType: 'json',
    async: false,
    url: 'ws.php?format=json&method=pem.extensions.deleteExtension&extension_id=' + extensionId + '&pwg_token=' + pwg_token,
    data: { ajaxload: 'true' },
    success: function (data) {
      if (data.stat == 'ok') {
        window.location.replace(link)
      }
    }
  });
}

// Ajax request to delete a revision from an extension
function deleteRevision(revisionId,extensionId )
{
  jQuery.ajax({
    type: 'GET',
    dataType: 'json',
    async: false,
    url: 'ws.php?format=json&method=pem.revisions.deleteRevision&extension_id=' + extensionId + '&revision_id=' + revisionId+ '&pwg_token=' + pwg_token,
    data: { ajaxload: 'true' },
    success: function (data) {
      window.location.reload(); 
    }
  });
}

// Depending on selected file type display according inputs
function showOnlyThisChild(parentId, childIdtoShow)
{
  var parent = jQuery('#'+parentId);
  var divsToChange = jQuery(parent[0]).children();

  jQuery(divsToChange).each(function(i, child){
    if (child.id == childIdtoShow)
    {
      jQuery('.modal #'+child.id).removeClass('d-none');
      jQuery('.modal #'+child.id).addClass('d-block');
    }
    else
    {
      jQuery('.modal #'+child.id).removeClass('d-block');
      jQuery('.modal #'+child.id).addClass('d-none');
    }
  })

  return false;
}

// Toggle detectLang link
function showHideDetectLang() {
  if (jQuery("input[name=file_type]:checked").val() == "svn" || jQuery("input[name=file_type]:checked").val() == "git")
  {
    jQuery(".modal .detectLang").show();
  }
  else 
  {
    jQuery(".modal .detectLang").hide();
  }
}

function detectLang()
{
  var file_type = jQuery("input[name=file_type]:checked").val();
  var url = "'ws.php?format=json&method=";

  url+= "eid={$extension_id}&svn=";

  var file_type = jQuery("input[name=file_type]:checked").val();

  if (file_type == "svn") {
    url+= jQuery("input[name=svn_revision]").val();
  }
  else {
    url+= 'HEAD';
  }

  jQuery.ajax({
    url: url,
    type:"GET",
    beforeSend: function() {
      jQuery("#detectLangLoad").show();
    },
    success:function(data) {
      jQuery("#detectLangLoad").hide();

      var data = jQuery.parseJSON(data);
      if (data.stat == 'ok') {
        var new_desc = jQuery(".desc_en_UK").val();
        if (new_desc != "") {
          new_desc+= "\n\n";
        }
        new_desc+= data.desc_extra;

        jQuery(".desc_en_UK").val(new_desc);

        /* reset the list of checked languages */
        jQuery('#extensions_languages option').removeAttr('selected').trigger("list:updated");

        jQuery.each(data.language_ids, function(i, language_id) {
          jQuery('#extensions_languages option[value="'+language_id+'"]')
            .attr('selected', 'selected')
            .trigger("list:updated")
          ;
        });
      }
      else {
        var error_message = "error#1, a problem has occured";
        if (typeof data.error_message != "undefined") {
          error_message = data.error_message;
        }
        alert(error_message);
      }
    },
    error:function(XMLHttpRequest, textStatus, errorThrows) {
      jQuery("#detectLangLoad").hide();
      alert("error#2, a problem has occured");
    }
  });
}

  // Script used for editing link modal
  // The link data is saved in the data attributes of the edit button, 
  // This data is added to the modal on the modal show when thue button is clicked
  const editLinkModal = document.getElementById('editLinkModal');

  editLinkModal.addEventListener('show.bs.modal', event => {
    const buttonEditLink = event.relatedTarget
    // Extract info from data-bs-* attributes
    const linkId = buttonEditLink.getAttribute('data-bs-link-id')
    const linkName = buttonEditLink.getAttribute('data-bs-link-name')
    const linkURL = buttonEditLink.getAttribute('data-bs-link-url')
    const linkLang = buttonEditLink.getAttribute('data-bs-link-lang')

    // Get the modal's input
    const modalLinkID= editLinkModal.querySelector('#link_id')
    const modalLinkName = editLinkModal.querySelector('#link_name')
    const modalLinkUrl= editLinkModal.querySelector('#link_url')
    const modalLinkLang= editLinkModal.querySelector('#link_language')

    // Update the modal's content.
    modalLinkID.value = linkId
    modalLinkName.value = linkName
    modalLinkUrl.value = linkURL
    modalLinkLang.value = linkLang
  });

  // Script used for editing revision modal
  // The link data is saved in the data attributes of the edit button, 
  // This data is added to the modal on the modal show when thue button is clicked
  // const editRevisionModal = document.getElementById('revisionInfoModal');
  const editRevisionModal = document.getElementById('revisionInfoModal');
  editRevisionModal.addEventListener('show.bs.modal', event => {

    const buttonEditRev = event.relatedTarget

    // Extract info from data-bs-* attributes
    const revId = buttonEditRev.getAttribute('data-bs-rev_id')
    const revVersionName = buttonEditRev.getAttribute('data-bs-rev_version_name')
    const revDescription = buttonEditRev.getAttribute('data-bs-rev_description')
    const revDescriptionLang = buttonEditRev.getAttribute('data-bs-rev_description_lang')
    const revDefaultDescription = buttonEditRev.getAttribute('data-bs-rev_default_description')
    const revDefaultDescriptionLang = buttonEditRev.getAttribute('data-bs-rev_default_description_lang')
    const revVersionsCompatible = buttonEditRev.getAttribute('data-bs-rev_versions_compatible')
    const revAuthor = buttonEditRev.getAttribute('data-bs-rev_author')
    const arrayRevVersionsCompatible = revVersionsCompatible.split(',')
    jQuery(arrayRevVersionsCompatible).each(function(i) {
      arrayRevVersionsCompatible[i] = parseInt(arrayRevVersionsCompatible[i])
    })
    const current_rev_edit = buttonEditRev.getAttribute('data-bs-rev_id')


    // Get the modal's input
    const modalRevId= editRevisionModal.querySelector('#rid')
    const modalRevVersion= editRevisionModal.querySelector('#revision_version')
    const modalRevDescriptionLang= editRevisionModal.querySelector('#revision_lang_desc_select')

    // Fills inputs 
    modalRevId.value = revId
    modalRevVersion.value = revVersionName
    jQuery('#author_'+revAuthor).prop('checked', true);


    if(revDescription != revDefaultDescriptionLang)
    {
      const modalRevDefaultDescription= editRevisionModal.querySelector('#desc_'+revDefaultDescriptionLang)
      jQuery(modalRevDefaultDescription).val(revDefaultDescription).change()
      jQuery(modalRevDescriptionLang).val(revDefaultDescriptionLang).change()

      const modalRevDescription= editRevisionModal.querySelector('#desc_'+revDescriptionLang)
      jQuery(modalRevDescription).val(revDescription).change()
      jQuery(modalRevDescriptionLang).val(revDescriptionLang).change()
    }
    else
    {
      const modalRevDescription= editRevisionModal.querySelector('#desc_'+revDescriptionLang)
      jQuery(modalRevDescription).val(revDescription).change()
      jQuery(modalRevDescriptionLang).val(revDescriptionLang).change()
    }

    jQuery('#revisionInfoModal .revison_languages').selectize({
      plugins: ["remove_button"],
      items : all_revision_languages[current_rev_edit],
      valueField: 'id_language',
      labelField: 'name',
      searchField: 'name',
      maxItems: null,
      options:ALL_LANGUAGES,
    })

    jQuery('#revisionInfoModal .revision_compatible_versions').selectize({
      plugins: ["remove_button"],
      items:arrayRevVersionsCompatible,
      valueField: 'id_version',
      labelField: 'version',
      searchField: 'version',
      maxItems: null,
      options:VERSIONS_PWG,
    })

  });
