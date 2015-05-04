#!/bin/bash
#set -ex
BASE_PATH=$(pwd)
E_UNREACHABLE=86

if [ "$FOURSTORE" != "" ] || [ "$VIRTUOSO" != "" ] || [ "$SESAME" != "" ]
then
	sudo apt-get update -qq
fi

# Version 1.1.0 is available and testable on Travis/SMW
if [ "$FUSEKI" != "" ]
then
	# Archive
	# http://archive.apache.org/dist/jena/binaries/jena-fuseki-$FUSEKI-distribution.tar.gz
	# http://www.eu.apache.org/dist/jena/binaries/jena-fuseki-$FUSEKI-distribution.tar.gz

	wget http://archive.apache.org/dist/jena/binaries/jena-fuseki-$FUSEKI-distribution.tar.gz
	tar -zxf jena-fuseki-$FUSEKI-distribution.tar.gz
	mv jena-fuseki-$FUSEKI fuseki

	cd fuseki

	## Start fuseki in-memory as background
	bash fuseki-server --update --mem /db &>/dev/null &
fi

if [ "$SESAME" != "" ]
then
	sudo apt-get install tomcat6

	sudo chown $USER -R /var/lib/tomcat6/
	sudo chmod g+rw -R /var/lib/tomcat6/

	sudo mkdir -p /usr/share/tomcat6/.aduna
	sudo chown -R tomcat6:tomcat6 /usr/share/tomcat6

	# One method to get the war files
	# wget http://search.maven.org/remotecontent?filepath=org/openrdf/sesame/sesame-http-server/$SESAME/sesame-http-server-$SESAME.war -O openrdf-sesame.war
	# wget http://search.maven.org/remotecontent?filepath=org/openrdf/sesame/sesame-http-workbench/$SESAME/sesame-http-workbench-$SESAME.war -O openrdf-workbench.war
	# cp *.war /var/lib/tomcat6/webapps/

	# http://sourceforge.net/projects/sesame/
	wget http://downloads.sourceforge.net/project/sesame/Sesame%202/$SESAME/openrdf-sesame-$SESAME-sdk.zip

	# tar caused a lone zero block, using zip instead
	unzip -q openrdf-sesame-$SESAME-sdk.zip
	cp openrdf-sesame-$SESAME/war/*.war /var/lib/tomcat6/webapps/

	sudo service tomcat6 restart
	sleep 3

	if curl --output /dev/null --silent --head --fail "http://localhost:8080/openrdf-sesame"
	then
		echo "openrdf-sesame service url is reachable"
	else
		echo "openrdf-sesame service url is not reachable"
		exit $E_UNREACHABLE
	fi

	./openrdf-sesame-$SESAME/bin/console.sh < $BASE_PATH/build/travis/openrdf-sesame-memory-repository.txt
fi

# Version 1.1.4-1 is available but has a problem
# https://github.com/garlik/4store/issues/110
# 4STORE can not be used as variable name therefore FOURSTORE
if [ "$FOURSTORE" != "" ]
then

	sudo mkdir -p /var/lib/4store
	sudo mkdir -p /var/log/4store

	sudo chown $USER:$USER /var/lib/4store/
	sudo chown $USER:$USER /var/log/4store/

	sudo touch /etc/4store.conf
	sudo chown $USER:$USER /etc/4store.conf

	sudo apt-get install -y software-properties-common
	sudo add-apt-repository ppa:yves-raimond/ppa -y
	sudo apt-get update -q
	sudo apt-get install -y --force-yes 4store

echo '[4s-boss]
discovery = sole
nodes = 127.0.0.1

[smwrepo]
port = 8088' > /etc/4store.conf

	4s-boss
	ps auxw | grep 4s-bos[s]

	4s-admin create-store smwrepo
	sleep 5

	4s-admin start-stores smwrepo
	sleep 5

	4s-admin list-nodes
	4s-admin list-stores
	sleep 5

	4s-admin list-nodes
	4s-admin list-stores

	## Output the current process table
	##ps auwwx | grep 4s-

	## -D only used to check the status of the 4store instance
	4s-backend-setup smwrepo
	sleep 5
	4s-backend smwrepo
	sleep 5
	4s-httpd -p 8088 smwrepo

	##4s-httpd -p 8088 db
fi

# Version 6.1 is available
if [ "$VIRTUOSO" != "" ]
then
	sudo apt-get install -qq virtuoso-opensource
	echo "RUN=yes" | sudo tee -a /etc/default/virtuoso-opensource-$VIRTUOSO
	sudo service virtuoso-opensource-$VIRTUOSO start

	isql-vt 1111 dba dba $BASE_PATH/build/travis/virtuoso-sparql-permission.sql
fi
