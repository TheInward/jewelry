# SiteGround migration

Target temporary domain: `https://mrnobodys1.sg-host.com`.

SSH host: `ssh.mrnobodys1.sg-host.com`, port `18765`. The site-specific SSH key stays outside the repository. The target directory is `/home/customer/www/mrnobodys1.sg-host.com/public_html`.

The existing Alibaba site stays available during migration. Full WordPress files and the database are copied, rather than reconstructing only products from the seed JSON. Do not run the Alibaba provisioning scripts on SiteGround.

## Deployment status

- Temporary-domain site created in Singapore and WordPress published at the HTTPS root.
- Dedicated SSH public key imported with the user's confirmation.
- Site-specific database and dedicated database user created in Site Tools with user confirmation; the user is assigned only to this database.
- Full source database imported and serialized URL values changed from the Alibaba `/jewelry` URL to the target HTTPS root.
- 175 products and the existing admin account preserved. Disposable registration test account removed after validation.
- Source archives stay outside public web roots and outside Git. The Alibaba `wp-config.php` is excluded; the new host gets independent database credentials and WordPress salts.

## Migration steps after database access is ready

1. Check both archive SHA-256 hashes against the source copies before extracting.
2. Extract the application into `/home/customer/jewelry-migration/site`, outside the public directory.
3. Run `siteground-write-config.php` over SSH. Supply database name, user and password as a JSON object on stdin, never in command-line arguments. The script checks that the new database is empty before writing its config.
4. Import the SQL backup with WP-CLI; replace the old Alibaba `/jewelry` URL with the target HTTPS root URL using serialization-aware `wp search-replace`, skipping GUIDs.
5. Preserve the blank site's default placeholder outside the public directory, then copy staged files into the existing `public_html` directory and install `siteground.htaccess`. Preserve the hosting provider's existing document-root directory rather than replacing it.
6. Confirm site/checkout/account URLs, rewrite rules, cache behavior, guest cart, login and public HTTPS access. Keep backups and credentials outside the web root.

This migration does not complete the outstanding design, product-variation, translation, social-login or payment work documented in the comparison report.

## Verification

- Official WordPress core checksums passed before publication; both archive hashes matched the source.
- Safari displayed the published catalog over HTTPS with no preview password.
- Public catalog, product, cart persistence, checkout with an item, and login page returned successfully.
- Registration, logout, incorrect-password rejection, email/password login, orders, addresses and account details passed with a disposable test customer.
- Account and checkout responses use private/no-cache headers. Empty-cart checkout redirects to the cart as expected.
- Direct access to `wp-config.php` returns HTTP 403. Development noindex is retained.
- Plain HTTP redirects to HTTPS with HTTP 301. The live theme's 12 tracked files match the Git source hashes.
- Alibaba and the existing port-80 Clipflow site remain available.
- Reset-password email delivery and real payments are not verified or connected by this migration.
