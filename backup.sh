#!/bin/bash

mysqldump -uroot generaldrugcentre  > generaldrugcentre.sql

zip -r "ps-general-drugs-center-archive-$(date +"%Y-%m-%d").zip" generaldrugcentre.sql

gdrive files upload "ps-general-drugs-center-archive-$(date +"%Y-%m-%d").zip"

rm "ps-general-drugs-center-archive-$(date +"%Y-%m-%d").zip"

rm generaldrugcentre.sql