#!/bin/bash

cat <<EOT
Test "remove file distribution db bridge for iserv-exam"
  "[[ ! \$(dpkg-query --showformat='\${Status}\n' --show 'stsbl-iserv-file-distribution-bridge-exam' 2> /dev/null | grep 'install ok installed') ]]" 
  "/usr/lib/iserv/aptitude_auto remove stsbl-iserv-file-distribution-bridge-exam"
EOT

cat <<EOT
Test "remove file distribution db bridge for iserv-lock"
  "[[ ! \$(dpkg-query --showformat='\${Status}\n' --show 'stsbl-iserv-file-distribution-bridge-lock' 2> /dev/null | grep 'install ok installed') ]]"
  "/usr/lib/iserv/aptitude_auto remove stsbl-iserv-file-distribution-bridge-lock"
EOT

