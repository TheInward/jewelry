# Homepage repair and current limits — 2026-11-02

Deployed to https://mrnobodys1.sg-host.com/.

- Added a dedicated WordPress front-page template, replacing the necklace catalog previously shown on the homepage.
- Added responsive reference campaign images. The image contains its campaign title and Shop Now artwork; the entire banner links to the bestseller collection.
- Added the top-left Sign Up Now trigger and accessible native dialog. Footer email signup opens the same form.
- Signup generates a 10% coupon, restricted to the submitted email, one redemption, 30-day expiry, and no combination with other coupons. Repeated signup retrieves the same code. Code is shown on screen; mail submission is attempted but delivery is not verified.
- Fixed the homepage bracelet link to use bracelet-anklet, and corrected signup's locale object.

Verification: deployed PHP lint, JS syntax, live homepage markup and responsive image resources, invalid-email and CSRF rejection. Catalog membership checked for necklaces, bracelet-anklet, rings, earrings and bestseller. Commerce regression passed for USD/HKD/EUR/JPY/GBP, price tampering rejection, cart quantity/persistence, calculated checkout totals, USD54 free shipping, guest isolation, and English/French/Japanese catalog/account language persistence. No payment or order submitted.

Remaining limits:
- Live payment gateways, merchant credentials, refunds and wallet payments need end-to-end verification before launch.
- Email transport/delivery and marketing platform synchronization are not verified/configured.
- New signup copy currently falls back to English when translations are absent. Text embedded in campaign images cannot change with the language selector.
- This is a homepage repair, not a certification of visual parity across all reference pages. Visual browser checks at desktop/mobile widths remain outstanding.
- Reference media must be replaced before commercial launch. Preview remains noindex.
