<div class="modal fade" id="loginform">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h3 class="modal-title">Login</h3>
      </div>
      <div class="modal-body">
        <div class="row">
          <div class="login_wrap">
            <div class="col-md-12">
              <form method="post">
                <?php echo csrf_field(); ?>
                <div class="form-group">
                  <label class="sr-only" for="login-email">Email address</label>
                  <input type="email" class="form-control" id="login-email" name="email" placeholder="Email address*" autocomplete="email" required>
                </div>
                <div class="form-group">
                  <label class="sr-only" for="login-password">Password</label>
                  <input type="password" class="form-control" id="login-password" name="password" placeholder="Password*" autocomplete="current-password" required>
                </div>
                <div class="form-group">
                  <input type="submit" name="login" value="Login" class="btn btn-block">
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer text-center">
        <p>Don't have an account? <a href="#signupform" data-toggle="modal" data-dismiss="modal">Sign up here</a></p>
        <p><a href="#forgotpassword" data-toggle="modal" data-dismiss="modal">Forgot password?</a></p>
      </div>
    </div>
  </div>
</div>
