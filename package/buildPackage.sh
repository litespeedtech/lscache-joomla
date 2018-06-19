rm -f ./build/*.zip
zip -r ./build/com_lscache.zip ../com_lscache/*
zip -r ./build/lscache_plugin.zip ../lscache_plugin/*
zip -r ./lscache-latest.zip ./build/*
echo Latest package has been built to lscache-latest.zip

