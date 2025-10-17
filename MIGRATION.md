# Introduction

AWS discontinued Elastic Transcoder on November 13 2025. This plugin previously used Elastic Transcoder, but has since been updated to use MediaConvert (the successor to Elastic Transcoder).

This guide shows you how to migrate your existing site using Smartmedia to the new codebase.

## Good to know
Please note some features have not been updated or migrated, and are broken for the time being, including:

1. Rekonition (label, face, etc.. detection)

Also check https://github.com/catalyst/moodle-local_smartmedia/issues for known issues

## Steps

1. Gracefully stop conversions (can be skipped if > Nov 13 2025, as no conversions will be run after this time):
    1. Put site into maintenance mode. This stops users uploading any new files.
    2. Run `php admin/cli/scheduled_task.php --execute="\local_smartmedia\task\process_conversions"` until it displays that there are no more, confirm in your Elastic Transcoder queue that no more conversions are in progress
2. Run site/plugin upgrade `php admin/cli/upgrade.php`
3. Provision new resources, using the updated [Provision script](README.md#aws-stack-setup)
4. Enter the new details into the `local_smartmedia` plugin settings
5. No further action is necessary, and all previous conversions will continue to function.


