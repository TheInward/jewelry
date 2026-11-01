# SiteGround migration

Target temporary domain: `https://mrnobodys1.sg-host.com`.

SSH host: `ssh.mrnobodys1.sg-host.com`, port `18765`. The site-specific SSH key stays outside the repository. The target directory is `/home/customer/www/mrnobodys1.sg-host.com/public_html`.

The existing Alibaba site stays available during migration. Full WordPress files and the database are copied, rather than reconstructing only products from the seed JSON. Do not run the Alibaba provisioning scripts on SiteGround.

## Prepared state

- Blank temporary-domain site created in Singapore, with working HTTPS.
- Dedicated SSH public key imported with the user's confirmation.
- Site-specific empty database created in Site Tools.
- Database user creation and assignment need confirmation in Site Tools before completing migration.
- Source archives stay outside public web roots and outside Git. The Alibaba `wp-config.php` is excluded; the new host gets independent database credentials and WordPress salts.

## Migration steps after database access is ready

1. Check both archive SHA-256 hashes against the source copies before extracting.
2. Extract the application into `/home/customer/jewelry-migration/site`, outside the public directory.
3. Run `siteground-write-config.php` over SSH. Supply database name, user and password as a JSON object on stdin, never in command-line arguments. The script checks that the new database is empty before writing its config.
4. Import the SQL backup with WP-CLI; replace the old Alibaba `/jewelry` URL with the target HTTPS root URL using serialization-aware `wp search-replace`, skipping GUIDs.
5. Preserve the blank site's default placeholder outside the public directory, then publish the staged files and `siteground.htaccess` at the target root.
6. Confirm site/checkout/account URLs, rewrite rules, cache behavior, guest cart, login and public HTTPS access. Keep backups and credentials outside the web root.

This migration does not complete the outstanding design, product-variation, translation, social-login or payment work documented in the comparison report.
