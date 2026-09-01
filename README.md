# EGFGames

Course tool for Chamilo **2.0.x** (tested on 2.0.3) that imports and plays [EGF](https://www.egf-format.org) games (1.0 and 1.1).

## Overview

EGFGames adds an **EGFGames** icon on the course homepage.

- Teachers import and delete `.egf` packages in that course only
- Learners open a game and play it in the embedded EGF reader
- Games are not shared between courses

The player is the Embedded EGF 1.1 Reader. Activities are not edited inside Chamilo: you prepare the `.egf` file elsewhere, then import it.

## Requirements

- Chamilo **2.0.x** (tested on 2.0.3)
- Platform administrator account
- PHP extensions already used by Chamilo, including **zip** (`ZipArchive`)
- Web server write access to `public/plugin/EGFGames/storage/`
- For large `.egf` files, check PHP `upload_max_filesize` and `post_max_size`

## Installation

1. Copy the `EGFGames` folder into the Chamilo root so you get:

   `{CHAMILO}/public/plugin/EGFGames/plugin.php`

   `{CHAMILO}` is the directory that contains `public/` and `bin/`.

2. Allow the web server user (`www-data`, `nginx`, etc.) to write in `storage/`:

   ```bash
   chown -R www-data:www-data public/plugin/EGFGames/storage
   chmod 775 public/plugin/EGFGames/storage
   ```

3. From the Chamilo root, clear the cache:

   ```bash
   php bin/console cache:clear
   ```

   If `bin/console` is not available:

   ```bash
   rm -rf var/cache/*
   ```

4. As platform admin, open:

   `https://YOUR-PORTAL/main/admin/settings.php?category=Plugins&show_all_plugins=1`

   Chamilo 2.0.x hides third-party plugins on the default Plugins page.  
   **`show_all_plugins=1` is required.**

5. Find **EGFGames** → **Install** → **Enable**.

6. Open a course as a teacher. If the **EGFGames** icon is missing, switch the course homepage to edit mode and make the tool visible.

### Apache

The file `storage/.htaccess` already blocks HTTP access to imported games.  
No extra Apache config is needed if `.htaccess` files are allowed (`AllowOverride` not set to `None`).

### Nginx

`.htaccess` is ignored on Nginx. Add:

```nginx
location ^~ /plugin/EGFGames/storage/ {
    deny all;
}
```

Then reload Nginx.

### Update

Replace the plugin files but **keep** `storage/`. Deleting that folder removes imported games.

## Configuration

No API key or extra service is required.

After install, **Administration → Plugins → EGFGames → Configure** (use the same `show_all_plugins=1` URL if the plugin is hidden).  
The only setting is the default visibility of the tool on new course homepages (`Visible` / `Hidden`). Teachers can still show or hide it per course.

## Usage

Once the tool is visible on the course homepage:

- Teacher: open **EGFGames**, choose a `.egf` file, import it. Use **Play** to preview or **Delete** to remove it from the course.
- Learner: open **EGFGames**, then **Play**.

Games stay in that course. They are not added to learning paths and do not appear in the “new items since your last visit” list.

## Uninstall

1. Open  
   `https://YOUR-PORTAL/main/admin/settings.php?category=Plugins&show_all_plugins=1`
2. **EGFGames** → **Disable**, then **Uninstall**
3. Optionally delete `public/plugin/EGFGames/`
4. Files in `storage/` remain until you remove that folder

## License

Copyright (c) 2026 Hervé Yvis

EGFGames is licensed under the MIT License.  
The embedded reader in `resources/reader/` is also MIT.

## Source

The original source code of this program is available at:

https://directory.yvisherve.net/EGFGames.zip
