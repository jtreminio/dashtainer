#!/usr/bin/env python

# apachelint - simple tool to cleanup apache conf files
# USAGE: apachelint [conffile]
# -*-Python-*-

import sys
import re

filename = sys.argv[1]
indentlevel = 0
indentstep = 4
prevlineblank = False

with open(filename) as f:
    for line in f:
        # strip leading & trailing space / line ending
        line = re.sub('\s+$', '', line)
        line = re.sub('^\s+', '', line)
        # compress blank lines
        if line == "":
            if prevlineblank:
                next
            else:
                prevlineblank = True
        else:
            prevlineblank = False

        if re.search('</', line):
            indentlevel -= 1

        indent = ' ' * indentlevel * indentstep
        print indent + line

        if re.search('<(?!/)', line):
            indentlevel += 1


