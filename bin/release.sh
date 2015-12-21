# release.sh
#
# Takes a tag to release, and syncs it to WordPress.org

TAG=$1

PLUGIN="gp-additional-links"
TMPDIR=/tmp/release-svn
PLUGINDIR="$PWD"
PLUGINSVN="https://plugins.svn.wordpress.org/$PLUGIN"
TODAY=`date +'%B %d, %Y'`

if [ "$2" !=  "" ]; then
	SVN_OPTIONS=" --username $2"
fi

# Fail on any error
set -e

# Is the tag valid?
if [ -z "$TAG" ] || ! git rev-parse "$TAG" > /dev/null; then
	echo "Invalid tag. Make sure you tag before trying to release."
	exit 1
fi

if [[ $VERSION == "v*" ]]; then
	# Starts with an extra "v", strip for the version
	VERSION=${TAG:1}
else
	VERSION="$TAG"
fi

if [ -d "$TMPDIR" ]; then
	# Wipe it clean
	rm -r "$TMPDIR"
fi

# Ensure the directory exists first
mkdir "$TMPDIR"

# Grab an unadulterated copy of SVN
svn co "$PLUGINSVN/trunk" "$TMPDIR" > /dev/null

# Extract files from the Git tag to there
git archive --format="tar" "$TAG" | tar -C "$TMPDIR" -xf -

# Switch to build dir
cd "$TMPDIR"

# Run build tasks
sed -e "s/{{TAG}}/$VERSION/g" < "$PLUGINDIR/bin/readme.template" > readme.temp
sed -e "s/##\(.*\)/==\1 ==/g" < "$PLUGINDIR/CHANGES.md" > changelog.temp
cat readme.temp changelog.temp > readme.txt
rm readme.temp
rm changelog.txt

# Remove special files
rm README.md
rm CHANGES.md
rm -r "bin"

# Add any new files, disable error trapping and then check to see if there are any results, if not, don't run the svn add command as it fails.
set +e
svn status | grep -v "^.[ \t]*\..*" | grep "^?"
if (( $? == 0 )); then
        set -e
        svn status | grep -v "^.[ \t]*\..*" | grep "^?" | awk '{print $2}' | xargs svn add $SVN_OPTIONS
fi
set -e

# Pause to allow checking
echo "About to commit $VERSION. Double-check $TMPDIR to make sure everything looks fine."
read -p "Hit Enter to continue."

# Commit the changes
svn commit -m "Updates for $VERSION release." $SVN_OPTIONS

# tag_ur_it
svn copy "$PLUGINSVN/trunk" "$PLUGINSVN/tags/$VERSION" -m "Tagged v$VERSION." $SVN_OPTIONS

