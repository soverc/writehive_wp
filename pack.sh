PACKNAME="writehive"
SRCNAME="src"
VERSION="1.0.4"
ln -s $SRCNAME $PACKNAME
zip  -r writehive_$VERSION.zip $PACKNAME
rm $PACKNAME
