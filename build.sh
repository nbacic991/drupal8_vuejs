#!/usr/bin/env bash
# MCI-Drupal build.sh v1.1 see details in docs/install-drupal.md
# 2017 (C) @MacMladen

drush make build.make.yml webroot
cd webroot
drush si -y --db-url=mysql://drupal:drupal@mariadb/drupal \
  --account-name=admin \
  --account-pass=admin \
  --account-mail=admin@example.com \
  --site-mail=admin@example.com \
  --site-name=Drupal \
  standard
