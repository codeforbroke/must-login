# WordPress.org Plugin Assets

This directory contains assets for the WordPress.org plugin repository. These files are **NOT** included in the plugin ZIP file - they are only for display on WordPress.org.

## Required Files

### Screenshots
Add screenshots referenced in `readme.txt`:
- `screenshot-1.png` - Admin bar toggle with status indicator
- `screenshot-2.png` - Simple settings page with REST API protection option
- `screenshot-3.png` - Cache notice when caching plugins are detected

**Recommended size:** 1280x720px or higher (16:9 ratio)

### Optional Assets

#### Plugin Icon
- `icon-128x128.png` - Small icon (128x128px)
- `icon-256x256.png` - Large icon (256x256px)
- `icon.svg` - Vector icon (optional, preferred)

#### Plugin Banner
- `banner-772x250.png` - Low resolution banner
- `banner-1544x500.png` - High resolution banner (retina)

## Image Guidelines

- Use PNG format for all images (except SVG icon)
- Screenshots should show actual plugin functionality
- Banners should match your brand colors
- Keep file sizes optimized for web

## Uploading to WordPress.org

These assets are pushed to the WordPress.org SVN repository separately from the plugin code:

```bash
# Add assets to the /assets directory in SVN
svn checkout https://plugins.svn.wordpress.org/your-plugin/assets
cp .wordpress-org/* assets/
svn add assets/*
svn commit -m "Add plugin assets"
```

For more information, see: https://developer.wordpress.org/plugins/wordpress-org/plugin-assets/
