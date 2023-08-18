<section id="userInfo-popin">
  <div>
    <div class="modal fade" id="userInfoModal" tabindex="-1" aria-labelledby="userInfoModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="userInfoModalLabel"><i class="icon-user"></i>My profil</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <form method="post" action="{$f_action}" enctype="multipart/form-data">

            <div class="modal-body">

              {* Extension name *}
              <div class="mb-3 form-group">
                <label for="extension_name" class="form-label w-100 ">Name</label>
                <input type="text" name="extension_name" size="35" maxlength="255" value="{$USER.username}" class="w-100"/>
              </div>

            </div>

            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
              <input type="submit" class="btn btn-primary" value="{'Save Changes'|@translate}" name="submit" />
            </div>

          </form>
        </div>
      </div>
    </div>
  </div>
</section>