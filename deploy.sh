j=jquery-3.3.1.min.js
f=public/$j
if [ ! -f $f ] ; then
    curl "https://code.jquery.com/$j" > $f
fi