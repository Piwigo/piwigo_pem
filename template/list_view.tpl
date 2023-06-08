<div id="list_view" class="container">

{if $SPOTLIGHTED}
  <div class="col-12 py-4 spotlighted">
    <h2>Spotlighted</h2>
    <div class="col-12 p-3 purple-gradient">
      <div class="row">
  {if $CATEGORY.spotlight_extension.screenshot}
        <img class="col-md-2" src="{$CATEGORY.spotlight_extension.screenshot}">
        <div class="col-md-8">
  {else}
        <div class="col-md-10">
  {/if}
          <h3>{$CATEGORY.spotlight_extension.name}</h3>
          <p class="description">{$CATEGORY.spotlight_extension.description}</p>
        </div>
        <div class="col-md-2 col-md-2 d-flex justify-content-end align-items-end">
          <a href="{$PEM_ROOT_URL}index.php?eid={$CATEGORY.spotlight_extension.eid}">Voir <i class="icon-chevron-right"></i></a>
        </div>
      </div>
    </div>
  </div>
{/if}


  <div class="d-flex content_header">
    <h4>{$CATEGORY.name_plural_EN}
      <span class='badge blue-badge'>{$CATEGORY.extension_count}</span>
    </h4>
    <div class="d-flex filter_section">
      <div class="filter_tab mx-2" onclick="toggleFilter()"><h5 >Filter</h5></div>
      <label for="sort_order">Sort order</label>
      <select name="sort_order" id="sort_order" class="form-control">
          <option value="date_asc">Newest to oldest</option>
          <option value="date_desc">Oldest to Newest</option>
          <option value="a_Z">A to Z</option>
          <option value="z_a">Z to A</option>
      </select>
      <form class="form-inline  cid-search-form">
        <i class="icon-magnifying-glass"></i>
        <input id="cid-search" class="form-control mr-sm-2" type="search" placeholder="Search" aria-label="Search">
      </form>
    </div>
  </div>

  <div class="col-12 extension_filters">
    <div class="row">

      <div class="col-md-4 form-group">
        <label for="version_filter" class="col-12">Version</label>
        <select class="form-select" >
          <option selected disabled>Select a version</option>
{foreach from=$VERSIONS item=version}
          <option value="{$version.id_version}">{$version.version}</option>
{/foreach}
        </select>
      </div>

      <div class="col-md-8">
        <div class="form-group">

          <label for="autor_filter">Author</label>
          <select class="form-select" >
            <option selected disabled>Select an author</option>
{foreach from=$AUTHORS item=author}
            <option value="{$author.uId}">{$author.username}</option>
{/foreach}
          </select>
          {* <input type="text" class="form-control w-100" id="autor_filter" placeholder="Select authors"> *}   

        </div>  
      </div>
    </div>

    <div class="row mt-4">

      <div class="col-md-4">

        <div data-slider="last_update" class="slider_blocks">
              <div class="dimensionSlidersTitleButtons">
                <div>
                  Date <span class="slider-info">between 2003 and 2023</span>
                </div>
                <a class="slider-choice dimension-cancel" data-min="112" data-max="8256">Annuler</a>
              </div>
              <div class="slider-slider ui-slider ui-slider-horizontal ui-widget ui-widget-content ui-corner-all" aria-disabled="false">
                <div class="ui-slider-range ui-widget-header ui-corner-all" style="left: 0%; width: 100%;"></div>
                <a class="ui-slider-handle ui-state-default ui-corner-all" href="#" style="left: 0%;"></a>
              </div>

              <input type="hidden" data-input="min" name="filter_dimension_min_width" value="112">
              <input type="hidden" data-input="max" name="filter_dimension_max_width" value="8256">
            </div>

      </div>

    </div>
    {* Tags filter *}
    <div class="row mt-4" id="tag_select">
      <div class="col-12">
{foreach from=$TAGS item=tag}
    <label class="badge rounded-pill" for="flexCheckDefault">
      <input type="hidden" name="alarm" value="False" />
      <input class="form-check-input checkbox_hidden" type="checkbox" value="true" id="{$tag.tId}_{$tag.name}">
      {$tag.name}
    </label>
  
{/foreach}
      </div>
    </div>

  </div>

  <div class="extensions_container">

    <div class="extension_info card" id="jango_fett">
      <div class="row card-body">
        <div class="col col-3">
          <h5 class="card-title extension_name"></h5>
          <div class="card-text extension_authors"></div>
          <div class="extension_score"></div>
          <div class="d-flex"><i class="icon-download"></i><p class="card-text extension_number_downloads"></p></div>
        </div>
        <div class="col col-9 extension_description_container">
          <p class="card-text extension_description"></p>
          <a class="more_info_link" href="{$PEM_ROOT_URL}index.php?eid=" >Discover this {$CATEGORY.name}</a>
        </div>
      </div>
    </div>

  </div>

  <div class="pagination text-center justify-content-center">
    <a class="align-middle" id="previous_page" href="{$PEM_ROOT_URL}index.php?" ><i class="icon-chevron-left"></i></a>
    <div class="page_buttons align-middle">
    </div>
    <a class="align-middle" id="next_page" href="{$PEM_ROOT_URL}index.php?"><i class="icon-chevron-right"></i></a>
  </div>

</div>

<script src="{$PEM_ROOT_URL_PLUGINS}template/js/list_view.js" require="jquery"></script>