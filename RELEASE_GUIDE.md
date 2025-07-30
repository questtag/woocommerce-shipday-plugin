# Shipday WooCommerce Plugin Release Guide

## Overview

This guide covers the complete process for releasing updates to the Shipday WooCommerce plugin on WordPress.org. Our development workflow uses **Git for development** and **SVN for WordPress.org releases**.

## Repository Information

- **WordPress.org SVN Repository:** `https://plugins.svn.wordpress.org/shipday-for-woocommerce/`
- **Plugin Name:** Shipday Integration for WordPress (WooCommerce)
- **Main File:** `shipday-integration-for-wooCommerce.php`

## WordPress Plugin SVN Architecture

### Directory Structure
```
shipday-for-woocommerce/
├── trunk/          # Latest development code (working directory)
├── tags/           # Released versions (what users download)
│   ├── 1.8.0/
│   ├── 1.8.6/
│   ├── 1.8.7/
│   └── 1.8.8/      # Latest release
└── assets/         # Screenshots, banners, icons
```

### How WordPress.org Plugin Updates Work

1. **Users get updates from:** The version specified in `trunk/readme.txt` "Stable tag" field
2. **That stable tag points to:** A specific folder in `/tags/`
3. **Trunk is for:** Development and preparing the next release
4. **Tags are for:** Immutable release snapshots (never modified after creation)

### Key Concepts

- **Trunk = Development workspace** (always contains your next version)
- **Tags = Release snapshots** (immutable, what users download)
- **readme.txt stable tag** = Points WordPress.org to which tag users should download
- **Version consistency** = Plugin file version, readme stable tag, and tag folder name must all match

## Development Workflow

### Our Setup
- **Development Repository:** Git-based directory with latest code
- **Release Repository:** SVN checkout of WordPress.org repository
- **Process:** Develop in Git → Copy to SVN → Release via SVN

### Version Management
- Plugin versions follow semantic versioning (e.g., 1.8.7, 1.8.8, 1.9.0)
- Each release gets its own tag in SVN
- Trunk always contains the next version in development

## Prerequisites

### Install SVN (macOS)
```bash
# Using Homebrew (recommended)
brew install svn

# Or using Xcode Command Line Tools
xcode-select --install
```

### Get SVN Repository Locally
```bash
# Initial checkout (first time only)
svn checkout https://plugins.svn.wordpress.org/shipday-for-woocommerce/ shipday-svn

# Navigate to the directory
cd shipday-svn
```

### WordPress.org Credentials
- **Username:** Your WordPress.org username (case-sensitive!)
- **Password:** Your SVN password (set in WordPress.org profile settings)

## Step-by-Step Release Process

### Step 1: Prepare Your Development Code

Ensure your Git development directory has:
- All new features tested and working
- Updated version numbers (prepare for next step)
- Clean, documented code ready for release

### Step 2: Determine Next Version Number

Follow semantic versioning:
- **Major version** (2.0.0): Breaking changes
- **Minor version** (1.9.0): New features, backward compatible
- **Patch version** (1.8.8): Bug fixes, backward compatible

### Step 3: Update SVN Working Directory

```bash
cd /path/to/your/svn/directory
svn update
```

### Step 4: Copy Development Code to SVN Trunk

```bash
cd trunk/

# Remove old files (keeping .svn directories)
find . -name "*.php" -not -path "./.svn/*" -delete
find . -name "*.txt" -not -path "./.svn/*" -delete
find . -name "*.js" -not -path "./.svn/*" -delete
find . -name "*.css" -not -path "./.svn/*" -delete

# Copy new code from your Git development directory
cp -r /path/to/your/git-development/trunk/* ./
```

### Step 5: Update Version Information

#### In `shipday-integration-for-wooCommerce.php`:
```php
/*
Plugin Name: Shipday Integration for Wordpress (WooCommerce)
Version: 1.8.8  // Update this
*/

global $shipday_plugin_version;
$shipday_plugin_version = '1.8.8';  // Update this
```

#### In `readme.txt`:
```
Stable tag: 1.8.8  // Update this

== Changelog ==

= 1.8.8 =
* Add your new features here
* List bug fixes
* Note any compatibility updates
```

### Step 6: Stage Changes in SVN

```bash
# Add any new files
svn add . --force

# Remove any deleted files
svn status | grep '^!' | awk '{print $2}' | xargs svn remove 2>/dev/null || true

# Check what will be committed
svn status

# Review differences
svn diff
```

### Step 7: Commit to Trunk

```bash
svn commit -m "Update to version 1.8.8 - [brief description of changes]"
```

### Step 8: Create Release Tag

```bash
svn copy https://plugins.svn.wordpress.org/shipday-for-woocommerce/trunk \
         https://plugins.svn.wordpress.org/shipday-for-woocommerce/tags/1.8.8 \
         -m "Tagging version 1.8.8"
```

### Step 9: Update Local Copy

```bash
svn update
```

### Step 10: Verify Release

```bash
# Check that tag was created
svn list tags/

# Verify readme.txt stable tag
cat trunk/readme.txt | grep "Stable tag"
```

## Post-Release Process

### Timeline
- **Immediate:** Changes are committed to SVN
- **15 minutes - 6 hours:** WordPress.org processes updates
- **After processing:** Users see update notifications

### Verification
1. **Check plugin page:** Visit [WordPress.org plugin page](https://wordpress.org/plugins/shipday-for-woocommerce/)
2. **Test update:** Install previous version on test site and verify update works
3. **Monitor:** Check for user reports or issues

### Update Git Repository
After successful release, update your Git repository:
```bash
cd /path/to/your/git-repository
# Commit and tag the release in Git for consistency
git tag v1.8.8
git push origin v1.8.8
```

## Best Practices

### Version Control
- **SVN is for releases only** - don't commit every small change
- **Keep trunk updated** with your latest stable development code
- **Always create tags** for releases - never use trunk as stable
- **Test thoroughly** before releasing

### Release Notes
- **Be descriptive** in commit messages
- **Document breaking changes** clearly
- **List new features** and bug fixes
- **Update compatibility** information

### Security
- **Never commit sensitive data** (API keys, passwords)
- **Review changes** before committing
- **Test on staging** environment first

## Common Issues and Troubleshooting

### Issue: "Path not found" Error
**Problem:** Using wrong repository name in SVN commands
**Solution:** Ensure you're using `shipday-for-woocommerce` not `shipday-integration-for-woocommerce`

### Issue: Plugin Update Not Showing
**Problem:** Version mismatch or caching
**Solutions:**
- Verify `readme.txt` stable tag matches plugin version and SVN tag
- Wait up to 6 hours for WordPress.org processing
- Check that tag was created successfully

### Issue: SVN Authentication Failed
**Problem:** Wrong credentials or case-sensitive username
**Solutions:**
- Verify username matches WordPress.org profile exactly
- Set/reset SVN password in WordPress.org profile
- Use: `svn --username your-username commit`

### Issue: Trunk Out of Sync
**Problem:** SVN trunk doesn't match latest development
**Solution:**
```bash
# Switch trunk to latest tag first
svn switch https://plugins.svn.wordpress.org/shipday-for-woocommerce/tags/1.8.7 trunk/
# Then switch back to trunk (now it has latest released content)
svn switch https://plugins.svn.wordpress.org/shipday-for-woocommerce/trunk trunk/
```

## Useful SVN Commands

```bash
# Check repository status
svn status

# See differences
svn diff

# Update from repository
svn update

# View commit history
svn log

# List tags
svn list tags/

# View repository information
svn info

# Revert changes to a file
svn revert filename.php
```

## Emergency Procedures

### Rolling Back a Release
If a critical issue is found after release:

1. **Quick fix approach:**
   ```bash
   # Fix the issue in trunk
   # Commit fix
   svn commit -m "Critical fix for version 1.8.8"
   
   # Create patch release
   svn copy trunk tags/1.8.9 -m "Tagging version 1.8.9 - Critical fix"
   ```

2. **Rollback approach:**
   ```bash
   # Update readme.txt to point to previous stable version
   # Change "Stable tag: 1.8.7" in trunk/readme.txt
   svn commit trunk/readme.txt -m "Rollback to version 1.8.7"
   ```

### Contact Information
For WordPress.org plugin repository issues:
- **Plugin Review Team:** [Email support](mailto:plugins@wordpress.org)
- **Documentation:** [Plugin Handbook](https://developer.wordpress.org/plugins/)

---

## Summary Checklist

- [ ] Development code is tested and ready
- [ ] Version number decided (semantic versioning)
- [ ] SVN working directory updated
- [ ] Development code copied to SVN trunk
- [ ] Version numbers updated in all files
- [ ] Changes staged and reviewed in SVN
- [ ] Committed to trunk with descriptive message
- [ ] Tag created for release
- [ ] Local SVN copy updated
- [ ] Release verified on WordPress.org
- [ ] Git repository tagged for consistency

**Remember:** WordPress plugin releases are public and permanent. Always test thoroughly before releasing!