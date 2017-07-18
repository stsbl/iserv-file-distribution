#!/bin/bash

# exclude iserv.stsbl.test from bridges, because we do not have package management here
if [ -f "/var/lib/dpkg/info/iserv-exam.list" ] && [ ! "$(hostname)" = "iserv.stsbl.test" ]
then
  cat <<EOT
Test "install file distribution db bridge for iserv-exam"
  "[[ \$(dpkg-query --showformat='\${Status}\n' --show 'stsbl-iserv-file-distribution-bridge-exam' 2> /dev/null | grep 'install ok installed') ]]"
  "/usr/lib/iserv/aptitude_auto install stsbl-iserv-file-distribution-bridge-exam"
EOT
else
  cat <<EOT
Test "remove file distribution db bridge for iserv-exam"
  "[[ ! \$(dpkg-query --showformat='\${Status}\n' --show 'stsbl-iserv-file-distribution-bridge-exam' 2> /dev/null | grep 'install ok installed') ]]" 
  "/usr/lib/iserv/aptitude_auto remove stsbl-iserv-file-distribution-bridge-exam"
EOT
fi

cat <<EOT
Test "remove file distribution db bridge for iserv-lock"
  "[[ ! \$(dpkg-query --showformat='\${Status}\n' --show 'stsbl-iserv-file-distribution-bridge-lock' 2> /dev/null | grep 'install ok installed') ]]"
  "/usr/lib/iserv/aptitude_auto remove stsbl-iserv-file-distribution-bridge-lock"
EOT

