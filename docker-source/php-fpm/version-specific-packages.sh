#!/bin/bash

VERSION=$1
read -a MODULES <<<${2}

REPO_MODULES=("bcmath" "bz2" "cgi" "cli" "common" "curl" "dba" "dev" "enchant" "fpm" "gd" "gmp" "imap" "interbase" "intl" "json" "ldap" "mbstring" "mcrypt" "mysql" "odbc" "opcache" "pgsql" "phpdbg" "pspell" "readline" "recode" "snmp" "soap" "sqlite3" "sybase" "tidy" "xml" "xmlrpc" "xsl" "zip")

MODIFIED=''

for MODULE in "${MODULES[@]}"; do
    ADDED=0
    for REPO_MODULE in "${REPO_MODULES[@]}"; do
        if [[ "$MODULE" == "$REPO_MODULE" ]]; then
          MODIFIED="${MODIFIED} php${VERSION}-${MODULE}"
          ADDED=1

          continue
        fi
    done

    if [[ ${ADDED} -eq 0 ]]; then
        MODIFIED="${MODIFIED} php-${MODULE}"
    fi
done

echo "${MODIFIED}"
