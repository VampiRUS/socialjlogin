#!/bin/bash

zip -rq packages/com_socialjlogin.zip com_socialjlogin
cd modules
for i in $(ls);do
	zip -rq ../packages/$i.zip $i
done
cd ../plugins/
for i in $(ls);do
	cd $i
	PREFIX=`grep "<extension" "$i.xml"|sed 's/.*group="\(.*\)" .*/\1/'`
	cd ..
	zip -rq "../packages/plg_${PREFIX}_${i}.zip" $i
done
cd ..
VERSION=`grep "<version" pkg_socialjlogin.xml|sed 's/<version>\(.*\)<\/version>/\1/'`

zip -rq "pkg_socialjlogin_${VERSION}.zip" packages install.php pkg_socialjlogin.xml
