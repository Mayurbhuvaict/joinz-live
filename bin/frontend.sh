#!/bin/bash
THEME_PATH=custom/static-plugins/JoinzTheme

SOURCE_CSS_PATH=frontend/dist/assets/css/app.css
JOINZ_CSS_PATH=$THEME_PATH/src/Resources/app/storefront/dist/css/app.scss

cd frontend/;
echo "Building frontend...";
yarn build;
cd ..;

#---------------------------------------

echo "Copying $SOURCE_CSS_PATH to $JOINZ_CSS_PATH";
rm $JOINZ_CSS_PATH;
cp $SOURCE_CSS_PATH $JOINZ_CSS_PATH;

#----------------------------------------

FIND="url(../img"
REPLACE="url(/bundles/joinztheme/img"
EXP="s~$FIND~$REPLACE~g"

echo "Replacing $FIND with $REPLACE..."
if [[ "$OSTYPE" == "darwin"* ]]; then
        sed -i'.original' -e $EXP $JOINZ_CSS_PATH
else
        sed -i $EXP $JOINZ_CSS_PATH
fi


#--------------------------------------

SOURCE_JS_PATH=frontend/dist/assets/js/app.js
JOINZ_JS_PATH=$THEME_PATH/src/Resources/app/storefront/dist/js/app.js

echo "Copying $SOURCE_JS_PATH to $JOINZ_JS_PATH";
rm $JOINZ_JS_PATH;
cp $SOURCE_JS_PATH $JOINZ_JS_PATH;

#------------------------------------

echo "Compiling theme...";
bin/console theme:compile

#------------------------------------
SOURCE_ASSETS=frontend/dist/assets/img/*
JOINZ_ASSETS=$THEME_PATH/src/Resources/public/img

echo "Copying $SOURCE_ASSETS to $JOINZ_ASSETS";
cp -R $SOURCE_ASSETS $JOINZ_ASSETS

#-----------------------------------

echo "Installing assets..."
bin/console assets:install
