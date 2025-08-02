# changelog

## Changes made for V 0.2.2

### Added
- changed version-tag in index.php
- Added new sorting functionality to the JSON reader (assets/js/jsonreader.js | index.php):
  + Click on the column headers "Date", "Action", or "Jail" to sort the data.
- uncommented the "reset & reload" button to have a easy posibillity to reset set filters or sorting
- Added date-filter and reset button in Blocklist Overlay (assets/js/blocklist-overlay.js | index.php | assets/css/style.css)
- Added max_display_days to set the number of days displayed in the "Select Date" list on the main site. (fail2ban-report.config | includes/list-files.php)
- 

### Files changed in V 0.2.2 (for manual patching)

The following files were modified or added in this patch:

- `index.php`
- `assets/js/jsonreader.js`
- `assets/js/blocklist-overlay.js`
- `assets/css/style.css`
- `includes/list-files.php`
- `fail2ban-report.config` (/opt/Fail2Ban-Report/)

To benefit from all new features, please ensure these files are updated.
You do **not** need to update shell scripts or other backend logic for this patch.
