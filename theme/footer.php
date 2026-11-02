<footer class="footer footer-full">
<div class="footer-newsletter"><h3><?php echo esc_html(study_t('Stay in the Loop')); ?></h3><p><?php echo esc_html(study_t('Join the list for special discounts and new releases.')); ?></p><form id="newsletter"><input type="email" placeholder="<?php echo esc_attr(study_t('Email')); ?>" aria-label="<?php echo esc_attr(study_t('Email')); ?>" required><button type="submit" aria-label="<?php echo esc_attr(study_t('Sign Up Now')); ?>"><span class="newsletter-arrow">→</span><span class="newsletter-submit">SUBMIT</span></button><label class="newsletter-consent"><input type="checkbox" required><span><?php echo esc_html(study_t('By subscribing, I agree with the')); ?> <a href="<?php echo esc_url(get_privacy_policy_url()); ?>"><?php echo esc_html(study_t('Privacy Policy')); ?></a>.</span></label></form><p id="newsletter-status" aria-live="polite"></p></div>
<div class="mobile-footer-brand"><img src="https://cdn.shopify.com/s/files/1/1782/4093/t/650/assets/footer_logo.png?v=12095384966375010103" alt="en route jewelry" width="125" height="125" loading="lazy"></div>
<?php foreach (['About en route'=>['about-us'=>'The Brand','a-letter-from-us'=>'A Letter From Us'],'Info'=>['terms-and-conditions'=>'Terms and Conditions','privacy-policy'=>'Privacy Policy','press'=>'Press','rewards'=>'Membership & Rewards','blogs'=>'Blogs'],'Help'=>['track-my-order'=>'Track My Order','faq'=>'FAQ','shipping-return'=>'Shipping & Return','jewelry-care'=>'Jewelry Care','start-a-return'=>'Start a Return','contact'=>'Contact Us']] as $group=>$links): ?><div><h3><?php echo esc_html(study_t($group)); ?></h3><?php foreach($links as $slug=>$label): ?><a href="<?php echo esc_url(home_url('/pages/'.$slug.'/')); ?>"><?php echo esc_html(study_t($label)); ?></a><?php endforeach; ?></div><?php endforeach; ?>
<div class="footer-legal">© <?php echo esc_html(wp_date('Y')); ?> en route jewelry · <?php echo esc_html(study_t('Development Preview')); ?> · <a href="https://www.exchangerate-api.com" target="_blank" rel="noopener">Rates By Exchange Rate API</a></div>
</footer>
<dialog class="signup-dialog" id="signup-dialog" aria-labelledby="signup-title">
  <div class="signup-modal">
    <img class="signup-photo" src="https://cdn.shopify.com/s/files/1/1782/4093/files/img_v3_0216v_18134370-6dc3-4191-99de-e26f6bd4ddeg.jpg?v=1793359637&amp;width=720" alt="" loading="lazy">
    <div class="signup-content">
      <button class="signup-close" type="button" id="signup-close" aria-label="<?php echo esc_attr(study_t('Close')); ?>">×</button>
      <div class="signup-form-panel" id="signup-form-panel">
        <h2 id="signup-title"><?php echo esc_html(study_t('GET')); ?> <em>10% OFF</em><br><?php echo esc_html(study_t('FOR YOUR NEXT ORDER!')); ?></h2>
        <p><?php echo esc_html(study_t('Yes, even on sale items.')); ?></p>
        <form id="signup-form"><input name="email" type="email" autocomplete="email" placeholder="<?php echo esc_attr(study_t('Enter your email')); ?>" aria-label="<?php echo esc_attr(study_t('Enter your email')); ?>" required><button type="submit"><?php echo esc_html(study_t('EMAIL ME THE CODE')); ?></button></form>
        <div class="signup-status" id="signup-status" role="status" aria-live="polite"></div>
      </div>
      <div class="signup-result" id="signup-result" hidden>
        <h2><?php echo esc_html(study_t('YOUR 10% OFF CODE IS READY!')); ?></h2>
        <p><?php echo esc_html(study_t('Copy the code below and apply it at checkout.')); ?></p>
        <div class="signup-code"><span id="signup-code"></span><button type="button" id="signup-copy"><?php echo esc_html(study_t('COPY')); ?></button></div>
        <a class="signup-shop" href="<?php echo esc_url(study_collection_url('bestseller')); ?>"><?php echo esc_html(study_t('Shop Now')); ?></a>
      </div>
      <p class="signup-privacy"><?php echo esc_html(study_t('By submitting, you agree to receive marketing emails.')); ?> <a href="<?php echo esc_url(get_privacy_policy_url()); ?>" target="_blank" rel="noopener"><?php echo esc_html(study_t('Privacy Policy')); ?></a></p>
    </div>
  </div>
</dialog>
<div class="overlay" id="overlay"><section class="drawer" role="dialog" aria-modal="true" aria-labelledby="drawer-title"><div class="drawer-head"><h2 id="drawer-title"></h2><button id="close-drawer" aria-label="Close">✕</button></div><div id="drawer-body"></div></section></div><div class="toast" role="status"></div><?php wp_footer(); ?></body></html>
