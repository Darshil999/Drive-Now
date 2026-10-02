<script>
function checkAvailability() {
  var email = $("#emailid").val();
  if (!email) { return; }
  $.post("check_availability.php", { emailid: email, csrf_token: "<?php echo e(csrf_token()); ?>" }, function (data) {
    $("#user-availability-status").text(data.message).css("color", data.available ? "green" : "red");
    $("#submit").prop("disabled", !data.available);
  }, "json");
}
</script>

<div class="modal fade" id="signupform">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h3 class="modal-title">Sign Up</h3>
      </div>
      <div class="modal-body">
        <div class="row">
          <div class="signup_wrap">
            <div class="col-md-12">
              <form method="post" name="signup">
                <?php echo csrf_field(); ?>
                <div class="form-group">
                  <input type="text" class="form-control" name="fullname" placeholder="Full Name*" maxlength="120" autocomplete="name" required>
                </div>
                <div class="form-group">
                  <input type="tel" class="form-control" name="mobileno" placeholder="Mobile Number (10 digits)*" pattern="[0-9]{10}" maxlength="10" title="Enter a 10 digit mobile number" autocomplete="tel" required>
                </div>
                <div class="form-group">
                  <input type="email" class="form-control" name="emailid" id="emailid" onblur="checkAvailability()" placeholder="Email Address*" autocomplete="email" required>
                  <span id="user-availability-status" style="font-size:12px;"></span>
                </div>
                <div class="form-group">
                  <input type="password" class="form-control" name="password" placeholder="Password*" minlength="<?php echo MIN_PASSWORD_LENGTH; ?>" pattern="(?=.*[A-Za-z])(?=.*\d).{<?php echo MIN_PASSWORD_LENGTH; ?>,}" title="At least <?php echo MIN_PASSWORD_LENGTH; ?> characters, including a letter and a number" autocomplete="new-password" required>
                </div>
                <div class="form-group">
                  <input type="password" class="form-control" name="confirmpassword" placeholder="Confirm Password*" autocomplete="new-password" required>
                </div>
                <div class="form-group checkbox">
                  <input type="checkbox" id="terms_agree" required>
                  <label for="terms_agree">I agree with the <a href="page.php?type=terms" target="_blank">Terms and Conditions</a></label>
                </div>
                <div class="form-group">
                  <input type="submit" value="Sign Up" name="signup" id="submit" class="btn btn-block">
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer text-center">
        <p>Already have an account? <a href="#loginform" data-toggle="modal" data-dismiss="modal">Login here</a></p>
      </div>
    </div>
  </div>
</div>
