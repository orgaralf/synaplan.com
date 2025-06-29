#!/bin/bash
for i in {1..2}
do
    cd /wwwroot/synaplan.com/web/
    php gmailrefresh.php
    sleep 27
done
