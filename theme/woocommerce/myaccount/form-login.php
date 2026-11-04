<?php
defined('ABSPATH')||exit;
do_action('woocommerce_before_customer_login_form');
$register=isset($_GET['create_account'])&&get_option('woocommerce_enable_myaccount_registration')==='yes';
?>
<div class="account-auth" id="customer_login">
 <section data-account-panel="login" <?php if($register)echo 'hidden'; ?>>
  <h1><?php echo esc_html(study_t("Log in")); ?></h1>
  <form class="woocommerce-form woocommerce-form-login login" method="post" action="<?php echo esc_url(wc_get_page_permalink('myaccount')); ?>">
   <?php do_action('woocommerce_login_form_start'); ?>
   <label for="username" class="screen-reader-text"><?php echo esc_html(study_t("Email")); ?></label><input class="input-text" type="text" name="username" id="username" autocomplete="username" placeholder="<?php echo esc_attr(study_t("Email")); ?>" required value="<?php echo isset($_POST['username'])?esc_attr(wp_unslash($_POST['username'])):''; ?>">
   <div class="password-field"><label for="password" class="screen-reader-text"><?php echo esc_html(study_t("Password")); ?></label><input class="input-text" type="password" name="password" id="password" autocomplete="current-password" placeholder="<?php echo esc_attr(study_t("Password")); ?>" required><button type="button" class="password-toggle" data-password-target="password" aria-label="Show password"><?php echo esc_html(study_t("Show")); ?></button></div>
   <?php do_action('woocommerce_login_form'); ?>
   <p class="account-forgot"><a href="<?php echo esc_url(wp_lostpassword_url()); ?>"><?php echo esc_html(study_t("Forgot password?")); ?></a></p>
   <label class="remember"><input name="rememberme" type="checkbox" value="forever"> <?php echo esc_html(study_t("Remember me")); ?></label>
   <?php wp_nonce_field('woocommerce-login','woocommerce-login-nonce'); ?>
   <button type="submit" class="primary" name="login" value="Log in"><?php echo esc_html(study_t("Log in")); ?></button>
   <?php do_action('woocommerce_login_form_end'); ?>
  </form>
  <?php if(get_option('woocommerce_enable_myaccount_registration')==='yes'): ?><p class="account-switch"><a href="<?php echo esc_url(add_query_arg('create_account','1',wc_get_page_permalink('myaccount'))); ?>" data-account-switch="register"><?php echo esc_html(study_t("Create account")); ?></a></p><?php endif; ?>
 </section>
 <?php if(get_option('woocommerce_enable_myaccount_registration')==='yes'): ?>
 <section data-account-panel="register" <?php if(!$register)echo 'hidden'; ?>>
  <h1><?php echo esc_html(study_t("Create account")); ?></h1>
  <form method="post" class="woocommerce-form woocommerce-form-register register" action="<?php echo esc_url(add_query_arg('create_account','1',wc_get_page_permalink('myaccount'))); ?>">
   <?php do_action('woocommerce_register_form_start'); ?>
   <div class="name-fields"><label><span class="screen-reader-text"><?php echo esc_html(study_t("First Name")); ?></span><input class="input-text" type="text" name="first_name" placeholder="<?php echo esc_attr(study_t("First Name")); ?>" autocomplete="given-name" required></label><label><span class="screen-reader-text"><?php echo esc_html(study_t("Last Name")); ?></span><input class="input-text" type="text" name="last_name" placeholder="<?php echo esc_attr(study_t("Last Name")); ?>" autocomplete="family-name" required></label></div>
   <label for="reg_email" class="screen-reader-text"><?php echo esc_html(study_t("Email")); ?></label><input class="input-text" type="email" name="email" id="reg_email" placeholder="<?php echo esc_attr(study_t("Email")); ?>" autocomplete="email" required>
   <div class="password-field"><label for="reg_password" class="screen-reader-text"><?php echo esc_html(study_t("Password")); ?></label><input class="input-text" type="password" name="password" id="reg_password" placeholder="<?php echo esc_attr(study_t("Password")); ?>" autocomplete="new-password" minlength="8" required><button type="button" class="password-toggle" data-password-target="reg_password" aria-label="Show password"><?php echo esc_html(study_t("Show")); ?></button></div>
   <div class="password-field"><label for="reg_password_confirm" class="screen-reader-text"><?php echo esc_html(study_t("Confirm password")); ?></label><input class="input-text" type="password" name="password_confirmation" id="reg_password_confirm" placeholder="<?php echo esc_attr(study_t("Confirm password")); ?>" autocomplete="new-password" minlength="8" required><button type="button" class="password-toggle" data-password-target="reg_password_confirm" aria-label="Show password"><?php echo esc_html(study_t("Show")); ?></button></div>
   <?php if(get_privacy_policy_url()): ?><label class="remember"><input name="agree_privacy" type="checkbox" value="1" required> <span><?php echo esc_html(study_t("I agree to the")); ?> <a href="<?php echo esc_url(get_privacy_policy_url()); ?>" target="_blank" rel="noopener"><?php echo esc_html(study_t("Privacy Policy")); ?></a>.</span></label><?php endif; ?>
   <?php do_action('woocommerce_register_form'); wp_nonce_field('woocommerce-register','woocommerce-register-nonce'); ?>
   <button type="submit" class="primary" name="register" value="Create account"><?php echo esc_html(study_t("Create account")); ?></button>
   <?php do_action('woocommerce_register_form_end'); ?>
  </form>
  <p class="account-switch"><a href="<?php echo esc_url(wc_get_page_permalink('myaccount')); ?>" data-account-switch="login"><?php echo esc_html(study_t("Log in")); ?></a></p>
 </section>
 <?php endif; ?>
</div>
<?php do_action('woocommerce_after_customer_login_form'); ?>
